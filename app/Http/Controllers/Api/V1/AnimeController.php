<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnimeResource;
use App\Http\Resources\EpisodeResource;
use App\Models\Anime;
use App\Models\Episode;
use Illuminate\Http\Request;

class AnimeController extends Controller
{
    public function index(Request $request)
    {
        $query = Anime::with(['genres', 'studios']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('slug', $request->genre);
            });
        }

        if ($request->has('studio')) {
            $query->whereHas('studios', function ($q) use ($request) {
                $q->where('slug', $request->studio);
            });
        }

        if ($request->has('year')) {
            $query->whereYear('aired_from', $request->year);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('title_english', 'like', "%{$request->search}%")
                  ->orWhere('title_japanese', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'rating':
                    $query->orderByDesc('rating');
                    break;
                case 'popularity':
                    $query->orderByDesc('popularity');
                    break;
                case 'newest':
                    $query->orderByDesc('aired_from');
                    break;
                default:
                    $query->orderByDesc('created_at');
            }
        } else {
            $query->orderByDesc('created_at');
        }

        $anime = $query->paginate(20);

        return AnimeResource::collection($anime);
    }

    public function show(Anime $anime)
    {
        $anime->load(['genres', 'studios', 'tags']);
        return new AnimeResource($anime);
    }

    public function episodes(Anime $anime)
    {
        $episodes = $anime->episodes()
            ->orderBy('episode_number')
            ->orderBy('translator')
            ->paginate(50);

        return EpisodeResource::collection($episodes);
    }

    public function episode(Anime $anime, Episode $episode)
    {
        if ($episode->anime_id !== $anime->id) {
            abort(404, 'Episode not found for this anime');
        }

        return new EpisodeResource($episode);
    }

    public function genres()
    {
        $genres = \App\Models\Genre::withCount('anime')
            ->orderBy('name')
            ->get();

        return response()->json($genres);
    }

    public function studios()
    {
        $studios = \App\Models\Studio::withCount('anime')
            ->orderBy('name')
            ->get();

        return response()->json($studios);
    }
}
