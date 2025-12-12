<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\Tag;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnilistImportService
{
    protected string $apiUrl = 'https://graphql.anilist.co';
    protected int $perPage = 50;

    public function importAll(bool $isInitialImport = true): ImportLog
    {
        $importLog = ImportLog::create([
            'import_type' => $isInitialImport ? 'initial' : 'update',
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $currentPage = 1;
            $hasNextPage = true;

            while ($hasNextPage) {
                $result = $this->importPage($currentPage, $isInitialImport, $importLog);
                
                if (!$result['success']) {
                    throw new \Exception($result['error'] ?? 'Unknown error');
                }

                $hasNextPage = $result['hasNextPage'];
                $currentPage++;

                usleep(500000);
            }

            $importLog->update([
                'finished_at' => now(),
                'status' => 'completed',
            ]);

        } catch (\Exception $e) {
            $importLog->update([
                'finished_at' => now(),
                'status' => 'failed',
                'errors' => json_encode(['error' => $e->getMessage()]),
            ]);

            Log::error('Import failed', ['exception' => $e->getMessage()]);
        }

        return $importLog;
    }

    public function importPage(int $page, bool $isInitialImport, ImportLog $importLog): array
    {
        try {
            $response = $this->fetchAnimeFromAnilist($page);

            if (!$response['success']) {
                return $response;
            }

            $mediaList = $response['data']['Page']['media'] ?? [];
            $pageInfo = $response['data']['Page']['pageInfo'] ?? [];

            foreach ($mediaList as $mediaData) {
                $this->processAnime($mediaData, $isInitialImport, $importLog);
            }

            return [
                'success' => true,
                'hasNextPage' => $pageInfo['hasNextPage'] ?? false,
            ];

        } catch (\Exception $e) {
            Log::error('Page import failed', [
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function fetchAnimeFromAnilist(int $page): array
    {
        $query = '
            query ($page: Int!, $perPage: Int!) {
                Page(page: $page, perPage: $perPage) {
                    pageInfo {
                        total
                        currentPage
                        lastPage
                        hasNextPage
                    }
                    media(type: ANIME, sort: ID) {
                        id
                        title {
                            english
                            romaji
                        }
                        description
                        coverImage {
                            large
                            extraLarge
                        }
                        format
                        status
                        episodes
                        startDate {
                            year
                            month
                            day
                        }
                        endDate {
                            year
                            month
                            day
                        }
                        isAdult
                        averageScore
                        popularity
                        favourites
                        genres
                        tags {
                            name
                        }
                    }
                }
            }
        ';

        try {
            $response = Http::timeout(30)->post($this->apiUrl, [
                'query' => $query,
                'variables' => [
                    'page' => $page,
                    'perPage' => $this->perPage,
                ],
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => 'AniList API request failed: ' . $response->status(),
                ];
            }

            $data = $response->json();

            if (isset($data['errors'])) {
                return [
                    'success' => false,
                    'error' => 'AniList API returned errors: ' . json_encode($data['errors']),
                ];
            }

            return [
                'success' => true,
                'data' => $data['data'],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'HTTP request exception: ' . $e->getMessage(),
            ];
        }
    }

    protected function processAnime(array $mediaData, bool $isInitialImport, ImportLog $importLog): void
    {
        $externalId = (string) $mediaData['id'];
        $externalSource = 'anilist';

        $existing = Anime::where('external_source', $externalSource)
            ->where('external_id', $externalId)
            ->first();

        if ($existing && $isInitialImport) {
            $importLog->increment('total_skipped');
            return;
        }

        $animeData = $this->mapAnilistMediaToAnime($mediaData);

        if ($existing) {
            $existing->update($animeData);
            $anime = $existing;
            $importLog->increment('total_updated');
        } else {
            $anime = Anime::create($animeData);
            $importLog->increment('total_created');
        }

        $this->syncTags($anime, $mediaData);

        $importLog->increment('total_processed');
    }

    protected function mapAnilistMediaToAnime(array $mediaData): array
    {
        $title = $mediaData['title']['english'] ?? $mediaData['title']['romaji'] ?? 'Unknown';
        
        $baseSlug = Str::slug($title);
        $slug = $this->generateUniqueSlug($baseSlug, $mediaData['id']);
        
        $rating = null;
        if (isset($mediaData['averageScore']) && $mediaData['averageScore'] > 0) {
            $rating = round($mediaData['averageScore'] / 10, 1);
        }

        $airedFrom = $this->parseAnilistDate($mediaData['startDate'] ?? null);
        $airedTo = $this->parseAnilistDate($mediaData['endDate'] ?? null);

        $year = $airedFrom ? (int) date('Y', strtotime($airedFrom)) : null;

        return [
            'title' => $title,
            'slug' => $slug,
            'description' => $mediaData['description'] ?? null,
            'poster_url' => $mediaData['coverImage']['extraLarge'] ?? $mediaData['coverImage']['large'] ?? null,
            'rating' => $rating,
            'year' => $year,
            'status' => $this->mapStatus($mediaData['status'] ?? null),
            'type' => $this->mapFormat($mediaData['format'] ?? null),
            'number_of_episodes' => $mediaData['episodes'] ?? null,
            'external_id' => (string) $mediaData['id'],
            'external_source' => 'anilist',
            'aired_from' => $airedFrom,
            'aired_to' => $airedTo,
            'nsfw_flag' => $mediaData['isAdult'] ?? false,
            'popularity' => $mediaData['popularity'] ?? null,
            'favorites' => $mediaData['favourites'] ?? null,
            'score_count' => null,
        ];
    }

    protected function generateUniqueSlug(string $baseSlug, string $externalId): string
    {
        $slug = $baseSlug;
        $counter = 2;

        while (Anime::where('slug', $slug)
            ->where('external_id', '!=', $externalId)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function parseAnilistDate(?array $date): ?string
    {
        if (!$date || !isset($date['year'])) {
            return null;
        }

        $year = $date['year'];
        $month = $date['month'] ?? 1;
        $day = $date['day'] ?? 1;

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    protected function mapStatus(?string $status): string
    {
        return match($status) {
            'FINISHED' => 'finished',
            'RELEASING' => 'ongoing',
            'NOT_YET_RELEASED' => 'planned',
            'CANCELLED' => 'paused',
            'HIATUS' => 'paused',
            default => 'planned',
        };
    }

    protected function mapFormat(?string $format): string
    {
        return match($format) {
            'TV' => 'tv',
            'MOVIE' => 'movie',
            'OVA' => 'ova',
            'ONA' => 'ona',
            'SPECIAL' => 'special',
            'MUSIC' => 'music',
            default => 'tv',
        };
    }

    protected function syncTags(Anime $anime, array $mediaData): void
    {
        $tagNames = array_merge(
            $mediaData['genres'] ?? [],
            array_map(fn($tag) => $tag['name'], $mediaData['tags'] ?? [])
        );

        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['name' => $tagName]
            );

            $tagIds[] = $tag->id;
        }

        $anime->tags()->sync($tagIds);
    }
}
