<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KodikEpisodeImportService
{
    protected string $apiUrl = 'https://kodikapi.com';
    protected string $apiToken;

    public function __construct()
    {
        $this->apiToken = config('services.kodik.token');
    }

    public function importAllEpisodes(bool $isInitialImport = true): ImportLog
    {
        $importLog = ImportLog::create([
            'import_type' => $isInitialImport ? 'episodes_initial' : 'episodes_update',
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $anime = Anime::all();
            $totalAnime = $anime->count();
            $processed = 0;

            foreach ($anime as $animeItem) {
                $this->importEpisodesForAnime($animeItem, $isInitialImport, $importLog);
                $processed++;

                if ($processed % 100 === 0) {
                    Log::info("Episodes import progress: {$processed}/{$totalAnime}");
                }

                usleep(500000);
            }

            $importLog->update([
                'finished_at' => now(),
                'status' => 'completed',
            ]);

        } catch (\Exception $e) {
            $importLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'errors' => json_encode(['error' => $e->getMessage()]),
            ]);

            Log::error('Episodes import failed', ['exception' => $e->getMessage()]);
        }

        return $importLog;
    }

    public function importEpisodesForAnime(Anime $anime, bool $isInitialImport, ImportLog $importLog): void
    {
        try {
            $kodikAnime = $this->searchAnimeInKodik($anime);

            if (!$kodikAnime) {
                Log::info("Anime not found in Kodik: {$anime->title}");
                return;
            }

            $episodes = $this->fetchEpisodesFromKodik($kodikAnime['id']);

            foreach ($episodes as $episodeData) {
                $this->processEpisode($anime, $episodeData, $isInitialImport, $importLog);
            }

        } catch (\Exception $e) {
            Log::error("Failed to import episodes for anime {$anime->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function searchAnimeInKodik(Anime $anime): ?array
    {
        $titles = $this->resolveTitles($anime);

        foreach ($titles as $title) {
            $result = $this->searchByTitle($title);
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    protected function resolveTitles(Anime $anime): array
    {
        $titles = [];

        if ($anime->title) {
            $titles[] = $anime->title;
        }

        if ($anime->slug) {
            $titles[] = str_replace('-', ' ', $anime->slug);
        }

        return array_filter(array_unique($titles));
    }

    protected function searchByTitle(string $title): ?array
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl . '/search', [
                'token' => $this->apiToken,
                'title' => $title,
                'types' => 'anime-serial',
                'limit' => 1,
            ]);

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            if (isset($data['results']) && count($data['results']) > 0) {
                return $data['results'][0];
            }

        } catch (\Exception $e) {
            Log::error('Kodik search failed', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function fetchEpisodesFromKodik(string $kodikId): array
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl . '/list', [
                'token' => $this->apiToken,
                'id' => $kodikId,
            ]);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();

            if (!isset($data['results']) || empty($data['results'])) {
                return [];
            }

            $episodes = [];
            foreach ($data['results'] as $result) {
                if (isset($result['seasons'])) {
                    foreach ($result['seasons'] as $seasonNumber => $season) {
                        if (isset($season['episodes'])) {
                            foreach ($season['episodes'] as $episodeNumber => $episodeUrl) {
                                $episodes[] = [
                                    'season' => $seasonNumber,
                                    'episode' => $episodeNumber,
                                    'url' => $episodeUrl,
                                    'translation' => $result['translation']['title'] ?? null,
                                    'quality' => $result['quality'] ?? null,
                                ];
                            }
                        }
                    }
                }
            }

            return $episodes;

        } catch (\Exception $e) {
            Log::error('Failed to fetch episodes from Kodik', [
                'kodik_id' => $kodikId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function processEpisode(Anime $anime, array $episodeData, bool $isInitialImport, ImportLog $importLog): void
    {
        $externalEpisodeId = $this->generateExternalEpisodeId($anime->external_id, $episodeData);

        $existing = Episode::where('external_episode_id', $externalEpisodeId)->first();

        if ($existing && $isInitialImport) {
            $importLog->increment('total_skipped');
            return;
        }

        $mappedData = $this->mapKodikEpisodeToDbFields($anime, $episodeData);

        if ($existing) {
            $existing->update($mappedData);
            $importLog->increment('total_updated');
        } else {
            Episode::create($mappedData);
            $importLog->increment('total_created');
        }

        $importLog->increment('total_processed');
    }

    protected function generateExternalEpisodeId(string $animeExternalId, array $episodeData): string
    {
        return sprintf(
            'kodik_%s_s%s_e%s',
            $animeExternalId,
            $episodeData['season'],
            $episodeData['episode']
        );
    }

    protected function mapKodikEpisodeToDbFields(Anime $anime, array $episodeData): array
    {
        return [
            'anime_id' => $anime->id,
            'episode_number' => (int) $episodeData['episode'],
            'season_number' => (int) $episodeData['season'],
            'title' => null,
            'player_url' => $episodeData['url'],
            'player_iframe' => $episodeData['url'],
            'external_episode_id' => $this->generateExternalEpisodeId($anime->external_id, $episodeData),
            'external_source' => 'kodik',
            'source' => 'kodik',
            'translator' => $episodeData['translation'],
            'quality' => $episodeData['quality'],
            'aired_at' => null,
            'release_date' => null,
            'duration' => null,
            'thumbnail_url' => null,
            'poster_url' => null,
        ];
    }
}
