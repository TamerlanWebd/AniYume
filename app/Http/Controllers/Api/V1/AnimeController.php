<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AnimeCollection;
use App\Http\Resources\Api\V1\AnimeResource;
use App\Models\Anime;
use Illuminate\Http\Request;
use App\Http\Resources\EpisodeResource;
use App\Models\Episode;

class AnimeController extends Controller
{
    public function index(Request $request)
    {
        $query = Anime::with('tags');

        if ($request->has('search')) {
            $query->where('title', 'ILIKE', '%' . $request->search . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
            });
        }

        if ($request->has('nsfw')) {
            $query->where('nsfw_flag', filter_var($request->nsfw, FILTER_VALIDATE_BOOLEAN));
        } else {
            $query->where('nsfw_flag', false);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $validSortColumns = ['title', 'rating', 'year', 'popularity', 'favorites', 'created_at'];
        if (in_array($sortBy, $validSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = min($request->get('per_page', 20), 100);

        return new AnimeCollection(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function show(string $slug)
    {
        $anime = Anime::with('tags')->where('slug', $slug)->firstOrFail();

        return new AnimeResource($anime);
    }
    public function episodes(Anime $anime)
    {
        $episodes = $anime->episodes()
            ->orderBy('episode_number')
            ->orderBy('translation_name')
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
