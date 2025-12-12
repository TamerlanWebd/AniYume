<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportAnimeJob;
use App\Models\AuditLog;
use App\Models\ImportLog;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function run(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:initial,update',
        ]);

        $isInitialImport = $validated['type'] === 'initial';

        $importLog = ImportLog::create([
            'import_type' => $validated['type'],
            'started_at' => now(),
            'status' => 'running',
        ]);

        ImportAnimeJob::dispatch(1, $isInitialImport, $importLog->id);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'run_import',
            'description' => "Started {$validated['type']} import (Log ID: {$importLog->id})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Import started successfully',
            'import_log_id' => $importLog->id,
            'type' => $validated['type'],
        ]);
    }

    public function status(Request $request, string $id)
    {
        $importLog = ImportLog::findOrFail($id);

        return response()->json([
            'id' => $importLog->id,
            'type' => $importLog->import_type,
            'status' => $importLog->status,
            'started_at' => $importLog->started_at?->toISOString(),
            'finished_at' => $importLog->finished_at?->toISOString(),
            'total_processed' => $importLog->total_processed,
            'total_created' => $importLog->total_created,
            'total_updated' => $importLog->total_updated,
            'total_skipped' => $importLog->total_skipped,
            'errors' => $importLog->errors ? json_decode($importLog->errors) : null,
        ]);
    }

    public function logs(Request $request)
    {
        $logs = ImportLog::orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($logs);
    }
}
