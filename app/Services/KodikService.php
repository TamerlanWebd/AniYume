<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class KodikService
{
    private string $baseUrl = 'https://kodikapi.com';
    private ?string $apiToken;

    public function __construct()
    {
        $this->apiToken = config('services.kodik.token', '');
    }

    public function searchByShikimoriId(?int $shikimoriId): array
    {
        if (!$shikimoriId) {
            return [];
        }

        $cacheKey = "kodik_anime_{$shikimoriId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($shikimoriId) {
            $params = [
                'shikimori_id' => $shikimoriId,
                'with_episodes' => true,
                'with_material_data' => true,
            ];

            if ($this->apiToken) {
                $params['token'] = $this->apiToken;
            }

            $response = Http::timeout(10)->get("{$this->baseUrl}/search", $params);

            if ($response->failed()) {
                return [];
            }

            return $response->json('results', []);
        });
    }

    public function getEpisodes(string $kodikId): array
    {
        $data = $this->searchByKodikId($kodikId);
        
        if (empty($data)) {
            return [];
        }

        $episodes = [];
        $anime = $data[0];
        $seasons = $anime['seasons'] ?? [];

        foreach ($seasons as $seasonNumber => $seasonData) {
            $episodesData = $seasonData['episodes'] ?? [];
            
            foreach ($episodesData as $episodeNumber => $link) {
                $episodes[] = [
                    'episode' => (int) $episodeNumber,
                    'season' => (int) $seasonNumber,
                    'title' => $anime['material_data']['title'] ?? "Episode {$episodeNumber}",
                    'link' => $link,
                    'translation' => [
                        'id' => $anime['translation']['id'] ?? null,
                        'title' => $anime['translation']['title'] ?? 'Unknown',
                        'type' => $anime['translation']['type'] ?? 'voice',
                    ],
                ];
            }
        }

        return $episodes;
    }

    private function searchByKodikId(string $kodikId): array
    {
        $params = [
            'id' => $kodikId,
            'with_episodes' => true,
            'with_material_data' => true,
        ];

        if ($this->apiToken) {
            $params['token'] = $this->apiToken;
        }

        $response = Http::timeout(10)->get("{$this->baseUrl}/search", $params);

        if ($response->failed()) {
            return [];
        }

        return $response->json('results', []);
    }

    public function searchByTitle(string $title): array
    {
        $params = [
            'title' => $title,
            'with_episodes' => true,
            'limit' => 10,
        ];

        if ($this->apiToken) {
            $params['token'] = $this->apiToken;
        }

        $response = Http::timeout(10)->get("{$this->baseUrl}/search", $params);

        if ($response->failed()) {
            return [];
        }

        return $response->json('results', []);
    }
}
