<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\Serie;
use App\Services\FavoritesService;
use App\Services\SettingsService;
use App\Services\TorrentSearchService;
use App\Services\TorrentClients\TorrentClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AutoDownloadJob - Scheduled job that checks for aired episodes and auto-downloads torrents.
 *
 * Ported from DuckieTV Angular AutoDownloadService.js (418 lines).
 * In the Angular version, this ran as a setTimeout loop every 15 minutes.
 * In Laravel, this runs as a scheduled queued job via the Laravel scheduler.
 *
 * NOTE: This is a skeleton/stub implementation for Phase 2.
 * Full torrent search and download functionality depends on Phase 3 (Torrent Layer):
 * - TorrentSearchService (search engine registry)
 * - GenericSearchEngine (HTML scraping search)
 * - Torrent client implementations (uTorrent, qBittorrent, etc.)
 *
 * Current Phase 2 scope:
 * - Episode candidate selection logic (fully ported)
 * - Filter logic structure (fully ported)
 * - Activity logging structure (fully ported)
 * - Actual torrent search/download calls are stubbed with TODO markers
 *
 * Candidate filtering (ported from AutoDownloadService.js autoDownloadCheck(), lines 60-157):
 * 1. Get episodes aired within the configured period
 * 2. Skip specials if calendar.show-specials is false (unless serie overrides)
 * 3. Skip episodes not shown on calendar (displaycalendar = false)
 * 4. Skip already downloaded episodes
 * 5. Skip already watched episodes
 * 6. Skip episodes with existing magnet hash
 * 7. Skip episodes still on-air (firstaired + runtime + delay > now)
 * 8. Skip series without TVDB_ID
 * 9. Skip series with autoDownload disabled
 *
 * Download filtering (ported from AutoDownloadService.js autoDownload(), lines 159-379):
 * - filterByScore: All search words must appear in release name
 * - filterRequireKeywords: At least one require keyword must match (OR mode)
 * - filterIgnoreKeywords: No ignore keywords may match
 * - filterBySize: Torrent size must be within min/max range
 * - Minimum seeders check
 *
 * Activity status codes (ported from AutoDownloadService.js):
 * - 0: Already downloaded
 * - 1: Already watched
 * - 2: Has magnet hash (in progress)
 * - 3: Auto-download disabled (or hidden from calendar/specials)
 * - 4: No search results found
 * - 5: Filtered out (RK=require keywords, IK=ignore keywords, MS=min/max size)
 * - 6: Torrent launched successfully
 * - 7: Not enough seeders
 * - 8: Episode still on-air + delay not elapsed
 * - 9: Missing TVDB_ID
 *
 * @see \App\Services\SettingsService
 * @see \App\Services\FavoritesService
 */
class AutoDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 1800;

    /**
     * Activity log for this run. Each entry records the search/filter result
     * for an episode candidate.
     *
     * Structure per entry (ported from AutoDownloadService.js activityUpdate()):
     * [
     *     'search'         => string,  // search query used
     *     'status'         => int,     // status code (0-9, see class docblock)
     *     'extra'          => string,  // additional status detail
     *     'serie_name'     => string,  // serie name
     *     'episode_format' => string,  // formatted episode (s01e05)
     * ]
     *
     * @var array<array>
     */
    private array $activityList = [];

    /**
     * Execute the auto-download check.
     */
    public function handle(
        SettingsService $settings,
        FavoritesService $favorites,
        ?TorrentSearchService $searchService = null,
        ?TorrentClientInterface $torrentClient = null
    ): void
    {
        if (!$settings->get('torrenting.enabled') || !$settings->get('torrenting.autodownload')) {
            Log::info('AutoDownload: Disabled in settings, skipping.');
            return;
        }

        $period = (int) $settings->get('autodownload.period', 1);
        $settingsDelay = (int) $settings->get('autodownload.delay', 15);
        $showSpecials = (bool) $settings->get('calendar.show-specials', true);
        $nowMs = now()->getTimestampMs();

        // Calculate date range: from (lastrun - period days) to now
        $from = now()->subDays($period)->startOfDay();
        $fromMs = $from->getTimestampMs();

        $candidates = $favorites->getEpisodesForDateRange($fromMs, $nowMs);

        Log::info("AutoDownload: Checking {$candidates->count()} episode candidates.");

        foreach ($candidates as $episode) {
            $this->processCandidate($episode, $settings, $searchService, $torrentClient, $settingsDelay, $showSpecials, $nowMs);
        }

        $settings->set('autodownload.lastrun', $nowMs);

        Log::info('AutoDownload: Check completed. ' . count($this->activityList) . ' episodes processed.');
    }

    /**
     * Process a single episode candidate through all filters.
     *
     * Ported from AutoDownloadService.js autoDownloadCheck() inner loop (lines 88-156).
     * Each filter check logs an activity entry explaining why the episode was skipped or processed.
     *
     * @param Episode         $episode       The candidate episode
     * @param SettingsService $settings      Settings service for global configuration
     * @param int             $settingsDelay Delay in minutes after airing before download attempt
     * @param bool            $showSpecials  Whether specials should be auto-downloaded
     * @param int             $nowMs         Current timestamp in milliseconds
     */
    private function processCandidate(
        Episode $episode,
        SettingsService $settings,
        ?TorrentSearchService $searchService,
        ?TorrentClientInterface $torrentClient,
        int $settingsDelay,
        bool $showSpecials,
        int $nowMs
    ): void {
        $serie = $episode->serie;
        if (!$serie) {
            return;
        }

        $serieEpisode = $serie->name . ' ' . $episode->getFormattedEpisode();

        // Filter: specials hidden from calendar
        if ($episode->seasonnumber === 0 && !$showSpecials && !$serie->ignoreHideSpecials) {
            $this->logActivity($serieEpisode, 3, ' HS');
            return;
        }

        // Filter: serie hidden from calendar
        if (!$serie->displaycalendar) {
            $this->logActivity($serieEpisode, 3, ' HC');
            return;
        }

        // Filter: already downloaded
        if ($episode->isDownloaded()) {
            $this->logActivity($serieEpisode, 0);
            return;
        }

        // Filter: already watched
        if ($episode->watchedAt !== null) {
            $this->logActivity($serieEpisode, 1);
            return;
        }

        // Filter: already has magnet hash (download in progress)
        if ($episode->magnetHash !== null) {
            $this->logActivity($serieEpisode, 2);
            return;
        }

        // Filter: episode still on-air (firstaired + runtime + delay > now)
        $delay = $serie->customDelay ? (int) $serie->customDelay : $settingsDelay;
        $delay = min($delay, (int) $settings->get('autodownload.period', 1) * 24 * 60); // sanity check
        $runtime = $serie->runtime ? (int) $serie->runtime : 60;

        if ($episode->firstaired && $episode->firstaired > 0) {
            $episodeAiredMs = $episode->firstaired + (($runtime + $delay) * 60 * 1000);
            if ($episodeAiredMs > $nowMs) {
                $minutesToGo = ($episodeAiredMs - $nowMs) / 1000 / 60;
                $this->logActivity($serieEpisode, 8, ' ' . $this->formatDhm($minutesToGo));
                return;
            }
        }

        // Filter: missing TVDB_ID
        if (!$serie->tvdb_id) {
            $this->logActivity($serieEpisode, 9);
            return;
        }

        // Filter: auto-download disabled for this serie
        if (!$serie->autoDownload) {
            $this->logActivity($serieEpisode, 3);
            return;
        }

        // Ready to auto-download
        $this->autoDownload($serie, $episode, $settings, $searchService, $torrentClient);
    }

    /**
     * Attempt to auto-download an episode.
     *
     * @param Serie                  $serie         The serie to download for
     * @param Episode                $episode       The episode to download
     * @param SettingsService        $settings      Settings service
     * @param TorrentSearchService   $searchService Torrent search registry
     * @param TorrentClientInterface $torrentClient Active torrent client
     */
    private function autoDownload(
        Serie $serie, 
        Episode $episode, 
        SettingsService $settings,
        TorrentSearchService $searchService,
        TorrentClientInterface $torrentClient
    ): void {
        // Build search parameters
        $hasCustomSeeders = ($serie->customSeeders !== null);
        $hasCustomIncludes = ($serie->customIncludes !== null);
        $hasCustomExcludes = ($serie->customExcludes !== null);

        $minSeeders = $hasCustomSeeders ? $serie->customSeeders : $settings->get('torrenting.min_seeders', 50);
        $preferredQuality = $serie->ignoreGlobalQuality ? '' : $settings->get('torrenting.searchquality', '');

        // Ignore keywords: custom + global
        $ignoreKeywords = explode(' ', strtolower($this->buildIgnoreKeywords($serie, $settings, $hasCustomExcludes)));
        $ignoreKeywords = array_filter(array_map('trim', $ignoreKeywords));

        // Require keywords: custom + global
        $requireKeywordsStr = $this->buildRequireKeywords($serie, $settings, $hasCustomIncludes);
        $requireKeywords = array_filter(array_map('trim', explode(' ', strtolower($requireKeywordsStr))));

        $globalSizeMin = $settings->get('torrenting.global_size_min', 0);
        $globalSizeMax = $settings->get('torrenting.global_size_max', 5000 * 1024 * 1024); // 5GB default
        $requireKeywordsModeOR = (bool) $settings->get('torrenting.require_keywords_mode_or', true);

        // Build search query
        $searchString = $serie->name . ' ' . $episode->getFormattedEpisode();
        $requireKeywordsQuery = $requireKeywordsModeOR ? '' : $requireKeywordsStr;
        $query = trim(implode(' ', array_filter([$searchString, $preferredQuality, $requireKeywordsQuery])));

        // Execute search
        $searchEngine = $serie->searchProvider 
            ? $searchService->getSearchEngine($serie->searchProvider) 
            : $searchService->getDefaultEngine();

        if (!$searchEngine) {
            $this->logActivity($query, 4, ' Engine not found');
            return;
        }

        try {
            $results = $searchEngine->search($query, 'seeders.d');
        } catch (\Exception $e) {
            Log::error("AutoDownload Search Error: " . $e->getMessage());
            $this->logActivity($query, 4, ' Search failed');
            return;
        }

        if (empty($results)) {
            $this->logActivity($query, 4);
            return;
        }

        // Processing results
        foreach ($results as $result) {
            $releaseName = strtolower($result['releasename']);

            // 1. Minimum seeders check
            if (($result['seeders'] ?? 0) < $minSeeders) {
                continue; // Too few seeders
            }

            // 2. Score check (All search words must appear in release name)
            $searchWords = array_filter(explode(' ', strtolower($searchString)));
            foreach ($searchWords as $word) {
                if (strpos($releaseName, $word) === false) {
                    continue 2;
                }
            }

            // 3. Ignore keywords check
            foreach ($ignoreKeywords as $word) {
                if (strpos($releaseName, $word) !== false) {
                    continue 2;
                }
            }

            // 4. Require keywords check
            if (!empty($requireKeywords)) {
                $foundCount = 0;
                foreach ($requireKeywords as $word) {
                    if (strpos($releaseName, $word) !== false) {
                        $foundCount++;
                    }
                }
                if ($requireKeywordsModeOR && $foundCount === 0) {
                    continue;
                }
                if (!$requireKeywordsModeOR && $foundCount < count($requireKeywords)) {
                    continue;
                }
            }

            // 5. Size check
            $size = (float) ($result['size_bytes'] ?? 0);
            if ($size > 0 && ($size < $globalSizeMin || $size > $globalSizeMax)) {
                continue;
            }

            // Found a winner!
            try {
                // If we only have a detailUrl, we need to fetch details first
                if (empty($result['magnetUrl']) && !empty($result['detailUrl'])) {
                    $details = $searchEngine->getDetails($result['detailUrl'], $result['releasename']);
                    $result['magnetUrl'] = $details['magnetUrl'] ?? null;
                }

                if (empty($result['magnetUrl'])) {
                    continue; // Skip if no magnet found even after details
                }

                $dlPath = $settings->get('torrenting.directory');
                if ($torrentClient->addMagnet($result['magnetUrl'], $dlPath, 'DuckieTV')) {
                    $episode->magnetHash = $this->extractHash($result['magnetUrl']);
                    // Use a manual save to avoid triggering events if necessary, 
                    // though standard save is fine for Phase 3.
                    $episode->save();
                    
                    $this->logActivity($query, 6, ' ' . $result['releasename']);
                    return; // Done with this episode
                }
            } catch (\Exception $e) {
                Log::error("AutoDownload launch error: " . $e->getMessage());
            }
        }

        $this->logActivity($query, 5); // Filtered out or no valid magnets
    }

    /**
     * Extract infohash from a magnet link.
     */
    private function extractHash(string $magnet): ?string
    {
        if (preg_match('/btih:([a-f0-9]{40})/i', $magnet, $matches)) {
            return strtoupper($matches[1]);
        }
        if (preg_match('/btih:([a-z2-7]{32})/i', $magnet, $matches)) {
            // base32 to hex conversion could be added here if needed
            return strtoupper($matches[1]);
        }
        return null;
    }

    // ─── Filter Methods (documented for Phase 3) ─────────────────

    /**
     * Build the ignore keywords string from serie custom + global settings.
     * Ported from AutoDownloadService.js lines 170-172.
     */
    private function buildIgnoreKeywords(Serie $serie, SettingsService $settings, bool $hasCustomExcludes): string
    {
        if ($serie->ignoreGlobalExcludes) {
            return $hasCustomExcludes ? $serie->customExcludes : '';
        }

        $global = $settings->get('torrenting.ignore_keywords', '');
        return $hasCustomExcludes
            ? trim($serie->customExcludes . ' ' . $global)
            : $global;
    }

    /**
     * Build the require keywords string from serie custom + global settings.
     * Ported from AutoDownloadService.js lines 175-177.
     */
    private function buildRequireKeywords(Serie $serie, SettingsService $settings, bool $hasCustomIncludes): string
    {
        if ($serie->ignoreGlobalIncludes) {
            return $hasCustomIncludes ? $serie->customIncludes : '';
        }

        $global = $settings->get('torrenting.require_keywords', '');
        return $hasCustomIncludes
            ? trim($serie->customIncludes . ' ' . $global)
            : $global;
    }

    /**
     * Log an activity entry for the current auto-download run.
     *
     * @param string $search Search query or episode identifier
     * @param int    $status Status code (0-9)
     * @param string $extra  Additional detail
     */
    private function logActivity(string $search, int $status, string $extra = ''): void
    {
        $this->activityList[] = [
            'search' => $search,
            'status' => $status,
            'extra' => $extra,
        ];
    }

    /**
     * Format minutes into a "Xd Xh Xm" duration string.
     * Ported from JavaScript Number.prototype.minsToDhm().
     *
     * @param float $totalMinutes Total minutes
     * @return string Formatted duration string
     */
    private function formatDhm(float $totalMinutes): string
    {
        $days = floor($totalMinutes / (24 * 60));
        $hours = floor(($totalMinutes % (24 * 60)) / 60);
        $minutes = floor($totalMinutes % 60);

        $result = '';
        if ($days > 0) {
            $result .= "{$days}d ";
        }
        $result .= sprintf('%dh %dm', $hours, $minutes);

        return trim($result);
    }
}
