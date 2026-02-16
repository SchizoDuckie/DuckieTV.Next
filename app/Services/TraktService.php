<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\RateLimitException;

/**
 * TraktService - API wrapper for Trakt.tv V2 API.
 *
 * Ported from DuckieTV Angular TraktTVv2.js (655 lines).
 * Provides HTTP communication with api.trakt.tv for fetching show data,
 * managing OAuth authentication, and syncing watched/collection state.
 *
 * All GET requests include automatic error handling:
 * - 401: Token expired → auto-renew via renewToken()
 * - 420: Trakt limit exceeded → log and abort
 * - 423: User account locked → log and abort
 * - 429: Rate limited → retry after Retry-After header (default 3s)
 * - 502/504: Cloudflare errors → retry after Retry-After header (default 3s)
 *
 * All POST requests for authorized endpoints include Bearer token from settings.
 * Parsers transform raw Trakt JSON into the internal format used by DuckieTV models.
 *
 * OAuth flow uses PIN-based authentication:
 * 1. User visits PIN_URL to get a PIN code
 * 2. PIN is exchanged for access_token + refresh_token via login()
 * 3. Tokens are stored in settings table (trakttv.token, trakttv.refresh_token)
 *
 * @see https://trakt.docs.apiary.io/
 * @see \App\Services\FavoritesService
 */
class TraktService
{
    // ─── Max retry attempts to prevent infinite loops ────────────

    private const MAX_RETRIES = 5;

    // ─── Endpoint Templates ──────────────────────────────────────
    // Ported from TraktTVv2.js lines 19-37
    // %s placeholders are replaced with URL-encoded parameters.
    // dtv_refresh parameter busts CDN caches daily.

    private array $endpoints = [
        'people'            => 'shows/%s/people',
        'serie'             => 'shows/%s?extended=full',
        'seasons'           => 'shows/%s/seasons?extended=full',
        'episodes'          => 'shows/%s/seasons/%s/episodes?extended=full',
        'search'            => 'search/show?extended=full&limit=100&fields=title,aliases&query=%s',
        'trending'          => 'shows/trending?extended=full&limit=500',
        'tvdb_id'           => 'search/tvdb/%s?type=show',
        'trakt_id'          => 'search/trakt/%s?type=show',
        'login'             => 'auth/login',
        'config'            => 'users/settings',
        'token'             => 'oauth/token',
        'watched'           => 'sync/watched/shows?limit=10000',
        'episode_seen'      => 'sync/history',
        'episode_unseen'    => 'sync/history/remove',
        'user_shows'        => 'sync/collection/shows?limit=10000',
        'add_collection'    => 'sync/collection',
        'remove_collection' => 'sync/collection/remove',
    ];

    // ─── Endpoints requiring Bearer token authorization ──────────
    // Ported from TraktTVv2.js line 134

    private array $authorizedEndpoints = ['watched', 'user_shows', 'config'];

    private SettingsService $settings;

    /**
     * Whether to throttle requests to "play nice" with Trakt API.
     * When true, sleeps for 1 second before *every* request.
     */
    private bool $throttlingEnabled = false;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Enable or disable global API throttling.
     * Useful for bulk operations like backup restore.
     */
    public function setThrottling(bool $enabled): void
    {
        $this->throttlingEnabled = $enabled;
    }


    /**
     * Sleep if throttling is enabled.
     */
    private function throttle(): void
    {
        if ($this->throttlingEnabled) {
            sleep(1);
        }
    }

    // ─── URL Builder ─────────────────────────────────────────────

