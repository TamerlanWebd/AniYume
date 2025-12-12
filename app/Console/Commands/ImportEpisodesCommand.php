<?php

namespace App\Console\Commands;

use App\Jobs\ImportEpisodesJob;
use App\Models\Anime;
use App\Models\ImportLog;
use App\Services\KodikEpisodeImportService;
use Illuminate\Console\Command;

class ImportEpisodesCommand extends Command
{
    protected $signature = 'import:episodes 
                            {--initial : Run initial import (skip existing episodes)}
                            {--update : Run update import (update existing episodes)}
                            {--anime= : Import episodes for specific anime ID}
                            {--sync : Run synchronously without queue}';

    protected $description = 'Import episodes from Kodik API';

    public function handle(KodikEpisodeImportService $importService): int
    {
        $isInitialImport = $this->option('initial');
        $isUpdate = $this->option('update');
        $specificAnimeId = $this->option('anime');
        $syncMode = $this->option('sync');

        if (!$isInitialImport && !$isUpdate) {
            $this->error('You must specify either --initial or --update flag');
            return self::FAILURE;
        }

        if ($isInitialImport && $isUpdate) {
            $this->error('You cannot use both --initial and --update flags');
            return self::FAILURE;
        }

        $importType = $isInitialImport ? 'episodes_initial' : 'episodes_update';

        $this->info("Starting {$importType} import...");

        if ($specificAnimeId) {
            return $this->importSpecificAnime((int) $specificAnimeId, $isInitialImport, $importService);
        }

        if ($syncMode) {
            return $this->importSync($isInitialImport, $importService);
        }

        return $this->importAsync($isInitialImport);
    }

    protected function importSpecificAnime(int $animeId, bool $isInitialImport, KodikEpisodeImportService $importService): int
    {
        $anime = Anime::find($animeId);

        if (!$anime) {
            $this->error("Anime with ID {$animeId} not found");
            return self::FAILURE;
        }

        $this->info("Importing episodes for: {$anime->title}");

        $importLog = ImportLog::create([
            'import_type' => $isInitialImport ? 'episodes_initial' : 'episodes_update',
            'started_at' => now(),
            'status' => 'running',
        ]);

        $importService->importEpisodesForAnime($anime, $isInitialImport, $importLog);

        $importLog->update([
            'finished_at' => now(),
            'status' => 'completed',
        ]);

        $this->info('Import completed!');
        $this->displayStats($importLog);

        return self::SUCCESS;
    }

    protected function importSync(bool $isInitialImport, KodikEpisodeImportService $importService): int
    {
        $this->info('Running synchronous import...');
        
        $importLog = $importService->importAllEpisodes($isInitialImport);

        $this->info('Import completed!');
        $this->displayStats($importLog);

        return $importLog->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }

    protected function importAsync(bool $isInitialImport): int
    {
        $this->info('Dispatching import jobs to queue...');

        $importLog = ImportLog::create([
            'import_type' => $isInitialImport ? 'episodes_initial' : 'episodes_update',
            'started_at' => now(),
            'status' => 'running',
        ]);

        $anime = Anime::all();
        $totalAnime = $anime->count();

        $bar = $this->output->createProgressBar($totalAnime);
        $bar->start();

        foreach ($anime as $animeItem) {
            ImportEpisodesJob::dispatch($animeItem->id, $isInitialImport, $importLog->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Dispatched {$totalAnime} jobs successfully!");
        $this->info("Import Log ID: {$importLog->id}");
        $this->info('Run "php artisan queue:work" to process the jobs');

        return self::SUCCESS;
    }

    protected function displayStats(ImportLog $importLog): void
    {
        $this->newLine();
        $this->info('Import Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $importLog->total_processed],
                ['Created', $importLog->total_created],
                ['Updated', $importLog->total_updated],
                ['Skipped', $importLog->total_skipped],
                ['Status', $importLog->status],
                ['Duration', $importLog->started_at->diffForHumans($importLog->finished_at, true)],
            ]
        );
    }
}
