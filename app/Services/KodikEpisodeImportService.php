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
            $kodikData = $this->searchAnimeInKodik($anime);

            if (!$kodikData || empty($kodikData['results'])) {
                Log::info("Anime not found in Kodik: {$anime->title}");
                return;
            }

            foreach ($kodikData['results'] as $result) {
                $episodes = $this->extractEpisodesFromResult($result);
                
                foreach ($episodes as $episodeData) {
                    $this->processEpisode($anime, $episodeData, $isInitialImport, $importLog);
                }
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
            $results = $this->searchAllByTitle($title);
            if (!empty($results)) {
                return [
                    'id' => 'multiple',
                    'results' => $results,
                ];
            }
        }

        return null;
    }

    protected function searchAllByTitle(string $title): array
    {
        try {
            $response = Http::timeout(30)->get($this->apiUrl . '/search', [
                'token' => $this->apiToken,
                'title' => $title,
                'types' => 'anime-serial',
                'with_episodes' => 'true',
                'with_material_data' => 'true',
                'limit' => 100,
            ]);
    
            if ($response->failed()) {
                return [];
            }
    
            $data = $response->json();
    
            if (isset($data['results']) && count($data['results']) > 0) {
                Log::info("Found {$data['total']} results for: {$title}");
                return $data['results'];
            }
    
        } catch (\Exception $e) {
            Log::error('Kodik search failed', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    
        return [];
    }
    

    protected function extractEpisodesFromResult(array $result): array
    {
        $episodes = [];
        $translationType = $result['translation']['type'] ?? 'voice';
        $translationTitle = $result['translation']['title'] ?? 'Unknown';
        $priority = $this->getTranslationPriority($translationTitle, $translationType);

        if (isset($result['seasons'])) {
            foreach ($result['seasons'] as $seasonNumber => $season) {
                if (isset($season['episodes'])) {
                    foreach ($season['episodes'] as $episodeNumber => $episodeUrl) {
                        $episodes[] = [
                            'season' => (int) $seasonNumber,
                            'episode' => (int) $episodeNumber,
                            'url' => $episodeUrl,
                            'translation' => $translationTitle,
                            'translation_type' => $translationType === 'subtitles' ? 'subtitles' : 'voice',
                            'quality' => $result['quality'] ?? null,
                            'priority' => $priority,
                        ];
                    }
                }
            }
        }

        return $episodes;
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
            'kodik_%s_s%s_e%s_%s',
            $animeExternalId,
            $episodeData['season'],
            $episodeData['episode'],
            md5(($episodeData['translation'] ?? 'default') . ($episodeData['translation_type'] ?? 'voice'))
        );
    }

    protected function mapKodikEpisodeToDbFields(Anime $anime, array $episodeData): array
    {
        $playerUrl = 'https:' . $episodeData['url'];
        
        return [
            'anime_id' => $anime->id,
            'episode_number' => (int) $episodeData['episode'],
            'season_number' => (int) $episodeData['season'],
            'title' => null,
            'player_url' => $playerUrl,
            'player_iframe' => $playerUrl,
            'external_episode_id' => $this->generateExternalEpisodeId($anime->external_id, $episodeData),
            'external_source' => 'kodik',
            'source' => 'kodik',
            'translator' => $episodeData['translation'],
            'translation_type' => $episodeData['translation_type'] ?? 'voice',
            'quality' => $episodeData['quality'],
            'priority' => $episodeData['priority'] ?? 50,
            'aired_at' => null,
            'release_date' => null,
            'duration' => null,
            'thumbnail_url' => null,
            'poster_url' => null,
        ];
    }

    protected function getTranslationPriority(string $translator, string $type): int
    {
        if ($type === 'subtitles') {
            return 90;
        }

        $priorityMap = [
            'AniLibria.TV' => 10,
            'AniDUB' => 20,
            'SHIZA Project' => 25,
            'Amazing Dubbing' => 30,
            'StudioBand' => 35,
            'Dreamcast' => 40,
            'Команда Диди' => 45,
            'Субтитры' => 90,
        ];

        foreach ($priorityMap as $key => $priority) {
            if (stripos($translator, $key) !== false) {
                return $priority;
            }
        }

        return 50;
    }
}
