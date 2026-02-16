<?php

namespace App\Jobs;

use App\Services\FavoritesService;
use App\Services\SettingsService;
use App\Services\TraktService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TraktUpdateJob - Periodic job that checks Trakt.tv for updated show information.
 *
 * Ported from DuckieTV Angular TraktTVUpdateService.js (125 lines).
 * In the Angular version, this ran as a setTimeout loop checking every N hours.
 * In Laravel, this runs as a scheduled queued job via the Laravel scheduler.
 *
 * Update logic (ported from TraktTVUpdateService.js update(), lines 16-57):
 * 1. Iterate all favorite series
 * 2. For each, fetch summary-only data from Trakt to check updated_at timestamp
 * 3. Compare Trakt's updated_at with local serie.lastupdated
 * 4. If remote is newer, fetch full serie data (with seasons/episodes) and upsert
 * 5. Track and log number of updated shows
 *
 * Also handles trending cache refresh (updateCachedTrending(), lines 64-81):
 * - Fetches fresh trending data from Trakt API
 * - Strips unnecessary fields to reduce storage
 * - Caches result in settings table as 'trakttv.trending.cache'
 *
 * Scheduling (ported from TraktTVUpdateService.js run block, lines 88-125):
 * - Update check: every 'trakt-update.period' hours (default: 1)
 * - Trending cache: once per day
 * - Timestamps stored in settings: 'trakttv.lastupdated', 'trakttv.lastupdated.trending'
 *
 * @see \App\Services\TraktService
 * @see \App\Services\FavoritesService
 */
class TraktUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     * Show updates can take a while with many favorites.
     */
    public int $timeout = 3600;

    /**
     * Execute the job: update favorite shows and refresh trending cache.
     *
     * Ported from TraktTVUpdateService.js run block (lines 88-125).
     * Checks timestamps to determine if updates are needed:
     * - Show updates: if last updated > 'trakt-update.period' hours ago
     * - Trending cache: if last updated > 24 hours ago
     */
    public function handle(TraktService $trakt, FavoritesService $favorites, SettingsService $settings): void
    {
        $this->checkForShowUpdates($trakt, $favorites, $settings);
        $this->checkForTrendingUpdate($trakt, $settings);
    }

    /**
     * Check if favorite shows need updating and update them.
     *
     * Ported from TraktTVUpdateService.js updateFunc (lines 90-105)
     * and update() (lines 16-57).
     *
     * Logic:
     * 1. Check if enough time has passed since last update (based on trakt-update.period)
     * 2. If yes, iterate all favorites and compare timestamps
     * 3. Fetch full data for any shows that have been updated on Trakt
     */
    private function checkForShowUpdates(TraktService $trakt, FavoritesService $favorites, SettingsService $settings): void
    {
        $nowMs = now()->getTimestampMs();
        $period = (int) $settings->get('trakt-update.period', 1); // hours
        $lastUpdated = (int) $settings->get('trakttv.lastupdated', 0);

        // Check if enough time has passed
        if ($lastUpdated > 0 && ($lastUpdated + ($period * 3600 * 1000)) > $nowMs) {
            Log::info("TraktUpdate: Skipping, already done within the last {$period} hour(s).");
            return;
        }

        $updatedCount = $this->updateFavorites($trakt, $favorites);

        Log::info("TraktUpdate: Completed. {$updatedCount} shows updated.");
        $settings->set('trakttv.lastupdated', $nowMs);
    }

    /**
     * Iterate all favorite shows and update any that have changed on Trakt.
     *
     * Ported from TraktTVUpdateService.js update() (lines 16-57).
     *
     * @return int Number of shows that were updated
     */
    private function updateFavorites(TraktService $trakt, FavoritesService $favorites): int
    {
        $allSeries = $favorites->getSeries();
        $totalSeries = $allSeries->count();
        $updatedCount = 0;

        foreach ($allSeries as $i => $serie) {
            try {
                // Fetch summary only to check updated_at timestamp
                $newSerie = $trakt->serie((string) $serie->trakt_id, null, true);
                $timeUpdated = strtotime($newSerie['updated_at'] ?? '');
                $serieLastUpdated = is_string($serie->lastupdated)
                    ? strtotime($serie->lastupdated)
                    : 0;

                if ($timeUpdated && $serieLastUpdated && $timeUpdated <= $serieLastUpdated) {
                    continue; // Hasn't been updated
                }

                Log::info("[TraktUpdate] [{$i}/{$totalSeries}] Updating: {$serie->name}");

                // Fetch full data with seasons and episodes
                $fullSerie = $trakt->serie((string) $newSerie['trakt_id'], $newSerie);
                $favorites->addFavorite($fullSerie, [], true);
                $updatedCount++;
            } catch (\Throwable $e) {
                Log::error("TraktUpdate: Error updating {$serie->name} [Id={$serie->id}] [Trakt={$serie->trakt_id}]: {$e->getMessage()}");
            }
        }

        return $updatedCount;
    }

    /**
     * Refresh the trending shows cache if it's more than 24 hours old.
     *
     * Ported from TraktTVUpdateService.js updateCachedTrending() (lines 64-81)
     * and the run block trending check (lines 107-118).
     *
     * Strips unnecessary fields from trending data to reduce storage:
     * ids, available_translations, title, tvrage_id, imdb_id, updated_at,
     * aired_episodes, homepage, slug_id
     */
    private function checkForTrendingUpdate(TraktService $trakt, SettingsService $settings): void
    {
        $nowMs = now()->getTimestampMs();
        $lastTrendingUpdate = (int) $settings->get('trakttv.lastupdated.trending', 0);

        // Update trending cache once per day (24 hours)
        if ($lastTrendingUpdate > 0 && ($lastTrendingUpdate + (24 * 3600 * 1000)) > $nowMs) {
            Log::info('TraktUpdate: Skipping trending update, done within last 24 hours.');
            return;
        }

        try {
            $trendingData = $trakt->trending(true);

            // Strip unnecessary fields to reduce storage (ported from lines 67-77)
            $stripped = array_map(function (array $serie) {
                unset(
                    $serie['ids'],
                    $serie['available_translations'],
                    $serie['title'],
                    $serie['tvrage_id'],
                    $serie['imdb_id'],
                    $serie['updated_at'],
                    $serie['aired_episodes'],
                    $serie['homepage'],
                    $serie['slug_id']
                );
                return $serie;
            }, $trendingData);

            $settings->set('trakttv.trending.cache', $stripped);
            $settings->set('trakttv.lastupdated.trending', $nowMs);

            Log::info('TraktUpdate: Trending cache updated.');
        } catch (\Throwable $e) {
            Log::error('TraktUpdate: Failed to update trending cache: ' . $e->getMessage());
        }
    }
}