    /**
     * Build a full API URL from an endpoint type and optional parameters.
     * Replaces %s placeholders with URL-encoded values and appends dtv_refresh cache buster.
     *
     * Ported from TraktTVv2.js getUrl() (lines 141-144).
     *
     * @param string      $type   Endpoint key from $endpoints
     * @param string|null $param  First URL parameter (e.g. show ID/slug)
     * @param string|null $param2 Second URL parameter (e.g. season number)
     */
    private function getUrl(string $type, ?string $param = null, ?string $param2 = null): string
    {
        $dtvRefresh = now()->toDateString();
        $path = $this->endpoints[$type];

        if ($param !== null) {
            $path = preg_replace('/%s/', urlencode($param), $path, 1);
        }
        if ($param2 !== null) {
            $path = preg_replace('/%s/', urlencode($param2), $path, 1);
        }

        $separator = str_contains($path, '?') ? '&' : '?';

        return config('services.trakt.api_endpoint') . $path . $separator . 'dtv_refresh=' . $dtvRefresh;
    }

    // ─── HTTP Headers ────────────────────────────────────────────

    /**
     * Build the standard API headers for Trakt.tv requests.
     * Adds Bearer token for authorized endpoints.
     *
     * Ported from TraktTVv2.js request headers block (lines 170-177).
     *
     * @param bool $withAuth Include Bearer authorization token
     */
    private function getHeaders(bool $withAuth = false): array
    {
        $headers = [
            'trakt-api-key' => config('services.trakt.client_id'),
            'trakt-api-version' => '2',
            'Content-Type' => 'application/json',
        ];

        if ($withAuth) {
            $token = $this->settings->get('trakttv.token');
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
        }

        return $headers;
    }

    // ─── Core HTTP Methods ───────────────────────────────────────

    /**
     * Perform an authenticated/unauthenticated GET request to the Trakt API.
     * Handles error codes with automatic retry for rate limiting and Cloudflare errors.
     *
     * Ported from TraktTVv2.js apiGet() (lines 167-239).
     * Error handling logic:
     * - 401 → renewToken() then retry (once)
     * - 420 → Trakt limit exceeded, abort with log
     * - 423 → User account locked, abort with log
     * - 429 → Rate limited, sleep for Retry-After seconds then retry
     * - 502/504 → Cloudflare error, sleep for Retry-After seconds then retry
     *
     * @param string      $type   Endpoint key
     * @param string|null $param  First URL parameter
     * @param string|null $param2 Second URL parameter
     * @param int         $retry  Current retry count (internal, prevents infinite loops)
     * @return mixed Parsed response data
     * @throws \RuntimeException On unrecoverable HTTP errors
     */
    private function apiGet(string $type, ?string $param = null, ?string $param2 = null, int $retry = 0): mixed
    {
        $this->checkRateLimit();
        $url = $this->getUrl($type, $param, $param2);
        // ... rest of method

        $needsAuth = in_array($type, $this->authorizedEndpoints);

        Log::info("TraktService GET: {$url}", ['headers' => $this->getHeaders($needsAuth)]);

        $response = Http::withHeaders($this->getHeaders($needsAuth))
            ->timeout(120)
            ->get($url);

        if ($response->successful()) {
            return $this->parse($type, $response->json());
        }

        return $this->handleError($response, $type, $param, $param2, $retry, 'GET');
    }

    /**
     * Perform an authenticated POST request to the Trakt API.
     * Always includes Bearer token. Handles same error codes as GET.
     *
     * Ported from TraktTVv2.js apiPost() (lines 241-285).
     *
     * @param string $type  Endpoint key
     * @param array  $data  POST body data
     * @param int    $retry Current retry count (internal)
     * @return mixed Response data
     * @throws \RuntimeException On unrecoverable HTTP errors
     */
    private function apiPost(string $type, array $data = [], int $retry = 0): mixed
    {
        $this->checkRateLimit();
        $url = $this->getUrl($type);


        $response = Http::withHeaders($this->getHeaders(true))
            ->timeout(120)
            ->post($url, $data);

        if ($response->successful()) {
            return $response->json();
        }

        return $this->handleError($response, $type, null, null, $retry, 'POST', $data);
    }

