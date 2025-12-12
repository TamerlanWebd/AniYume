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
            $this->info("Searching for: {$anime->title}");
            $kodikData = $kodikService->searchByTitle($anime->title);
    
            if (empty($kodikData)) {
                $this->stats['skipped']++;
                $this->warn("Not found in Kodik");
                return;
            }
    
            $this->info("Found: " . ($kodikData[0]['title'] ?? 'unknown'));
    
            $kodikId = $kodikData[0]['id'] ?? null;
            if (!$kodikId) {
                $this->stats['skipped']++;
                return;
            }
    
            $episodes = $kodikService->getEpisodes($kodikId);
            $this->info("Episodes found: " . count($episodes));
    
            if (empty($episodes)) {
                $this->stats['skipped']++;
                return;
            }
    
            foreach ($episodes as $episodeData) {
                try {
                    $this->importEpisode($anime, $episodeData, $kodikData[0]);
                } catch (\Exception $e) {
                    $this->error("Episode error: " . $e->getMessage());
                    $this->stats['errors']++;
                }
            }
    
            if ($this->option('sync')) {
                $this->syncEpisodes($anime, $episodes);
            }
    
        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->error("Error: " . $e->getMessage());
        }
    }
    

    private function importEpisode(Anime $anime, array $episodeData, array $sourceData): void
    {
        $episodeNumber = $episodeData['episode'];
        $translation = $episodeData['translation'];

        $existingEpisode = Episode::where('anime_id', $anime->id)
            ->where('episode_number', $episodeNumber)
            ->where('translator', $translation['title'])
            ->first();

        $data = [
            'anime_id' => $anime->id,
            'episode_number' => $episodeNumber,
            'season_number' => $episodeData['season'] ?? 1,
            'title' => $episodeData['title'] ?? "Episode {$episodeNumber}",
            'player_url' => $episodeData['link'],
            'player_iframe' => $episodeData['link'],
            'translator' => $translation['title'],
            'translation_type' => $translation['type'],
            'quality' => $sourceData['quality'] ?? '720p',
            'source' => 'kodik',
            'external_id' => $sourceData['id'],
            'external_source' => 'kodik',
            'thumbnail_url' => $sourceData['screenshots'][0] ?? null,
            'poster_url' => $sourceData['screenshots'][0] ?? null,
            'duration' => null,
            'priority' => 1,
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
