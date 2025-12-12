<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Tag;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnimeManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Anime::with('tags');

        if ($request->filled('search')) {
            $query->where('title', 'ILIKE', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $anime = $query->latest()->paginate(20);

        return view('admin.anime.index', compact('anime'));
    }

    public function create()
    {
        $tags = Tag::orderBy('name')->get();
        return view('admin.anime.create', compact('tags'));
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
        $validated['nsfw_flag'] = $request->has('nsfw_flag');

        $anime = Anime::create($validated);

        if (isset($validated['tags'])) {
            $anime->tags()->sync($validated['tags']);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create_anime',
            'description' => "Created anime: {$anime->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.anime.index')
            ->with('success', 'Anime created successfully');
    }

    public function show(string $id)
    {
        $anime = Anime::with('tags')->findOrFail($id);
        return view('admin.anime.show', compact('anime'));
    }

    public function edit(string $id)
    {
        $anime = Anime::with('tags')->findOrFail($id);
        $tags = Tag::orderBy('name')->get();
        return view('admin.anime.edit', compact('anime', 'tags'));
    }

    public function update(Request $request, string $id)
    {
        $anime = Anime::findOrFail($id);

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

        if ($validated['title'] !== $anime->title) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $anime->id);
        }

        $validated['nsfw_flag'] = $request->has('nsfw_flag');

        $anime->update($validated);

        if (isset($validated['tags'])) {
            $anime->tags()->sync($validated['tags']);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_anime',
            'description' => "Updated anime: {$anime->title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.anime.index')
            ->with('success', 'Anime updated successfully');
    }

    public function destroy(Request $request, string $id)
    {
        $anime = Anime::findOrFail($id);
        $title = $anime->title;

        $anime->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete_anime',
            'description' => "Deleted anime: {$title}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.anime.index')
            ->with('success', 'Anime deleted successfully');
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
