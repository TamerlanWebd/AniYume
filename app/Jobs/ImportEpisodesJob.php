<?php

namespace App\Jobs;

use App\Models\Anime;
use App\Models\ImportLog;
use App\Services\KodikEpisodeImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportEpisodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(
        public int $animeId,
        public bool $isInitialImport,
        public int $importLogId
    ) {}

    public function handle(KodikEpisodeImportService $importService): void
    {
        $importLog = ImportLog::find($this->importLogId);

        if (!$importLog) {
            Log::error('ImportLog not found', ['id' => $this->importLogId]);
            return;
        }

        $anime = Anime::find($this->animeId);

        if (!$anime) {
            Log::error('Anime not found', ['id' => $this->animeId]);
            return;
        }

        try {
            $importService->importEpisodesForAnime($anime, $this->isInitialImport, $importLog);
        } catch (\Exception $e) {
            Log::error('Import episodes job failed', [
                'anime_id' => $this->animeId,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportEpisodesJob failed', [
            'anime_id' => $this->animeId,
            'exception' => $exception->getMessage(),
        ]);

        $importLog = ImportLog::find($this->importLogId);
        
        if ($importLog) {
            $errors = json_decode($importLog->errors, true) ?? [];
            $errors[] = [
                'anime_id' => $this->animeId,
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
