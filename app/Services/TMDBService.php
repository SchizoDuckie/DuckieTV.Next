<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TMDBService - Fetches show/season images from TheMovieDB API.
 *
 * Ported from DuckieTV Angular TMDBService.js + FanartService.js.
 * The original used TMDB API v3 to fetch poster and fanart (backdrop) images
 * since Trakt.tv removed image support from their API.
 *
 * Image sizes used (matching original):
 * - Poster: w500
 * - Fanart/backdrop: original
 *
 * @see https://developer.themoviedb.org/docs
 */
class TMDBService
{
    private const API_URL = 'https://api.themoviedb.org/3';

    private const API_KEY = '79d916a2d2e91ff2714649d63f3a5cc5';

    private const IMAGE_BASE = 'https://image.tmdb.org/t/p/';

    /**
     * Get poster and fanart URLs for a TV show.
     *
     * Ported from FanartService.js updateTmdbImagesForShow() and
     * TMDBService.js getShow().
     *
     * @param  int  $tmdbId  The TMDB show ID
     * @return array{poster: string|null, fanart: string|null}
     */
    public function getShowImages(int $tmdbId): array
    {
        try {
            $response = Http::timeout(15)->get(self::API_URL."/tv/{$tmdbId}", [
                'api_key' => self::API_KEY,
                'language' => 'en-US',
            ]);

            if (! $response->successful()) {
                Log::warning("TMDB: Failed to fetch images for show {$tmdbId}: HTTP {$response->status()}");

                return ['poster' => null, 'fanart' => null];
            }

            $data = $response->json();

            return [
                'poster' => $this->getImageUrl($data['poster_path'] ?? null, 'w500'),
                'fanart' => $this->getImageUrl($data['backdrop_path'] ?? null, 'original'),
            ];
        } catch (\Exception $e) {
            Log::warning("TMDB: Exception fetching images for show {$tmdbId}: {$e->getMessage()}");

            return ['poster' => null, 'fanart' => null];
        }
    }

    /**
     * Build a full TMDB image URL from a path and size.
     *
     * Ported from TMDBService.js getImageUrl().
     *
     * @param  string|null  $path  Image path from TMDB API (e.g. "/zzWGRw277MNoCs3zhyGiGmTWFZHm.jpg")
     * @param  string  $size  Image size (w500, original, etc.)
     * @return string|null Full image URL or null if path is empty
     */
    public function getImageUrl(?string $path, string $size = 'w500'): ?string
    {
        if (! $path) {
            return null;
        }

        return self::IMAGE_BASE.$size.$path;
    }
}
