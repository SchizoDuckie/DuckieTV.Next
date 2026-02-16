<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Season;
use App\Models\Serie;
use Illuminate\Support\Facades\Log;

/**
 * FavoritesService - Central manager for all user's favorite TV shows.
 *
 * Ported from DuckieTV Angular FavoritesService.js.
 * Handles adding/removing shows from favorites, mapping Trakt API data,
 * and coordinating seasons/episodes upserts.
 */
class FavoritesService
{
    private SettingsService $settings;

    private TMDBService $tmdb;

    /**
     * Whether to process and store ratings from Trakt API responses.
     */
    private ?bool $downloadRatings = null;

    public function __construct(SettingsService $settings, TMDBService $tmdb)
    {
        $this->settings = $settings;
        $this->tmdb = $tmdb;
    }

    /**
     * Lazy accessor for the download.ratings setting.
     */
    private function shouldDownloadRatings(): bool
    {
        if ($this->downloadRatings === null) {
            $this->downloadRatings = (bool) $this->settings->get('download.ratings', true);
        }

        return $this->downloadRatings;
    }

    // ─── Core Operations ─────────────────────────────────────────

    /**
     * Add or update a show as a favorite. Creates/updates the Serie, its Seasons,
     * and all Episodes from Trakt API data.
     *
     * @param  array  $data  Show data from TraktService::serie()
     * @param  array  $watched  Optional watched episode data for backup restore
     * @param  bool  $useTraktId  When true, look up existing serie by trakt_id
     * @param  callable|null  $onProgress  Callback for progress reporting: function($processed, $total, $season)
     * @return Serie The created or updated Serie model
     */
    public function addFavorite(array $data, array $watched = [], bool $useTraktId = false, ?callable $onProgress = null): Serie
    {
        if (($data['title'] ?? null) === null) {
            Log::error('Received null title data from Trakt, removing from favorites.');
            if (isset($data['trakt_id'])) {
                $existing = Serie::where('trakt_id', $data['trakt_id'])->first();
                if ($existing) {
                    $this->remove($existing);
                }
            }
            throw new \RuntimeException('Invalid show data: title is null');
        }

        $serie = $useTraktId
            ? Serie::where('trakt_id', $data['trakt_id'])->first() ?? new Serie
            : Serie::where('tvdb_id', $data['tvdb_id'])->first() ?? new Serie;

        $this->fillSerie($serie, $data);

        // Fetch images from TMDB if we have a tmdb_id and no fanart yet.
        if (($data['tmdb_id'] ?? null) && empty($serie->fanart)) {
            $images = $this->tmdb->getShowImages((int) $data['tmdb_id']);
            $serie->fanart = $images['fanart'] ?? null;
            $serie->poster = $images['poster'] ?? null;
        }

        $serie->save();

        $this->cleanupEpisodes($data['seasons'] ?? [], $serie);
        $seasonCache = $this->updateSeasons($serie, $data['seasons'] ?? []);
        $this->updateEpisodes($serie, $data['seasons'] ?? [], $watched, $seasonCache, $onProgress);

        return $serie->fresh();
    }

    /**
     * Remove a serie and all its seasons and episodes.
     */
    public function remove(Serie $serie): void
    {
        Log::info("Removing serie from favorites: {$serie->name} [ID={$serie->id}]");

        Episode::where('serie_id', $serie->id)->delete();
        Season::where('serie_id', $serie->id)->delete();
        $serie->delete();
    }

    // ─── Data Mapping ────────────────────────────────────────────