    /**
     * Centralized error handler for both GET and POST requests.
     * Implements the retry logic from TraktTVv2.js for various HTTP error codes.
     *
     * @param \Illuminate\Http\Client\Response $response HTTP response
     * @param string      $type    Endpoint key
     * @param string|null $param   First URL parameter (GET only)
     * @param string|null $param2  Second URL parameter (GET only)
     * @param int         $retry   Current retry count
     * @param string      $method  HTTP method ('GET' or 'POST')
     * @param array       $data    POST body data (POST only)
     * @return mixed Parsed response on successful retry
     * @throws \RuntimeException On unrecoverable errors or max retries exceeded
     */
    private function handleError(
        \Illuminate\Http\Client\Response $response,
        string $type,
        ?string $param,
        ?string $param2,
        int $retry,
        string $method,
        array $data = []
    ): mixed {
        $status = $response->status();

        if ($retry >= self::MAX_RETRIES) {
            throw new \RuntimeException("Trakt API: Max retries exceeded for {$type} (HTTP {$status})");
        }

        if ($status === 401) {
            Log::warning('Trakt API: Token expired, renewing...');
            $this->renewToken();
            return $method === 'POST'
                ? $this->apiPost($type, $data, $retry + 1)
                : $this->apiGet($type, $param, $param2, $retry + 1);
        }

        if ($status === 420) {
            Log::error('Trakt API 420: Limit exceeded. See https://github.com/SchizoDuckie/DuckieTV/issues/1447');
            return null;
        }

        if ($status === 423) {
            Log::error('Trakt API 423: User account locked. Email support@trakt.tv to fix your account.');
            return null;
        }

        if (in_array($status, [429, 502, 504])) {
            $retryAfter = (int) ($response->header('Retry-After') ?: 1);
            // Exponential backoff: retryAfter * (2 ^ retry)
            $backoff = $retryAfter * pow(2, $retry);
            
            // Shared global block
            Cache::put('trakt_blocked_until', time() + $backoff, $backoff + 120);

            throw new RateLimitException("Trakt API Rate Limited", $status, $backoff);
        }


        if ($status >= 500) {
            Log::error("Trakt API Server Error ({$status}) on endpoint '{$type}'. Response body: " . $response->body());
        }

        throw new \RuntimeException("Trakt API error {$status}: " . $response->body());

    }

    /**
     * Check if a global rate limit block is active and sleep if necessary.
     */
    private function checkRateLimit(): void
    {
        $blockedUntil = Cache::get('trakt_blocked_until');
        if ($blockedUntil && time() < $blockedUntil) {
            $seconds = $blockedUntil - time();
            throw new RateLimitException("Trakt API: Global block active", 429, $seconds);
        }
    }


    // ─── Parsers ─────────────────────────────────────────────────

    /**
     * Route response data through the appropriate parser.
     * Ported from TraktTVv2.js getParser() (lines 149-153).
     *
     * @param string $type Endpoint key matching a parser method
     * @param mixed  $data Raw JSON response data
     * @return mixed Parsed data
     */
    private function parse(string $type, mixed $data): mixed
    {
        return match ($type) {
            'serie'     => $this->parseShow($data),
            'seasons'   => $this->parseSeasons($data),
            'episodes'  => $this->parseEpisodes($data),
            'search'    => $this->parseSearch($data),
            'trending'  => $this->parseTrending($data),
            'tvdb_id'   => $this->parseTvdbId($data),
            'trakt_id'  => $this->parseTraktId($data),
            'watched'    => $this->parseWatched($data),
            'user_shows' => $this->parseUserCollection($data),
            'people'    => $data, // people parser just returns data as-is
            default     => $data,
        };
    }

    /**
     * Normalize a Trakt show/episode/season object by flattening the nested ids object.
     * Converts ids.trakt → trakt_id, ids.tvdb → tvdb_id, etc.
     * Also maps title → name for consistency with the Serie model.
     *
     * Ported from TraktTVv2.js parsers.trakt() (lines 40-50).
     *
     * @param array $item Raw Trakt object with nested 'ids' key
     * @return array Normalized object with flattened ID fields
     */
    private function normalizeIds(array $item): array
    {
        if (isset($item['ids'])) {
            foreach ($item['ids'] as $key => $value) {
                $item[$key . '_id'] = $value;
            }
        }

        if (isset($item['title'])) {
            $item['name'] = $item['title'];
        }

        return $item;
    }

