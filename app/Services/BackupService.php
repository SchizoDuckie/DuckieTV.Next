<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * BackupService - Handles backup creation and restoration.
 *
 * Ported/Adapted from DuckieTV Angular BackupService.js.
 */
class BackupService
{
    private SettingsService $settings;

    private FavoritesService $favorites;

    private TraktService $trakt;

    public function __construct(SettingsService $settings, FavoritesService $favorites, TraktService $trakt)
    {
        $this->settings = $settings;
        $this->favorites = $favorites;
        $this->trakt = $trakt;
    }

    /**
     * Restore from a backup data array.
     *
     * @param  array  $data  Parsed JSON backup data
     * @param  callable|null  $onProgress  function($percent, $message|array)
     * @return array Result stats ['series_restored' => int]
     */
    public function restore(array $data, ?callable $onProgress = null): array
    {
        // Enable global throttling to play nice with Trakt
        $this->trakt->setThrottling(true);

        $stats = ['series_restored' => 0];

        if ($onProgress) {
            $onProgress(1, 'Initializing restore...');
        }

        // 1. Restore Settings
        if (isset($data['settings']) && is_array($data['settings'])) {
            if ($onProgress) {
                $onProgress(5, 'Restoring application settings...');
            }

            $this->settings->restoreSettings($data['settings']);

            if ($onProgress) {
                $onProgress(10, 'Settings restored.');
            }
        }

        // 2. Restore Series
        if (isset($data['series']) && is_array($data['series'])) {
            if ($onProgress) {
                $onProgress(10, 'Starting series restoration...');
            }
            $stats['series_restored'] = $this->restoreSeries($data['series'], $onProgress);
        }

        if ($onProgress) {
            $onProgress(100, 'Restore complete!');
        }

        return $stats;
    }

    /**
     * Restore a single show from backup data.
     *
     * IMPORTANT: The Trakt API call is performed OUTSIDE any database transaction
     * to avoid holding SQLite write locks during network I/O. With SQLite's
     * single-writer model, a long-held transaction blocks all other writers
     * (including the queue worker trying to reserve/complete jobs).
     *
     * The actual database writes use short, targeted transactions per operation
     * (each model save() is its own implicit transaction). This keeps lock
     * durations minimal and prevents "database is locked" errors.
     *
     * @param  string  $id  Series ID (Trakt/TVDB)
     * @param  array  $backupData  The array of watched episodes + custom settings
     * @return bool Success
     */
    public function restoreShow(string $id, array $backupData, ?callable $onProgress = null): bool
    {
        try {
            if ($onProgress) {
                $onProgress(0, "Fetching data for series ID: {$id}...");
            }

            // 1. Fetch Trakt Data OUTSIDE any transaction
            //    This is a network call that can take seconds - we must NOT hold
            //    a database lock while waiting for the network response.
            $traktData = $this->trakt->serie((string) $id);
            $name = $traktData['title'] ?? "Series #{$id}";

            if ($onProgress) {
                $onProgress(10, "Restoring: {$name}...");
            }

            $customSettings = $backupData[0] ?? [];
            $watchedData = $backupData;

            // 2. Write to database - each save() is its own implicit transaction.
            //    addFavorite() calls serie->save(), season->save(), episode->save()
            //    individually, which keeps each write lock very short.
            $serie = $this->favorites->addFavorite($traktData, $watchedData, false, function ($processed, $totalEpisodes, $season) use ($onProgress, $name) {
                if ($onProgress) {
                    $percent = $totalEpisodes > 0 ? round(($processed / $totalEpisodes) * 90) : 0;
                    $onProgress($percent, [
                        'type' => 'show_progress',
                        'show' => $name,
                        'processed' => $processed,
                        'total' => $totalEpisodes,
                        'season' => $season,
                        'message' => "Restoring {$name} - Season {$season}: {$processed}/{$totalEpisodes}",
                    ]);
                }
            });

            // 3. Apply Custom Settings (another quick save)
            if (! empty($customSettings) && ! isset($customSettings['TVDB_ID'])) {
                $this->applyCustomSettings($serie, $customSettings);
            }

            if ($onProgress) {
                $onProgress(100, [
                    'type' => 'show_completed',
                    'show' => $name,
                    'poster' => $serie->poster,
                    'message' => "Restored: {$name}",
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("BackupService: Failed to restore series [{$id}]: ".$e->getMessage());
            if ($onProgress) {
                $onProgress(0, "ERROR: Failed to restore series ID {$id}: ".$e->getMessage());
            }
            throw $e; // Re-throw to fail the job
        }
    }

    /**
     * Iterate through series in backup and restore them.
     * DEPRECATED: Use restoreShow via Jobs instead for large backups.
     * Keeping for synchronous fallback if needed.
     */
    private function restoreSeries(array $seriesMap, ?callable $onProgress = null): int
    {
        $count = 0;
        $total = count($seriesMap);
        $current = 0;

        foreach ($seriesMap as $id => $backupData) {
            $current++;
            $progress = 10 + (int) (($current / $total) * 85);
            try {
                $this->restoreShow((string) $id, $backupData, function ($p, $msg) use ($onProgress, $progress) {
                    // Adapt single-show progress to global progress check if needed
                    // For now we just pass the main message up
                    if ($onProgress && is_string($msg)) {
                        $onProgress($progress, $msg);
                    }
                });
                $count++;
            } catch (\Exception $e) {
                // Continue with next
            }
        }

        return $count;
    }

    private function applyCustomSettings(\App\Models\Serie $serie, array $settings): void
    {
        // Backup keys map 1:1 to camelCase column names in the series table.
        // 'displaymode' is not a column — it was a UI-only setting in Angular DuckieTV.
        $map = [
            'autoDownload' => 'autoDownload',
            'customSearchString' => 'customSearchString',
            'ignoreGlobalQuality' => 'ignoreGlobalQuality',
        ];

        $dirty = false;
        foreach ($map as $backupKey => $modelKey) {
            if (isset($settings[$backupKey])) {
                $serie->$modelKey = $settings[$backupKey];
                $dirty = true;
            }
        }

        if ($dirty) {
            $serie->save();
        }
    }
}