    private function fillSerie(Serie $serie, array $data): void
    {
        $serie->trakt_id = $data['trakt_id'] ?? null;
        $serie->tvdb_id = $data['tvdb_id'] ?? null;
        $serie->tmdb_id = $data['tmdb_id'] ?? null;
        $serie->tvrage_id = $data['tvrage_id'] ?? null;
        $serie->imdb_id = $data['imdb_id'] ?? null;
        $serie->name = $data['title'] ?? $data['name'] ?? null;
        $serie->contentrating = $data['certification'] ?? null;
        $serie->overview = $data['overview'] ?? null;
        $serie->network = $data['network'] ?? null;
        $serie->status = $data['status'] ?? null;
        $serie->country = $data['country'] ?? null;
        $serie->language = $data['language'] ?? null;
        $serie->runtime = $data['runtime'] ?? null;

        // Air schedule
        if (isset($data['airs'])) {
            $serie->airs_dayofweek = $data['airs']['day'] ?? null;
            $serie->airs_time = $data['airs']['time'] ?? null;
            $serie->timezone = $data['airs']['timezone'] ?? null;
        }

        // First aired date → millisecond timestamp
        if (isset($data['first_aired']) && $data['first_aired']) {
            $serie->firstaired = (new \DateTime($data['first_aired']))->getTimestamp() * 1000;
        }

        // Ratings (only if download.ratings is enabled)
        if ($this->shouldDownloadRatings() && isset($data['rating'])) {
            if (! $serie->ratingcount || ($serie->ratingcount + 25 > ($data['votes'] ?? 0))) {
                $serie->rating = (int) round(($data['rating'] ?? 0) * 10);
                $serie->ratingcount = $data['votes'] ?? null;
            }
        }

        // Genres → pipe-separated string
        if (isset($data['genres']) && is_array($data['genres'])) {
            $serie->genre = implode('|', $data['genres']);
        }

        // Last updated timestamp
        $serie->lastupdated = $data['updated_at'] ?? null;

        // Cast → pipe-separated "Name (Character)" strings
        if (isset($data['people']['cast']) && is_array($data['people']['cast'])) {
            $serie->actors = collect($data['people']['cast'])->map(function (array $actor) {
                $name = $actor['person']['name'] ?? '';
                $character = $actor['character'] ?? '';

                return $character !== '' ? "{$name} ({$character})" : $name;
            })->implode('|');
        }

        // Set added timestamp if this is a new serie
        if ($serie->added === null) {
            $serie->added = now()->getTimestampMs();
        }
    }

    private function fillEpisode(Episode $episode, array $data, Season $season, Serie $serie, array $watched = []): void
    {
        $episode->tvdb_id = $data['tvdb_id'] ?? null;
        $episode->tmdb_id = $data['tmdb_id'] ?? null;
        $episode->imdb_id = $data['imdb_id'] ?? null;
        $episode->trakt_id = $data['trakt_id'] ?? null;

        // Ratings
        if ($this->shouldDownloadRatings() && isset($data['rating'])) {
            if (! $episode->ratingcount || ($episode->ratingcount + 25 > ($data['votes'] ?? 0))) {
                $episode->rating = (int) round(($data['rating'] ?? 0) * 10);
                $episode->ratingcount = $data['votes'] ?? null;
            }
        }

        $episode->episodenumber = $data['number'] ?? null;
        $episode->episodename = ($data['title'] ?? null) === null ? 'TBA' : $data['title'];
        $episode->overview = $data['overview'] ?? null;

        // First aired → millisecond timestamp
        if (isset($data['first_aired']) && $data['first_aired']) {
            $episode->firstaired = (new \DateTime($data['first_aired']))->getTimestamp() * 1000;
            $episode->firstaired_iso = $data['first_aired'];
        } else {
            $episode->firstaired = 0;
            $episode->firstaired_iso = null;
        }

        // If episode hasn't aired yet and isn't leaked, reset download/watched state
        if (! $episode->isLeaked() && ($episode->firstaired === 0 || $episode->firstaired > now()->getTimestampMs())) {
            $episode->downloaded = 0;
            $episode->watched = 0;
            $episode->watchedAt = null;
        }

        // Absolute episode number for anime
        $episode->absolute = $serie->isAnime() ? ($data['number_abs'] ?? null) : null;

        $episode->seasonnumber = $season->seasonnumber;
        $episode->serie_id = $serie->id;
        $episode->season_id = $season->id;

        // Restore watched state from backup data if available
        foreach ($watched as $el) {
            $matchByTvdb = isset($el['TVDB_ID']) && $el['TVDB_ID'] && $el['TVDB_ID'] == $episode->tvdb_id;
            $matchByTrakt = isset($el['TRAKT_ID']) && $el['TRAKT_ID'] && $el['TRAKT_ID'] == $episode->trakt_id;

            if ($matchByTvdb || $matchByTrakt) {
                $episode->downloaded = 1;
                $episode->watchedAt = $el['watchedAt'] ?? null;
                $episode->watched = ($el['watchedAt'] !== null) ? 1 : 0;
                break;
            }
        }
    }

