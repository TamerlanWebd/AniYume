<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::withCount('anime');

        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        $tags = $query->orderBy('name')->paginate(50);

        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $tag = Tag::create($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'create_tag',
            'description' => "Created tag: {$tag->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag created successfully');
    }

    public function edit(string $id)
    {
        $tag = Tag::withCount('anime')->findOrFail($id);
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, string $id)
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name,' . $id,
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $tag->update($validated);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'update_tag',
            'description' => "Updated tag: {$tag->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag updated successfully');
    }

    public function destroy(Request $request, string $id)
    {
        $tag = Tag::findOrFail($id);
        $name = $tag->name;

        $tag->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete_tag',
            'description' => "Deleted tag: {$name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.tags.index')
            ->with('success', 'Tag deleted successfully');
    }
}