    /**
     * Parse a single show response.
     * Ported from TraktTVv2.js parsers.serie() (lines 86-88).
     */
    private function parseShow(array $data): array
    {
        return $this->normalizeIds($data);
    }

    /**
     * Parse seasons list response.
     * Ported from TraktTVv2.js parsers.seasons() (lines 54-58).
     */
    private function parseSeasons(array $data): array
    {
        return array_map(fn (array $season) => $this->normalizeIds($season), $data);
    }

    /**
     * Parse episodes list response. Deduplicates by episode number and filters out episode 0.
     * Ported from TraktTVv2.js parsers.episodes() (lines 69-80).
     *
     * @param array $data Raw episodes array from Trakt
     * @return array Deduplicated, normalized episodes
     */
    private function parseEpisodes(array $data): array
    {
        $seen = [];
        $episodes = [];

        foreach ($data as $episode) {
            $number = $episode['number'] ?? 0;
            if ($number === 0 || in_array($number, $seen, true)) {
                continue;
            }
            $episodes[] = $this->normalizeIds($episode);
            $seen[] = $number;
        }

        return $episodes;
    }

    /**
     * Parse search results. Each result wraps a show in a 'show' key.
     * Ported from TraktTVv2.js parsers.search() (lines 59-63).
     */
    private function parseSearch(array $data): array
    {
        return array_map(fn (array $result) => $this->normalizeIds($result['show']), $data);
    }

    /**
     * Parse trending results. Each result wraps a show in a 'show' key.
     * Ported from TraktTVv2.js parsers.trending() (lines 64-68).
     */
    private function parseTrending(array $data): array
    {
        return array_map(fn (array $result) => $this->normalizeIds($result['show']), $data);
    }

    /**
     * Parse TVDB ID lookup results. Filters for type=show and returns the first match.
     * Ported from TraktTVv2.js parsers.tvdb_id() (lines 89-99).
     *
     * @throws \RuntimeException When no show results found
     */
    private function parseTvdbId(array $data): array
    {
        $shows = array_filter($data, fn (array $record) => ($record['type'] ?? '') === 'show');

        if (empty($shows)) {
            throw new \RuntimeException('No results for search by tvdb_id');
        }

        return $this->normalizeIds(array_values($shows)[0]['show']);
    }

    /**
     * Parse Trakt ID lookup results. Filters for type=show and returns the first match.
     * Ported from TraktTVv2.js parsers.trakt_id() (lines 100-110).
     *
     * @throws \RuntimeException When no show results found
     */
    private function parseTraktId(array $data): array
    {
        $shows = array_filter($data, fn (array $record) => ($record['type'] ?? '') === 'show');

        if (empty($shows)) {
            throw new \RuntimeException('No results for search by trakt_id');
        }

        return $this->normalizeIds(array_values($shows)[0]['show']);
    }

    /**
     * Parse watched shows response. Includes season episode lists alongside show data.
     * Ported from TraktTVv2.js parsers.watched() (lines 111-117).
     */
    private function parseWatched(array $data): array
    {
        return array_map(function (array $item) {
            $show = $this->normalizeIds($item['show']);
            $show['seasons'] = $item['seasons'] ?? [];
            return $show;
        }, $data);
    }

    /**
     * Parse user collection (shows) response. Same structure as watched.
     * Ported from TraktTVv2.js parsers.userShows() (lines 118-124).
     */
    private function parseUserCollection(array $data): array
    {
        return array_map(function (array $item) {
            $show = $this->normalizeIds($item['show']);
            $show['seasons'] = $item['seasons'] ?? [];
            return $show;
        }, $data);
    }

    // ─── Public API: Show Data ───────────────────────────────────

