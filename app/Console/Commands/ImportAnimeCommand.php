<?php

namespace App\Console\Commands;

use App\Jobs\ImportAnimeJob;
use App\Models\ImportLog;
use App\Services\AnilistImportService;
use Illuminate\Console\Command;

class ImportAnimeCommand extends Command
{
    protected $signature = 'anime:import 
                            {--initial : Run initial import (skip existing anime)}
                            {--update : Run update import (update existing anime)}
                            {--page= : Import specific page only}
                            {--sync : Run synchronously without queue}';

    protected $description = 'Import anime from AniList API';

    public function handle(AnilistImportService $importService): int
    {
        $isInitialImport = $this->option('initial');
        $isUpdate = $this->option('update');
        $specificPage = $this->option('page');
        $syncMode = $this->option('sync');

        if (!$isInitialImport && !$isUpdate) {
            $this->error('You must specify either --initial or --update flag');
            return self::FAILURE;
        }

        if ($isInitialImport && $isUpdate) {
            $this->error('You cannot use both --initial and --update flags');
            return self::FAILURE;
        }

        $importType = $isInitialImport ? 'initial' : 'update';

        $this->info("Starting {$importType} import...");

        if ($specificPage) {
            return $this->importSpecificPage((int) $specificPage, $isInitialImport, $importService);
        }

        if ($syncMode) {
            return $this->importSync($isInitialImport, $importService);
        }

        return $this->importAsync($isInitialImport);
    }

    protected function importSpecificPage(int $page, bool $isInitialImport, AnilistImportService $importService): int
    {
        $this->info("Importing page {$page}...");

        $importLog = ImportLog::create([
            'import_type' => $isInitialImport ? 'initial' : 'update',
            'started_at' => now(),
            'status' => 'running',
        ]);

        $result = $importService->importPage($page, $isInitialImport, $importLog);

        $importLog->update([
            'finished_at' => now(),
            'status' => $result['success'] ? 'completed' : 'failed',
        ]);

        if ($result['success']) {
            $this->info("Page {$page} imported successfully");
            $this->displayStats($importLog);
            return self::SUCCESS;
        }

        $this->error("Page {$page} import failed: " . ($result['error'] ?? 'Unknown error'));
        return self::FAILURE;
    }

    protected function importSync(bool $isInitialImport, AnilistImportService $importService): int
    {
        $this->info('Running synchronous import...');
        
        $importLog = $importService->importAll($isInitialImport);

        $this->info('Import completed!');
        $this->displayStats($importLog);

        return $importLog->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }

    protected function importAsync(bool $isInitialImport): int
    {
        $this->info('Dispatching import jobs to queue...');

        $importLog = ImportLog::create([
            'import_type' => $isInitialImport ? 'initial' : 'update',
            'started_at' => now(),
            'status' => 'running',
        ]);

        ImportAnimeJob::dispatch(1, $isInitialImport, $importLog->id);

        $this->info('Import jobs dispatched successfully!');
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
