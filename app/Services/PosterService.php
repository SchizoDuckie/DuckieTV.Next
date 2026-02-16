<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PosterService
{
    private const TMDB_API_URL = 'https://api.themoviedb.org/3';

    private const TMDB_API_KEY = '79d916a2d2e91ff2714649d63f3a5cc5';

    private const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/w500';

    /**
     * Enrich a list of series with posters from TMDB.
     * Uses concurrent requests for efficiency.
     */
    public function enrich(array $series): array
    {
        if (empty($series)) {
            return [];
        }

        // Filter out shows that already have posters or don't have a tmdb_id
        $toFetch = array_filter($series, function ($show) {
            return empty($show['poster']) && ! empty($show['tmdb_id']);
        });

        if (empty($toFetch)) {
            return $series;
        }

        // Use Http::pool for concurrent requests
        $responses = Http::pool(fn ($pool) => array_map(
            fn ($show) => $pool->as((string) $show['tmdb_id'])->get(self::TMDB_API_URL."/tv/{$show['tmdb_id']}", [
                'api_key' => self::TMDB_API_KEY,
                'language' => 'en-US',
            ]),
            $toFetch
        ));

        // Map results back to the series array
        foreach ($series as &$show) {
            $tmdbId = (string) ($show['tmdb_id'] ?? '');
            if ($tmdbId && isset($responses[$tmdbId]) && $responses[$tmdbId]->successful()) {
                $data = $responses[$tmdbId]->json();
                if (! empty($data['poster_path'])) {
                    $show['poster'] = self::TMDB_IMAGE_BASE.$data['poster_path'];
                }
            }
        }

        return $series;
    }

    /**
     * Cache results for a given query or key.
     */
    public function cacheResults(string $key, array $results, int $ttl = 86400): void
    {
        Cache::put("search_posters:{$key}", $results, $ttl);
    }

    /**
     * Get cached results.
     */
    public function getCached(string $key): ?array
    {
        return Cache::get("search_posters:{$key}");
    }
}
