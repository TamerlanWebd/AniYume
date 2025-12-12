@extends('layouts.admin')

@section('title', 'Episodes Management - AniYume Admin')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-800">Episodes Management</h1>
    <button onclick="document.getElementById('import-modal').classList.remove('hidden')" 
            class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
        Import All Episodes
    </button>
</div>

@if($anime)
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold">{{ $anime->title }}</h2>
            <p class="text-gray-600">Total Episodes: {{ $episodes->total() }}</p>
        </div>
        <form action="{{ route('admin.episodes.import-for-anime', $anime->id) }}" method="POST">
            @csrf
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                    onclick="return confirm('Import episodes for this anime?')">
                Import Episodes for This Anime
            </button>
        </form>
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search Anime</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by anime title..." 
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Translator</label>
            <input type="text" name="translator" value="{{ request('translator') }}" 
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Quality</label>
            <select name="quality" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All</option>
                <option value="480p" {{ request('quality') === '480p' ? 'selected' : '' }}>480p</option>
                <option value="720p" {{ request('quality') === '720p' ? 'selected' : '' }}>720p</option>
                <option value="1080p" {{ request('quality') === '1080p' ? 'selected' : '' }}>1080p</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                Filter
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anime</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Season</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Episode</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Translator</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quality</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($episodes as $episode)
                <tr>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.anime.show', $episode->anime_id) }}" class="text-indigo-600 hover:text-indigo-800">
                            {{ $episode->anime->title }}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $episode->season_number ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap font-bold">{{ $episode->episode_number }}</td>
                    <td class="px-6 py-4">{{ $episode->translator ?? 'Unknown' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $episode->quality ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">{{ $episode->source }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        @if($episode->player_iframe)
                            <a href="{{ $episode->player_iframe }}" target="_blank" class="text-green-600 hover:text-green-900 mr-3">Watch</a>
                        @endif
                        <form action="{{ route('admin.episodes.destroy', $episode->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No episodes found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $episodes->links() }}
</div>

<div id="import-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">Import All Episodes</h3>
        
        <form action="{{ route('admin.episodes.import-all') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Import Type</label>
                <select name="type" required class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="initial">Initial Import (Skip existing)</option>
                    <option value="update">Update Import (Update existing)</option>
                </select>
            </div>

            <div class="flex justify-end space-x-4">
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Start Import
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