    // ─── Batch Operations ────────────────────────────────────────

    private function cleanupEpisodes(array $seasons, Serie $serie): int
    {
        $traktIds = [];
        foreach ($seasons as $season) {
            foreach ($season['episodes'] ?? [] as $episode) {
                $traktId = $episode['trakt_id'] ?? null;
                if ($traktId !== null && is_numeric($traktId)) {
                    $traktIds[] = (int) $traktId;
                }
            }
        }

        if (empty($traktIds)) {
            return 0;
        }

        $deleted = Episode::where('serie_id', $serie->id)
            ->whereNotIn('trakt_id', $traktIds)
            ->delete();

        if ($deleted > 0) {
            Log::info("Cleaned up {$deleted} orphaned episodes for series [{$serie->id}] {$serie->name}");
        }

        return $deleted;
    }

    private function updateSeasons(Serie $serie, array $seasons): array
    {
        $seasonCache = $serie->getSeasonsByNumber();

        foreach ($seasons as $seasonData) {
            $number = $seasonData['number'] ?? 0;
            $season = $seasonCache[$number] ?? new Season;

            $season->seasonnumber = $number;
            $season->serie_id = $serie->id;
            $season->overview = $seasonData['overview'] ?? null;
            $season->trakt_id = $seasonData['trakt_id'] ?? null;
            $season->tmdb_id = $seasonData['tmdb_id'] ?? null;

            if ($this->shouldDownloadRatings() && isset($seasonData['rating'])) {
                if (! $season->ratingcount || ($season->ratingcount + 25 > ($seasonData['votes'] ?? 0))) {
                    $season->ratings = (int) round(($seasonData['rating'] ?? 0) * 10);
                    $season->ratingcount = $seasonData['votes'] ?? null;
                }
            }

            $season->save();
            $seasonCache[$number] = $season;
        }

        return $seasonCache;
    }

    private function updateEpisodes(Serie $serie, array $seasons, array $watched, array $seasonCache, ?callable $onProgress = null): array
    {
        $episodeCache = $serie->getEpisodesMap();

        foreach ($seasons as $seasonData) {
            $seasonNumber = $seasonData['number'] ?? 0;
            $season = $seasonCache[$seasonNumber] ?? null;

            if (! $season) {
                continue;
            }

            $episodes = $seasonData['episodes'] ?? [];
            $totalEpisodes = count($episodes);
            $processed = 0;

            foreach ($episodes as $episodeData) {
                $traktId = $episodeData['trakt_id'] ?? null;
                $episode = ($traktId && isset($episodeCache[$traktId]))
                    ? $episodeCache[$traktId]
                    : new Episode;

                $this->fillEpisode($episode, $episodeData, $season, $serie, $watched);
                $episode->save();

                if ($traktId) {
                    $episodeCache[$traktId] = $episode;
                }

                $processed++;
                if ($onProgress) {
                    // Report progress for this season chunk
                    $onProgress($processed, $totalEpisodes, $seasonNumber);
                }
            }
        }

        return $episodeCache;
    }

    // ─── Lookup Methods ──────────────────────────────────────────

    public function getById(int $id): ?Serie
    {
        return Serie::find($id);
    }

    public function getByTvdbId(int $tvdbId): ?Serie
    {
        return Serie::where('tvdb_id', $tvdbId)->first();
    }

    public function getByTraktId(int $traktId): ?Serie
    {
        return Serie::where('trakt_id', $traktId)->first();
    }

    public function hasFavorite(int $traktId): bool
    {
        return Serie::where('trakt_id', $traktId)->exists();
    }

    public function getSeries(): \Illuminate\Database\Eloquent\Collection
    {
        return Serie::whereNotNull('name')->get();
    }

    public function getFavoriteIds(): array
    {
        return Serie::whereNotNull('trakt_id')
            ->pluck('trakt_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function getEpisodesForDateRange(int $start, int $end): \Illuminate\Database\Eloquent\Collection
    {
        return Episode::where('firstaired', '>=', $start)
            ->where('firstaired', '<=', $end)
            ->get();
    }

    public function getRandomBackground(): ?Serie
    {
        return Serie::whereNotNull('fanart')
            ->where('fanart', '!=', '')
            ->inRandomOrder()
            ->first();
    }
}
