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

@if($episodes->count() > 0)
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Episodes ({{ $episodes->count() }} tracks)</h2>
        <div class="space-x-2">
            <form action="{{ route('admin.episodes.import-for-anime', $anime->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm"
                        onclick="return confirm('Re-import/update all episodes and translations for this anime?')">
                    🔄 Re-import All Translations
                </button>
            </form>
            <a href="{{ route('admin.episodes.index', ['anime_id' => $anime->id]) }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm inline-block">
                📝 Manage Episodes
            </a>
        </div>
    </div>

    @php
        $groupedByTranslator = $episodes->groupBy('translator')->sortBy(function($group) {
            return $group->first()->priority;
        });
        $uniqueEpisodes = $episodes->unique('episode_number')->sortBy('episode_number');
    @endphp

    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Select Translation:</label>
        <select id="translation-selector" class="w-full max-w-md px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @foreach($groupedByTranslator as $translator => $translatorEpisodes)
                @php
                    $firstEp = $translatorEpisodes->first();
                    $translationType = $firstEp->translation_type === 'subtitles' ? '📝 SUB' : '🎤 DUB';
                    $episodeCount = $translatorEpisodes->count();
                @endphp
                <option value="{{ $translator }}" data-priority="{{ $firstEp->priority }}">
                    {{ $translationType }} - {{ $translator }} ({{ $episodeCount }} ep) - {{ $firstEp->quality }}
                </option>
            @endforeach
        </select>
    </div>

    <div id="player-container" class="mb-6 hidden">
        <div class="aspect-video bg-black rounded-lg overflow-hidden">
            <iframe id="player-iframe" src="" class="w-full h-full" frameborder="0" allowfullscreen allow="autoplay *; fullscreen *"></iframe>
        </div>
        <div class="mt-2 flex justify-between items-center">
            <div id="episode-info" class="text-sm text-gray-600"></div>
            <button onclick="closePlayer()" class="text-red-600 hover:text-red-800 text-sm font-bold">✕ Close Player</button>
        </div>
    </div>

    <div id="episodes-container" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
        @foreach($groupedByTranslator as $translator => $translatorEpisodes)
            <div class="translation-episodes" data-translator="{{ $translator }}" style="display: none; grid-column: 1 / -1;">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                    @foreach($translatorEpisodes->sortBy('episode_number') as $episode)
                        <button onclick="playEpisode('{{ $episode->player_iframe }}', '{{ $episode->episode_number }}', '{{ $episode->translator }}', '{{ $episode->quality }}')"
                                class="bg-indigo-100 hover:bg-indigo-200 text-indigo-800 px-3 py-3 rounded text-center transition group">
                            <div class="font-bold text-lg">{{ $episode->episode_number }}</div>
                            <div class="text-xs mt-1 text-gray-600">{{ $episode->quality }}</div>
                            <div class="text-xs mt-1 text-indigo-600 opacity-0 group-hover:opacity-100">▶ Play</div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@else
<div class="mt-6 bg-white rounded-lg shadow p-6 text-center">
    <p class="text-gray-500 mb-4">No episodes available for this anime</p>
    <form action="{{ route('admin.episodes.import-for-anime', $anime->id) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            Import Episodes
        </button>
    </form>
</div>
@endif

<script>
const translationSelector = document.getElementById('translation-selector');
const episodesContainer = document.getElementById('episodes-container');

translationSelector.addEventListener('change', function() {
    const selectedTranslator = this.value;
    
    document.querySelectorAll('.translation-episodes').forEach(el => {
        el.style.display = 'none';
    });
    
    const selectedEpisodes = document.querySelector(`.translation-episodes[data-translator="${selectedTranslator}"]`);
    if (selectedEpisodes) {
        selectedEpisodes.style.display = 'block';
    }
});

translationSelector.dispatchEvent(new Event('change'));

function playEpisode(iframeUrl, episodeNumber, translator, quality) {
    const container = document.getElementById('player-container');
    const iframe = document.getElementById('player-iframe');
    const info = document.getElementById('episode-info');
    
    iframe.src = iframeUrl;
    info.textContent = `Episode ${episodeNumber} - ${translator} - ${quality}`;
    container.classList.remove('hidden');
    
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closePlayer() {
    const container = document.getElementById('player-container');
    const iframe = document.getElementById('player-iframe');
    
    iframe.src = '';
    container.classList.add('hidden');
}
</script>
@endsection
