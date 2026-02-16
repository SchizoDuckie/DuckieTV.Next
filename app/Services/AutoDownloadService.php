<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoDownloadService
{
    protected SettingsService $settings;
    protected FavoritesService $favorites;
    protected TorrentSearchService $searchService;
    protected SceneNameResolverService $sceneNameResolver;
    protected TorrentClientService $torrentClientService;
    protected array $remoteTorrents = [];

    // Status codes matching original DuckieTV AutoDownloadService.js
    public const STATUS_DOWNLOADED = 0;
    public const STATUS_WATCHED = 1;
    public const STATUS_HAS_MAGNET = 2; // already in client
    public const STATUS_AUTODL_DISABLED = 3;
    public const STATUS_NOTHING_FOUND = 4;
    public const STATUS_FILTERED_OUT = 5;
    public const STATUS_TORRENT_LAUNCHED = 6;
    public const STATUS_NOT_ENOUGH_SEEDERS = 7;
    public const STATUS_ON_AIR_DELAY = 8;
    public const STATUS_TVDB_ID_MISSING = 9;

    public function __construct(
        SettingsService $settings,
        FavoritesService $favorites,
        TorrentSearchService $searchService,
        SceneNameResolverService $sceneNameResolver,
        TorrentClientService $torrentClientService
    ) {
        $this->settings = $settings;
        $this->favorites = $favorites;
        $this->searchService = $searchService;
        $this->sceneNameResolver = $sceneNameResolver;
        $this->torrentClientService = $torrentClientService;
    }

    /**
     * Get the activity list for the last run.
     * In DuckieTV.Next, we'll store this in Cache for accessibility.
     */
    public function getActivityList(): array
    {
        return Cache::get('autodownload.activity_list', []);
    }

    public function isEnabled(): bool
    {
        return $this->settings->get('torrenting.autodownload', false);
    }

    public function getLastRun(): ?Carbon
    {
        $lastRun = $this->settings->get('autodownload.lastrun');
        return $lastRun ? Carbon::createFromTimestampMs($lastRun) : null;
    }

    protected function logActivity(Serie $serie, Episode $episode, string $search, int $status, string $extra = ''): void
    {
        $searchExtra = '';
        $csm = 0;
        if ($serie->custom_search_size_min !== null || $serie->custom_search_size_max !== null) {
            $csm = 1;
            $min = $serie->custom_search_size_min ?? '-';
            $max = $serie->custom_search_size_max ?? '-';
            $searchExtra = " ($min/$max)";
        }

        if ($serie->custom_seeders !== null) {
            $searchExtra .= " [{$serie->custom_seeders}]";
        }
        if ($serie->custom_includes !== null) {
            $searchExtra .= " {{$serie->custom_includes}}";
        }
        if ($serie->custom_excludes !== null) {
            $searchExtra .= " <{$serie->custom_excludes}>";
        }

        $activity = [
            'search' => $search,
            'searchProvider' => $serie->search_provider ? " ({$serie->search_provider})" : '',
            'searchExtra' => $searchExtra,
            'status' => $status,
            'extra' => $extra,
            'serie_name' => $serie->name,
            'episode_formatted' => $episode->getFormattedEpisode(),
            'timestamp' => now()->timestamp,
        ];

        $list = $this->getActivityList();
        $list[] = $activity;
        
        // Keep only last 100 activities
        if (count($list) > 100) {
            array_shift($list);
        }

        Cache::put('autodownload.activity_list', $list, now()->addHours(24));
    }

    /**
     * Main check loop.
     */
    public function check(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        // Reset list for new run
        Cache::put('autodownload.activity_list', [], now()->addHours(24));

        $client = $this->torrentClientService->getActiveClient();
        if ($client && $client->isConnected()) {
            foreach ($client->getTorrents() as $torrent) {
                $this->remoteTorrents[strtolower($torrent->infoHash)] = $torrent;
            }
        }

        $periodDays = (int) $this->settings->get('autodownload.period', 1);
        $from = $this->getLastRun() ?? now()->subDays($periodDays);
        $to = now();

        // Get episodes that have aired since period
        $episodes = Episode::whereBetween('firstaired', [$from->timestamp * 1000, $to->timestamp * 1000])
            ->with('serie')
            ->get();

        foreach ($episodes as $episode) {
            $this->processEpisode($episode);
        }

        $this->settings->set('autodownload.lastrun', now()->getTimestampMs());
    }

    protected function processEpisode(Episode $episode): void
    {
        $serie = $episode->serie;
        if (!$serie) return;

        $searchString = $this->sceneNameResolver->getSearchStringForEpisode($serie, $episode);

        // Parity checks from AutoDownloadService.js lines 94-113
        if ($episode->seasonnumber === 0 && !$this->settings->get('calendar.show-specials') && $serie->ignore_hide_specials !== 1) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_AUTODL_DISABLED, ' HS');
            return;
        }

        if ($serie->displaycalendar !== 1) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_AUTODL_DISABLED, ' HC');
            return;
        }

        if ($episode->isDownloaded()) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_DOWNLOADED);
            return;
        }

        if ($episode->watchedAt !== null) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_WATCHED);
            return;
        }

        if (!empty($episode->magnetHash) && isset($this->remoteTorrents[strtolower($episode->magnetHash)])) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_HAS_MAGNET);
            return;
        }

        if ($serie->autoLimitByTorrent && !empty($episode->magnetHash)) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_HAS_MAGNET);
            return;
        }

        if ($serie->auto_download === 0) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_AUTODL_DISABLED);
            return;
        }

        if (!$serie->tvdb_id && !$serie->TVDB_ID) {
            $this->logActivity($serie, $episode, $searchString, self::STATUS_TVDB_ID_MISSING);
            return;
        }

        // Delay logic
        $settingsDelay = (int) $this->settings->get('autodownload.delay', 15);
        $delay = $serie->custom_delay ?? $settingsDelay;
        $runtime = $serie->runtime ?? 60;
        
        $airedAt = Carbon::createFromTimestampMs($episode->firstaired);
        $safeToDownload = $airedAt->copy()->addMinutes($runtime + $delay);

        if ($safeToDownload->isFuture()) {
            $diff = $safeToDownload->diffForHumans(['parts' => 2]);
            $this->logActivity($serie, $episode, $searchString, self::STATUS_ON_AIR_DELAY, " $diff");
            return;
        }

        // If we got here, perform search
        $this->performSearchAndDownload($serie, $episode, $searchString);
    }

    protected function performSearchAndDownload(Serie $serie, Episode $episode, string $searchString): void
    {
        $hasCustomSeeders = $serie->custom_seeders !== null;
        $hasCustomIncludes = $serie->custom_includes !== null;
        $hasCustomExcludes = $serie->custom_excludes !== null;

        $minSeeders = $hasCustomSeeders ? $serie->custom_seeders : (int) $this->settings->get('torrenting.min_seeders', 50);
        $preferredQuality = $serie->ignore_global_quality ? '' : $this->settings->get('torrenting.searchquality', '');
        
        $globalExcludes = $this->settings->get('torrenting.ignore_keywords', '');
        $ignoreKeywords = $hasCustomExcludes ? $serie->custom_excludes . ' ' . $globalExcludes : $globalExcludes;
        if ($serie->ignore_global_excludes) {
            $ignoreKeywords = $hasCustomExcludes ? $serie->custom_excludes : '';
        }

        $globalIncludes = $this->settings->get('torrenting.require_keywords', '');
        $requireKeywords = $hasCustomIncludes ? $serie->custom_includes . ' ' . $globalIncludes : $globalIncludes;
        if ($serie->ignore_global_includes) {
            $requireKeywords = $hasCustomIncludes ? $serie->custom_includes : '';
        }

        $globalSizeMin = $this->settings->get('torrenting.global_size_min', 0);
        $globalSizeMax = $this->settings->get('torrenting.global_size_max', 10000); // effectively unlimited if null
        
        $requireKeywordsModeOR = $this->settings->get('torrenting.require_keywords_mode_or', true);
        $requireKeywordsString = $requireKeywordsModeOR ? '' : $requireKeywords;

        $q = trim("{$searchString} {$preferredQuality} {$requireKeywordsString}");

        $results = $this->searchService->search($q, $serie->search_provider);

        if (empty($results)) {
            $this->logActivity($serie, $episode, $q, self::STATUS_NOTHING_FOUND);
            return;
        }

        // Sort by seeders descending (parity)
        usort($results, fn($a, $b) => ($b['seeders'] ?? 0) <=> ($a['seeders'] ?? 0));

        foreach ($results as $item) {
            $name = $item['releasename'] ?? ($item['title'] ?? '');
            
            if (!$this->filterByScore($name, $q)) {
                continue;
            }

            if (!$this->filterKeywords($name, $requireKeywords, $ignoreKeywords, $requireKeywordsModeOR, $q)) {
                $this->logActivity($serie, $episode, $q, self::STATUS_FILTERED_OUT, ' K');
                continue;
            }

            if (!$this->filterBySize($item['size'] ?? null, $serie, $globalSizeMin, $globalSizeMax)) {
                $this->logActivity($serie, $episode, $q, self::STATUS_FILTERED_OUT, ' S');
                continue;
            }

            $seeders = (int) ($item['seeders'] ?? 0);
            if ($seeders < $minSeeders) {
                $this->logActivity($serie, $episode, $q, self::STATUS_NOT_ENOUGH_SEEDERS, " $seeders < $minSeeders");
                continue;
            }

            // If we found a match!
            $this->download($serie, $episode, $item, $q);
            return;
        }

        $this->logActivity($serie, $episode, $q, self::STATUS_NOTHING_FOUND, ' (All results filtered)');
    }

    protected function filterByScore(string $name, string $query): bool
    {
        $queryParts = explode(' ', strtolower($query));
        $lowerName = strtolower($name);
        
        foreach ($queryParts as $part) {
            if (empty(trim($part))) continue;
            if (str_contains($lowerName, $part)) {
                continue;
            }
            return false;
        }
        return true;
    }

    protected function filterKeywords(string $name, string $require, string $ignore, bool $orMode, string $q): bool
    {
        $lowerName = strtolower($name);
        $lowerQuery = strtolower($q);

        if ($require !== '') {
            $requiredParts = collect(explode(' ', strtolower($require)))->filter();
            $matches = $requiredParts->filter(fn($part) => str_contains($lowerName, $part))->count();

            if ($orMode && $matches === 0) return false;
            if (!$orMode && $matches < $requiredParts->count()) return false;
        }

        if ($ignore !== '') {
            $hasIgnoredKeyword = collect(explode(' ', strtolower($ignore)))
                ->filter()
                ->reject(fn($part) => str_contains($lowerQuery, $part)) // Prevent exclude list from overriding primary search string
                ->contains(fn($part) => str_contains($lowerName, $part));

            if ($hasIgnoredKeyword) return false;
        }

        return true;
    }

    protected function filterBySize(?string $sizeStr, Serie $serie, $globalMin, $globalMax): bool
    {
        if ($sizeStr === null || $sizeStr === 'n/a') return true;

        /**
         * 100% Ported Line 261: size split into value and unit.
         * NOTE: Original logic DOES NOT normalize GB to MB. It just compares the prefix number.
         * If min is 500 (MB) and result is "1.5 GB", it compares 1.5 to 500. Result: false.
         */
        $parts = preg_split('/\s+/', $sizeStr);
        $value = (float) ($parts[0] ?? 0);

        $min = $serie->custom_search_size_min ?? $globalMin;
        $max = $serie->custom_search_size_max ?? $globalMax;

        return ($value >= ($min ?? 0) && $value <= ($max ?? PHP_INT_MAX));
    }

    protected function download(Serie $serie, Episode $episode, array $item, string $searchQuery): void
    {
        $label = $this->settings->get('torrenting.label') ? $serie->name : 'DuckieTV';
        $client = $this->torrentClientService->getActiveClient();
        $launched = false;

        if ($client && $client->isConnected()) {
            if (isset($item['magnetUrl'])) {
                $launched = $client->addMagnet($item['magnetUrl'], $serie->dlPath, $label);
            } elseif (isset($item['torrentUrl'])) {
                $launched = $client->addTorrentByUrl($item['torrentUrl'], $item['infoHash'], $item['releasename'], $serie->dlPath, $label);
            }
        }

        if ($launched) {
            $this->logActivity($serie, $episode, $searchQuery, self::STATUS_TORRENT_LAUNCHED);
            if (isset($item['infoHash'])) {
                $episode->magnetHash = strtolower($item['infoHash']);
                $episode->save();
            }
        } else {
            // If client is not connected or add failed, we still log it as nothing found/filtered due to connection
            $this->logActivity($serie, $episode, $searchQuery, self::STATUS_NOTHING_FOUND, ' (Torrent client error)');
        }
    }

    protected function extractHash(string $magnetUrl): ?string
    {
        if (preg_match('/btih:([a-f0-9]{40})/i', $magnetUrl, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }
}
