<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AnimeCollection;
use App\Http\Resources\Api\V1\AnimeResource;
use App\Models\Anime;
use Illuminate\Http\Request;

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
}
