@extends('layouts.admin')

@section('title', 'Anime Management - AniYume Admin')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-800">Anime Management</h1>
    <a href="{{ route('admin.anime.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
        Add New Anime
    </a>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title..." 
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All</option>
                <option value="planned" {{ request('status') === 'planned' ? 'selected' : '' }}>Planned</option>
                <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                <option value="finished" {{ request('status') === 'finished' ? 'selected' : '' }}>Finished</option>
                <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
            <select name="type" class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All</option>
                <option value="tv" {{ request('type') === 'tv' ? 'selected' : '' }}>TV</option>
                <option value="movie" {{ request('type') === 'movie' ? 'selected' : '' }}>Movie</option>
                <option value="ova" {{ request('type') === 'ova' ? 'selected' : '' }}>OVA</option>
                <option value="ona" {{ request('type') === 'ona' ? 'selected' : '' }}>ONA</option>
                <option value="special" {{ request('type') === 'special' ? 'selected' : '' }}>Special</option>
                <option value="music" {{ request('type') === 'music' ? 'selected' : '' }}>Music</option>
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($anime as $item)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @if($item->poster_url)
                                <img src="{{ $item->poster_url }}" alt="{{ $item->title }}" class="w-12 h-16 object-cover rounded mr-3">
                            @endif
                            <div>
                                <a href="{{ route('admin.anime.show', $item->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    {{ $item->title }}
                                </a>
                                @if($item->nsfw_flag)
                                    <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded">NSFW</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap uppercase text-sm">{{ $item->type }}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize text-sm">{{ $item->status }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $item->year ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $item->rating ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $item->external_source }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <a href="{{ route('admin.anime.edit', $item->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('admin.anime.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No anime found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $anime->links() }}
</div>
@endsection
