<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AnimeResource;
use App\Models\Anime;
use App\Models\AuditLog;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnimeController extends Controller
{
    public function index(Request $request)
    {
        $query = Anime::with('tags');

        if ($request->has('search')) {
            $query->where('title', 'ILIKE', '%' . $request->search . '%');
        }

        $perPage = min($request->get('per_page', 20), 100);

        return AnimeResource::collection(
            $query->latest()->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'poster_url' => 'nullable|url|max:1024',
            'rating' => 'nullable|numeric|min:0|max:10',
            'year' => 'nullable|integer|min:1900|max:2100',
            'status' => 'required|in:planned,ongoing,finished,paused',
            'type' => 'required|in:tv,movie,ova,ona,special,music',
            'number_of_episodes' => 'nullable|integer|min:0',
            'aired_from' => 'nullable|date',
            'aired_to' => 'nullable|date',
            'nsfw_flag' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['title']);
        $validated['external_source'] = 'manual';

        $anime = Anime::create($validated);

        if (isset($validated['tags'])) {
            $anime->tags()->sync($validated['tags']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create_anime',
            'description' => "Created anime: {$anime->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new AnimeResource($anime->load('tags'));
    }

    public function show(string $id)
    {
        $anime = Anime::with('tags')->findOrFail($id);

        return new AnimeResource($anime);
    }

    public function update(Request $request, string $id)
    {
        $anime = Anime::findOrFail($id);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'poster_url' => 'nullable|url|max:1024',
            'rating' => 'nullable|numeric|min:0|max:10',
            'year' => 'nullable|integer|min:1900|max:2100',
            'status' => 'in:planned,ongoing,finished,paused',
            'type' => 'in:tv,movie,ova,ona,special,music',
            'number_of_episodes' => 'nullable|integer|min:0',
            'aired_from' => 'nullable|date',
            'aired_to' => 'nullable|date',
            'nsfw_flag' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
        ]);

        if (isset($validated['title']) && $validated['title'] !== $anime->title) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $anime->id);
        }

        $anime->update($validated);

        if (isset($validated['tags'])) {
            $anime->tags()->sync($validated['tags']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update_anime',
            'description' => "Updated anime: {$anime->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new AnimeResource($anime->load('tags'));
    }

    public function destroy(Request $request, string $id)
    {
        $anime = Anime::findOrFail($id);
        $title = $anime->title;

        $anime->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete_anime',
            'description' => "Deleted anime: {$title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Anime deleted successfully',
        ]);
    }

    protected function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (true) {
            $query = Anime::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
