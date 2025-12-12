<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anime;
use App\Models\Tag;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_anime' => Anime::count(),
            'total_tags' => Tag::count(),
            'total_users' => User::count(),
            'recent_imports' => ImportLog::latest()->take(5)->get(),
            'anime_by_status' => Anime::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'anime_by_type' => Anime::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'latest_anime' => Anime::with('tags')->latest()->take(10)->get(),
        ];

        return view('admin.dashboard', $stats);
    }
}
