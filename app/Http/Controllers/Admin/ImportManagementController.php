<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportAnimeJob;
use App\Models\ImportLog;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ImportManagementController extends Controller
{
    public function index()
    {
        $latestImport = ImportLog::latest()->first();
        $stats = [
            'total_imports' => ImportLog::count(),
            'successful_imports' => ImportLog::where('status', 'completed')->count(),
            'failed_imports' => ImportLog::where('status', 'failed')->count(),
            'running_imports' => ImportLog::where('status', 'running')->count(),
        ];

        return view('admin.import.index', compact('latestImport', 'stats'));
    }

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
            'user_id' => auth()->id(),
            'action' => 'run_import',
            'description' => "Started {$validated['type']} import (Log ID: {$importLog->id})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.import.index')
            ->with('success', 'Import started successfully. Check logs for progress.');
    }

    public function logs(Request $request)
    {
        $logs = ImportLog::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.import.logs', compact('logs'));
    }
}