    /**
     * Get a single show summary with optional seasons and episodes.
     * When $seriesOnly is false (default), also fetches people, seasons, and all episodes.
     *
     * Ported from TraktTVv2.js service.serie() (lines 293-317).
     * id can be: Trakt.tv ID, Trakt.tv slug, or IMDB ID.
     *
     * @param string     $id           Show identifier (Trakt ID, slug, or IMDB ID)
     * @param array|null $existingSerie Pre-fetched serie data (skips initial API call)
     * @param bool       $seriesOnly   When true, return only series data (no seasons/episodes)
     * @return array Show data with people, seasons, and episodes attached
     * @see https://trakt.docs.apiary.io/#reference/shows/summary/get-a-single-show
     */
    public function serie(string $id, ?array $existingSerie = null, bool $seriesOnly = false): array
    {
        $serie = $existingSerie ?? $this->apiGet('serie', $id);

        if ($seriesOnly) {
            return $serie;
        }

        $serie['people'] = $this->people($serie['trakt_id']);
        $serie['seasons'] = $this->seasons($serie['trakt_id']);

        foreach ($serie['seasons'] as &$season) {
            $season['episodes'] = $this->episodes($serie['trakt_id'], (string) $season['number']);
        }

        return $serie;
    }

    /**
     * Get all seasons for a show.
     * Ported from TraktTVv2.js service.seasons() (lines 322-325).
     *
     * @param string $id Show identifier (Trakt ID, slug, or IMDB ID)
     * @return array List of season objects with flattened IDs
     * @see https://trakt.docs.apiary.io/#reference/seasons/summary/get-all-seasons-for-a-show
     */
    public function seasons(string $id): array
    {
        return $this->apiGet('seasons', $id);
    }

    /**
     * Get all episodes for a specific season of a show.
     * Ported from TraktTVv2.js service.episodes() (lines 332-334).
     *
     * @param string $id            Show identifier (Trakt ID, slug, or IMDB ID)
     * @param string $seasonNumber  Season number
     * @return array List of episode objects, deduplicated by episode number
     * @see https://trakt.docs.apiary.io/#reference/episodes/summary
     */
    public function episodes(string $id, string $seasonNumber): array
    {
        return $this->apiGet('episodes', $id, $seasonNumber);
    }

    /**
     * Get all people (cast and crew) for a show.
     * Ported from TraktTVv2.js service.people() (lines 340-342).
     *
     * @param string $id Show identifier (Trakt ID, slug, or IMDB ID)
     * @return array People data with cast and crew arrays
     * @see https://trakt.docs.apiary.io/#reference/shows/people/get-all-people-for-a-show
     */
    public function people(string $id): array
    {
        return $this->apiGet('people', (string) $id);
    }

    /**
     * Search for shows by title.
     * Ported from TraktTVv2.js service.search() (lines 343-350).
     *
     * @param string $query Search query string
     * @return array List of matching shows with flattened IDs
     */
    public function search(string $query): array
    {
        return $this->apiGet('search', $query);
    }

    /**
     * Get trending shows from Trakt. When $noCache is false, returns locally cached results.
     * When true, fetches fresh data from Trakt API.
     *
     * Ported from TraktTVv2.js service.trending() (lines 361-386).
     * Cache is stored in settings table as 'trakttv.trending.cache' (JSON-encoded).
     *
     * @param bool $noCache When true, bypass cache and fetch from API
     * @return array List of trending shows
     */
    public function trending(bool $noCache = false): array
    {
        if (!$noCache) {
            $cached = $this->settings->get('trakttv.trending.cache');
            if ($cached) {
                return is_string($cached) ? json_decode($cached, true) : $cached;
            }
        }

        $results = $this->apiGet('trending');

        $this->settings->set('trakttv.trending.cache', $results);

        return $results;
    }

    /**
     * Resolve a show by TVDB ID or Trakt ID.
     * Ported from TraktTVv2.js service.resolveID() (lines 393-400).
     *
     * @param string $id          The TVDB or Trakt ID to look up
     * @param bool   $useTraktId  When true, search by trakt_id instead of tvdb_id
     * @return array Show data with flattened IDs
     * @throws \RuntimeException When no results found
     */
    public function resolveID(string $id, bool $useTraktId = false): array
    {
        $type = $useTraktId ? 'trakt_id' : 'tvdb_id';

        return $this->apiGet($type, $id);
    }

