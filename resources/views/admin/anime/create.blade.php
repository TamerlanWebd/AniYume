@extends('layouts.admin')

@section('title', 'Create Anime - AniYume Admin')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Create New Anime</h1>
</div>

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.anime.store') }}" method="POST" class="bg-white rounded-lg shadow p-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="5"
                      class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Poster URL</label>
            <input type="url" name="poster_url" value="{{ old('poster_url') }}"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rating (0-10)</label>
            <input type="number" name="rating" value="{{ old('rating') }}" step="0.1" min="0" max="10"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
            <select name="status" required
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="planned" {{ old('status') === 'planned' ? 'selected' : '' }}>Planned</option>
                <option value="ongoing" {{ old('status') === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                <option value="finished" {{ old('status') === 'finished' ? 'selected' : '' }}>Finished</option>
                <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Paused</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
            <select name="type" required
                    class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="tv" {{ old('type') === 'tv' ? 'selected' : '' }}>TV</option>
                <option value="movie" {{ old('type') === 'movie' ? 'selected' : '' }}>Movie</option>
                <option value="ova" {{ old('type') === 'ova' ? 'selected' : '' }}>OVA</option>
                <option value="ona" {{ old('type') === 'ona' ? 'selected' : '' }}>ONA</option>
                <option value="special" {{ old('type') === 'special' ? 'selected' : '' }}>Special</option>
                <option value="music" {{ old('type') === 'music' ? 'selected' : '' }}>Music</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <input type="number" name="year" value="{{ old('year') }}" min="1900" max="2100"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Episodes</label>
            <input type="number" name="number_of_episodes" value="{{ old('number_of_episodes') }}" min="0"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Aired From</label>
            <input type="date" name="aired_from" value="{{ old('aired_from') }}"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Aired To</label>
            <input type="date" name="aired_to" value="{{ old('aired_to') }}"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center">
                <input type="checkbox" name="nsfw_flag" value="1" {{ old('nsfw_flag') ? 'checked' : '' }}
                       class="mr-2">
                <span class="text-sm font-medium text-gray-700">NSFW Content</span>
            </label>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
            <div class="border rounded p-4 max-h-64 overflow-y-auto">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach($tags as $tag)
                        <label class="flex items-center">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                   {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}
                                   class="mr-2">
                            <span class="text-sm">{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end space-x-4">
        <a href="{{ route('admin.anime.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Cancel
        </a>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            Create Anime
        </button>
    </div>
</form>
@endsection
