@extends('layouts.admin')

@section('title', 'Import Logs - AniYume Admin')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-800">Import Logs</h1>
    <a href="{{ route('admin.import.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
        Back to Import
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Processed</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Skipped</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($logs as $log)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">#{{ $log->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize">{{ $log->import_type }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded 
                            @if($log->status === 'completed') bg-green-100 text-green-800
                            @elseif($log->status === 'failed') bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            {{ $log->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ number_format($log->total_processed) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-green-600">{{ number_format($log->total_created) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-blue-600">{{ number_format($log->total_updated) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ number_format($log->total_skipped) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $log->started_at->format('Y-m-d H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($log->finished_at)
                            {{ $log->started_at->diffForHumans($log->finished_at, true) }}
                        @else
                            <span class="text-yellow-600">In progress...</span>
                        @endif
                    </td>
                </tr>
                @if($log->errors)
                    <tr class="bg-red-50">
                        <td colspan="9" class="px-6 py-4">
                            <div class="text-sm text-red-800">
                                <strong>Errors:</strong>
                                <pre class="mt-2 text-xs overflow-x-auto">{{ $log->errors }}</pre>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-gray-500">No import logs found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $logs->links() }}
</div>
@endsection
