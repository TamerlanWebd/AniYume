<?php

namespace App\Console\Commands;

use App\Models\Anime;
use App\Models\Episode;
use App\Services\KodikService;
use Illuminate\Console\Command;

class ImportEpisodesCommand extends Command
{
    protected $signature = 'import:episodes
                          {--anime= : Import episodes for specific anime ID}
                          {--update : Update existing episodes}
                          {--sync : Remove episodes that no longer exist in source}
                          {--batch=100 : Number of anime to process in batch}
                          {--offset=0 : Starting offset for batch processing}';

    protected $description = 'Import episodes from Kodik API';

    private array $stats = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function handle(KodikService $kodikService): int
    {
        $startTime = microtime(true);
        $this->info('Starting episodes import...');

        if ($animeId = $this->option('anime')) {
            $this->importForSingleAnime($animeId, $kodikService);
        } else {
            $this->importForMultipleAnime($kodikService);
        }

        $this->displayStats($startTime);
        return Command::SUCCESS;
    }

    private function importForSingleAnime(int $animeId, KodikService $kodikService): void
    {
        $anime = Anime::find($animeId);
        if (!$anime) {
            $this->error("Anime with ID {$animeId} not found");
            return;
        }

        $this->info("Importing episodes for: {$anime->title}");
        $this->processAnime($anime, $kodikService);
    }

    private function importForMultipleAnime(KodikService $kodikService): void
    {
        $batch = (int) $this->option('batch');
        $offset = (int) $this->option('offset');

        $query = Anime::query()
            ->whereNotNull('shikimori_id')
            ->orderBy('id')
            ->skip($offset)
            ->take($batch);

        $totalAnime = $query->count();
        $this->info("Processing {$totalAnime} anime (offset: {$offset}, batch: {$batch})");

        $progressBar = $this->output->createProgressBar($totalAnime);
        $progressBar->start();

        foreach ($query->cursor() as $anime) {
            $this->processAnime($anime, $kodikService);
            $progressBar->advance();
            
            sleep(2);
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function processAnime(Anime $anime, KodikService $kodikService): void
    {
        try {
            if (!$anime->shikimori_id) {
                $this->stats['skipped']++;
                return;
            }

            $kodikData = $kodikService->searchByShikimoriId($anime->shikimori_id);

            if (empty($kodikData)) {
                $this->stats['skipped']++;
                return;
            }

            $kodikId = $kodikData[0]['id'] ?? null;
            if (!$kodikId) {
                $this->stats['skipped']++;
                return;
            }

            $episodes = $kodikService->getEpisodes($kodikId);

            foreach ($episodes as $episodeData) {
                $this->importEpisode($anime, $episodeData, $kodikData[0]);
            }

            if ($this->option('sync')) {
                $this->syncEpisodes($anime, $episodes);
            }

        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->warn("Error processing anime {$anime->id}: {$e->getMessage()}");
        }
    }

    private function importEpisode(Anime $anime, array $episodeData, array $sourceData): void
    {
        $episodeNumber = $episodeData['episode'];
        $translation = $episodeData['translation'];

        $existingEpisode = Episode::where('anime_id', $anime->id)
            ->where('episode_number', $episodeNumber)
            ->where('translation_name', $translation['title'])
            ->first();

        $data = [
            'anime_id' => $anime->id,
            'episode_number' => $episodeNumber,
            'title' => $episodeData['title'] ?? "Episode {$episodeNumber}",
            'source' => 'kodik',
            'source_id' => $sourceData['id'],
            'translation_type' => $translation['type'],
            'translation_name' => $translation['title'],
            'translation_id' => $translation['id'],
            'quality' => $sourceData['quality'] ?? '720p',
            'player_link' => $episodeData['link'],
            'screenshot_url' => $sourceData['screenshots'][0] ?? null,
            'duration_minutes' => null,
        ];

        if ($existingEpisode && $this->option('update')) {
            $existingEpisode->update($data);
            $this->stats['updated']++;
        } elseif (!$existingEpisode) {
            Episode::create($data);
            $this->stats['created']++;
        } else {
            $this->stats['skipped']++;
        }

        $this->stats['processed']++;
    }

    private function syncEpisodes(Anime $anime, array $kodikEpisodes): void
    {
        $kodikEpisodeNumbers = collect($kodikEpisodes)->pluck('episode')->unique();
        
        Episode::where('anime_id', $anime->id)
            ->where('source', 'kodik')
            ->whereNotIn('episode_number', $kodikEpisodeNumbers)
            ->delete();
    }

    private function displayStats(float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->info('Import completed!');
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $this->stats['processed']],
                ['Created', $this->stats['created']],
                ['Updated', $this->stats['updated']],
                ['Skipped', $this->stats['skipped']],
                ['Errors', $this->stats['errors']],
                ['Duration', $duration . ' seconds'],
            ]
        );
    }
}
