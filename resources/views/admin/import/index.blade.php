@extends('layouts.admin')

@section('title', 'Import Management - AniYume Admin')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Import Management</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Imports</div>
        <div class="text-3xl font-bold text-indigo-600">{{ $stats['total_imports'] }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Successful</div>
        <div class="text-3xl font-bold text-green-600">{{ $stats['successful_imports'] }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Failed</div>
        <div class="text-3xl font-bold text-red-600">{{ $stats['failed_imports'] }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Running</div>
        <div class="text-3xl font-bold text-yellow-600">{{ $stats['running_imports'] }}</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-bold mb-4">Start New Import</h2>
    
    <form action="{{ route('admin.import.run') }}" method="POST" class="space-y-4">
        @csrf
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Import Type</label>
            <select name="type" required class="w-full max-w-md px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="initial">Initial Import (Skip existing anime)</option>
                <option value="update">Update Import (Update existing anime)</option>
            </select>
            <p class="mt-2 text-sm text-gray-500">
                <strong>Initial:</strong> Imports only new anime, skips existing ones<br>
                <strong>Update:</strong> Updates existing anime with latest data from AniList
            </p>
        </div>

        <div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700"
                    onclick="return confirm('Are you sure you want to start the import? Make sure queue worker is running.')">
                Start Import
            </button>
        </div>
    </form>
</div>

@if($latestImport)
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-bold mb-4">Latest Import Status</h2>
    
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div>
            <div class="text-sm text-gray-500">Type</div>
            <div class="font-bold capitalize">{{ $latestImport->import_type }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Status</div>
            <div class="font-bold">
                <span class="px-2 py-1 text-xs rounded 
                    @if($latestImport->status === 'completed') bg-green-100 text-green-800
                    @elseif($latestImport->status === 'failed') bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800
                    @endif">
                    {{ $latestImport->status }}
                </span>
            </div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Processed</div>
            <div class="font-bold">{{ number_format($latestImport->total_processed) }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Created</div>
            <div class="font-bold text-green-600">{{ number_format($latestImport->total_created) }}</div>
        </div>
        <div>
            <div class="text-sm text-gray-500">Updated</div>
            <div class="font-bold text-blue-600">{{ number_format($latestImport->total_updated) }}</div>
        </div>
    </div>

    <div class="mt-4 pt-4 border-t">
        <div class="text-sm text-gray-500">Started: {{ $latestImport->started_at->format('Y-m-d H:i:s') }}</div>
        @if($latestImport->finished_at)
            <div class="text-sm text-gray-500">Finished: {{ $latestImport->finished_at->format('Y-m-d H:i:s') }}</div>
            <div class="text-sm text-gray-500">Duration: {{ $latestImport->started_at->diffForHumans($latestImport->finished_at, true) }}</div>
        @endif
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Import History</h2>
        <a href="{{ route('admin.import.logs') }}" class="text-indigo-600 hover:text-indigo-800">View All Logs →</a>
    </div>
    
    <p class="text-gray-600">
        To view detailed import history and logs, click "View All Logs" above.
    </p>
</div>
@endsection