    // ─── Public API: Authentication ──────────────────────────────

    /**
     * Get the PIN authentication URL for the user to visit.
     * Ported from TraktTVv2.js service.getPinUrl() (lines 401-403).
     */
    public function getPinUrl(): string
    {
        return config('services.trakt.pin_url');
    }

    /**
     * Exchange a PIN code for an access token and refresh token.
     * Stores both tokens in the settings table for future authenticated requests.
     *
     * Ported from TraktTVv2.js service.login() (lines 408-428).
     *
     * @param string $pin The PIN code obtained from the Trakt PIN URL
     * @return string The access token
     * @throws \RuntimeException On authentication failure
     * @see https://trakt.docs.apiary.io/#reference/authentication-oauth/get-token/exchange-code-for-access_token
     */
    public function login(string $pin): string
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post($this->getUrl('token'), [
                'code' => $pin,
                'client_id' => config('services.trakt.client_id'),
                'client_secret' => config('services.trakt.client_secret'),
                'redirect_uri' => config('services.trakt.redirect_uri'),
                'grant_type' => 'authorization_code',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Trakt login failed: ' . $response->body());
        }

        $data = $response->json();
        $this->settings->set('trakttv.token', $data['access_token']);
        $this->settings->set('trakttv.refresh_token', $data['refresh_token']);

        return $data['access_token'];
    }

    /**
     * Exchange the stored refresh_token for a new access token.
     * Automatically updates both tokens in the settings table.
     *
     * Ported from TraktTVv2.js service.renewToken() (lines 433-454).
     *
     * @return string|null The new access token, or null on failure
     * @see https://trakt.docs.apiary.io/#reference/authentication-oauth/get-token/exchange-refresh_token-for-access_token
     */
    public function renewToken(): ?string
    {
        $refreshToken = $this->settings->get('trakttv.refresh_token');
        if (!$refreshToken) {
            Log::warning('Trakt: No refresh token available for renewal');
            return null;
        }

        $response = Http::withHeaders($this->getHeaders())
            ->post($this->getUrl('token'), [
                'refresh_token' => $refreshToken,
                'client_id' => config('services.trakt.client_id'),
                'client_secret' => config('services.trakt.client_secret'),
                'redirect_uri' => config('services.trakt.redirect_uri'),
                'grant_type' => 'refresh_token',
            ]);

        if (!$response->successful()) {
            Log::error('Trakt: Token renewal failed: ' . $response->body());
            return null;
        }

        $data = $response->json();
        $this->settings->set('trakttv.token', $data['access_token']);
        $this->settings->set('trakttv.refresh_token', $data['refresh_token']);

        Log::info('Trakt: Token has been renewed');

        return $data['access_token'];
    }

    // ─── Public API: Sync ────────────────────────────────────────

    /**
     * Get all shows a user has watched.
     * Ported from TraktTVv2.js service.watched() (lines 459-464).
     *
     * @return array List of watched shows with season data
     * @see https://trakt.docs.apiary.io/#reference/sync/get-watched/get-watched
     */
    public function watched(): array
    {
        return $this->apiGet('watched');
    }

    /**
     * Mark a single episode as watched on Trakt.
     * Ported from TraktTVv2.js service.markEpisodeWatched() (lines 469-481).
     *
     * @param int    $traktId   Episode's Trakt ID
     * @param int    $watchedAt Timestamp in milliseconds when the episode was watched
     * @return mixed API response
     * @see https://trakt.docs.apiary.io/#reference/sync/add-to-history/add-items-to-watched-history
     */
    public function markEpisodeWatched(int $traktId, int $watchedAt): mixed
    {
        return $this->apiPost('episode_seen', [
            'episodes' => [[
                'watched_at' => \Carbon\Carbon::createFromTimestampMs($watchedAt)->toIso8601String(),
                'ids' => ['trakt' => $traktId],
            ]],
        ]);
    }

