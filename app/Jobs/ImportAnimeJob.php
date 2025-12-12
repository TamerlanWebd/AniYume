<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Services\AnilistImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportAnimeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(
        public int $page,
        public bool $isInitialImport,
        public int $importLogId
    ) {}

    public function handle(AnilistImportService $importService): void
    {
        $importLog = ImportLog::find($this->importLogId);

        if (!$importLog) {
            Log::error('ImportLog not found', ['id' => $this->importLogId]);
            return;
        }

        $result = $importService->importPage($this->page, $this->isInitialImport, $importLog);

        if (!$result['success']) {
            Log::error('Import page failed', [
                'page' => $this->page,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            
            $this->fail(new \Exception($result['error'] ?? 'Import failed'));
            return;
        }

        if ($result['hasNextPage']) {
            self::dispatch($this->page + 1, $this->isInitialImport, $this->importLogId)
                ->delay(now()->addSeconds(2));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportAnimeJob failed', [
            'page' => $this->page,
            'exception' => $exception->getMessage(),
        ]);

        $importLog = ImportLog::find($this->importLogId);
        
        if ($importLog) {
            $errors = json_decode($importLog->errors, true) ?? [];
            $errors[] = [
                'page' => $this->page,
                'error' => $exception->getMessage(),
                'time' => now()->toISOString(),
            ];

            $importLog->update([
                'errors' => json_encode($errors),
                'status' => 'failed',
                'finished_at' => now(),
            ]);
        }
    }
}
