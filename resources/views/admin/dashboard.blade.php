@extends('layouts.admin')

@section('title', 'Dashboard - AniYume Admin')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Anime</div>
        <div class="text-3xl font-bold text-indigo-600">{{ number_format($total_anime) }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Tags</div>
        <div class="text-3xl font-bold text-green-600">{{ number_format($total_tags) }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="text-gray-500 text-sm mb-2">Total Users</div>
        <div class="text-3xl font-bold text-purple-600">{{ number_format($total_users) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Anime by Status</h2>
        <div class="space-y-2">
            @foreach($anime_by_status as $status => $count)
                <div class="flex justify-between">
                    <span class="capitalize">{{ $status }}</span>
                    <span class="font-bold">{{ number_format($count) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">Anime by Type</h2>
        <div class="space-y-2">
            @foreach($anime_by_type as $type => $count)
                <div class="flex justify-between">
                    <span class="uppercase">{{ $type }}</span>
                    <span class="font-bold">{{ number_format($count) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-bold mb-4">Recent Imports</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Processed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recent_imports as $import)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $import->import_type }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded 
                                @if($import->status === 'completed') bg-green-100 text-green-800
                                @elseif($import->status === 'failed') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ $import->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($import->total_processed) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($import->total_created) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($import->total_updated) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $import->started_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No imports yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-bold mb-4">Latest Anime</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($latest_anime as $anime)
                    <tr>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.anime.show', $anime->id) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $anime->title }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap uppercase">{{ $anime->type }}</td>
                        <td class="px-6 py-4 whitespace-nowrap capitalize">{{ $anime->status }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $anime->year }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $anime->rating ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