    /**
     * Batch mark multiple episodes as watched on Trakt.
     * Ported from TraktTVv2.js service.markEpisodesWatched() (lines 486-502).
     *
     * @param array $episodes Array of ['trakt_id' => int, 'watchedAt' => int] entries
     * @return mixed API response
     * @see https://trakt.docs.apiary.io/#reference/sync/add-to-history/add-items-to-watched-history
     */
    public function markEpisodesWatched(array $episodes): mixed
    {
        $episodesArray = array_map(fn (array $ep) => [
            'watched_at' => \Carbon\Carbon::createFromTimestampMs($ep['watchedAt'])->toIso8601String(),
            'ids' => ['trakt' => $ep['trakt_id']],
        ], $episodes);

        return $this->apiPost('episode_seen', ['episodes' => $episodesArray]);
    }

    /**
     * Mark a single episode as not watched on Trakt.
     * Ported from TraktTVv2.js service.markEpisodeNotWatched() (lines 507-518).
     *
     * @param int $traktId Episode's Trakt ID
     * @return mixed API response
     * @see https://trakt.docs.apiary.io/#reference/sync/remove-from-history/remove-items-from-history
     */
    public function markEpisodeNotWatched(int $traktId): mixed
    {
        return $this->apiPost('episode_unseen', [
            'episodes' => [[
                'ids' => ['trakt' => $traktId],
            ]],
        ]);
    }

    /**
     * Get all shows in the user's collection.
     * Ported from TraktTVv2.js service.userShows() (lines 523-528).
     *
     * @return array List of shows in user's collection with season data
     * @see https://trakt.docs.apiary.io/#reference/sync/get-collection/get-collection
     */
    public function userShows(): array
    {
        return $this->apiGet('user_shows');
    }

    /**
     * Add a show to the user's collection on Trakt.
     * Ported from TraktTVv2.js service.addShowToCollection() (lines 533-544).
     *
     * @param int $traktId Show's Trakt ID
     * @return mixed API response
     * @see https://trakt.docs.apiary.io/#reference/sync/add-to-collection/add-items-to-collection
     */
    public function addShowToCollection(int $traktId): mixed
    {
        return $this->apiPost('add_collection', [
            'shows' => [[
                'ids' => ['trakt' => $traktId],
            ]],
        ]);
    }

    /**
     * Add an episode to the user's collection on Trakt (marks as downloaded).
     * Ported from TraktTVv2.js service.markEpisodeDownloaded() (lines 549-560).
     *
     * @param int $traktId Episode's Trakt ID
     * @return mixed API response
     * @see https://trakt.docs.apiary.io/#reference/sync/add-to-collection/add-items-to-collection
     */
    public function markEpisodeDownloaded(int $traktId): mixed
    {
        return $this->apiPost('add_collection', [
            'episodes' => [[
                'ids' => ['trakt' => $traktId],
            ]],
        ]);
    }

    /**
     * Remove a show from the user's collection on Trakt.
     * Ported from TraktTVv2.js service.removeShowFromCollection() (lines 565-576).
     *
     * @param int $traktId Show's Trakt ID
     * @return mixed API response
     * @see https://trakt.docs.apiary.io/#reference/sync/remove-from-collection/remove-items-from-collection
     */
    public function removeShowFromCollection(int $traktId): mixed
    {
        return $this->apiPost('remove_collection', [
            'shows' => [[
                'ids' => ['trakt' => $traktId],
            ]],
        ]);
    }

    /**
     * Remove an episode from the user's collection on Trakt (marks as not downloaded).
     * Ported from TraktTVv2.js service.markEpisodeNotDownloaded() (lines 581-592).
     *
     * @param int $traktId Episode's Trakt ID
     * @return mixed API response
     * @see https://trakt.docs.apiary.io/#reference/sync/remove-from-collection/remove-items-from-collection
     */
    public function markEpisodeNotDownloaded(int $traktId): mixed
    {
        return $this->apiPost('remove_collection', [
            'episodes' => [[
                'ids' => ['trakt' => $traktId],
            ]],
        ]);
    }
}
