@extends('layouts.admin')

@section('title', $anime->title . ' - AniYume Admin')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-800">{{ $anime->title }}</h1>
    <div class="space-x-2">
        <a href="{{ route('admin.anime.edit', $anime->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Edit
        </a>
        <a href="{{ route('admin.anime.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Back to List
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        @if($anime->poster_url)
            <img src="{{ $anime->poster_url }}" alt="{{ $anime->title }}" class="w-full rounded-lg mb-4">
        @endif

        <div class="space-y-2">
            <div>
                <span class="font-bold">Type:</span>
                <span class="uppercase">{{ $anime->type }}</span>
            </div>
            <div>
                <span class="font-bold">Status:</span>
                <span class="capitalize">{{ $anime->status }}</span>
            </div>
            <div>
                <span class="font-bold">Year:</span>
                <span>{{ $anime->year ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="font-bold">Rating:</span>
                <span>{{ $anime->rating ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="font-bold">Episodes:</span>
                <span>{{ $anime->number_of_episodes ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="font-bold">NSFW:</span>
                <span>{{ $anime->nsfw_flag ? 'Yes' : 'No' }}</span>
            </div>
        </div>
    </div>

    <div class="md:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Description</h2>
            <div class="text-gray-700">
                {!! $anime->description ?? 'No description available' !!}
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Additional Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="font-bold">Aired From:</span>
                    <span>{{ $anime->aired_from ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="font-bold">Aired To:</span>
                    <span>{{ $anime->aired_to ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="font-bold">Popularity:</span>
                    <span>{{ $anime->popularity ? number_format($anime->popularity) : 'N/A' }}</span>
                </div>
                <div>
                    <span class="font-bold">Favorites:</span>
                    <span>{{ $anime->favorites ? number_format($anime->favorites) : 'N/A' }}</span>
                </div>
                <div>
                    <span class="font-bold">External ID:</span>
                    <span>{{ $anime->external_id ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="font-bold">External Source:</span>
                    <span>{{ $anime->external_source }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Tags ({{ $anime->tags->count() }})</h2>
            <div class="flex flex-wrap gap-2">
                @forelse($anime->tags as $tag)
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">
                        {{ $tag->name }}
                    </span>
                @empty
                    <p class="text-gray-500">No tags assigned</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
