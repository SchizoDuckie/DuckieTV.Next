<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Serie - Represents a TV series tracked by the user.
 *
 * Ported from DuckieTV Angular CRUD.entities.js Serie entity.
 * Original table: "Series" with 52 fields and 21 schema migrations (versions 5-25).
 *
 * @property int         $id                    Primary key (was ID_Serie)
 * @property string|null $name                  Series title (max 250 chars)
 * @property string|null $banner                Banner image URL (max 1024 chars)
 * @property string|null $overview              Series description/synopsis
 * @property int|null    $tvdb_id               TheTVDB identifier
 * @property string|null $imdb_id               IMDB identifier (e.g. "tt0903747", max 20 chars)
 * @property int|null    $tvrage_id             TVRage identifier (legacy)
 * @property string|null $actors                Pipe-separated actor names (max 1024 chars)
 * @property string|null $airs_dayofweek        Day of week the show airs (e.g. "Monday", max 10 chars)
 * @property string|null $airs_time             Time the show airs (e.g. "21:00", max 15 chars)
 * @property string|null $timezone              Timezone for air schedule (max 30 chars)
 * @property string|null $contentrating         Content rating (e.g. "TV-MA", max 20 chars)
 * @property \Carbon\Carbon|null $firstaired    Date of first episode airing
 * @property string|null $genre                 Pipe-separated genres (e.g. "drama|thriller", max 50 chars)
 * @property string|null $country               Country of origin (max 50 chars)
 * @property string|null $language              Primary language (max 50 chars)
 * @property string|null $network               Broadcasting network (e.g. "AMC", max 50 chars)
 * @property int|null    $rating                Average rating
 * @property int|null    $ratingcount           Number of ratings
 * @property int|null    $runtime               Episode runtime in minutes
 * @property string|null $status                Show status: "continuing", "ended", etc. (max 50 chars)
 * @property \Carbon\Carbon|null $added         Date the series was added to favorites
 * @property string|null $addedby               How the series was added (max 50 chars)
 * @property string|null $fanart                Fanart image URL (max 150 chars), indexed
 * @property string|null $poster                Poster image URL (max 150 chars)
 * @property int|null    $lastupdated           Last updated timestamp (milliseconds)
 * @property int|null    $lastfetched           Last fetched from API timestamp (milliseconds)
 * @property int|null    $nextupdate            Next scheduled update timestamp (milliseconds)
 * @property bool        $displaycalendar       Whether to show this series on the calendar (default: true)
 * @property bool        $autoDownload          Whether auto-download is enabled for this series (default: true)
 * @property string|null $customSearchString    Custom search string override for torrent searches (max 150 chars)
 * @property bool        $watched               Whether all aired episodes are watched (default: false)
 * @property int         $notWatchedCount       Cached count of unwatched aired episodes (default: 0)
 * @property bool        $ignoreGlobalQuality   Ignore global quality settings for this series (default: false)
 * @property bool        $ignoreGlobalIncludes  Ignore global include keywords for this series (default: false)
 * @property bool        $ignoreGlobalExcludes  Ignore global exclude keywords for this series (default: false)
 * @property string|null $searchProvider        Override search provider for this series (max 20 chars)
 * @property bool        $ignoreHideSpecials    Show specials even if globally hidden (default: false)
 * @property int|null    $customSearchSizeMin   Minimum torrent size in MB for this series
 * @property int|null    $customSearchSizeMax   Maximum torrent size in MB for this series
 * @property int|null    $trakt_id              Trakt.tv identifier, UNIQUE indexed
 * @property string|null $dlPath                Custom download path for this series
 * @property int|null    $customDelay           Custom auto-download delay in hours
 * @property string|null $alias                 Alternative name for search (max 250 chars)
 * @property string|null $customFormat          Custom episode format (max 20 chars)
 * @property int|null    $tmdb_id               TheMovieDB identifier
 * @property string|null $customIncludes        Custom include keywords for torrent filtering (max 150 chars)
 * @property string|null $customExcludes        Custom exclude keywords for torrent filtering (max 150 chars)
 * @property int|null    $customSeeders         Minimum seeder count override for this series
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<Episode> $episodes
 * @property-read \Illuminate\Database\Eloquent\Collection<Season>  $seasons
 */
class Serie extends Model
{
    protected $table = 'series';

    protected $guarded = [];

    protected $casts = [
        'firstaired' => 'date',
        'added' => 'date',
        'displaycalendar' => 'boolean',
        'autoDownload' => 'boolean',
        'watched' => 'boolean',
        'ignoreGlobalQuality' => 'boolean',
        'ignoreGlobalIncludes' => 'boolean',
        'ignoreGlobalExcludes' => 'boolean',
        'ignoreHideSpecials' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'serie_id');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class, 'serie_id');
    }

    // ─── Computed Properties ────────────────────────────────────

    /**
     * Count total episodes for this series.
     */
    public function getEpisodeCount(): int
    {
        return $this->episodes()->count();
    }

    /**
     * Count unwatched aired episodes (excludes specials/season 0 and future episodes).
     * Mirrors the original notWatchedCount computation.
     */
    public function getNotWatchedCount(): int
    {
        return $this->episodes()
            ->where('watched', 0)
            ->where('seasonnumber', '>', 0)
            ->whereNotNull('firstaired')
            ->where('firstaired', '>', 0)
            ->where('firstaired', '<=', now()->getTimestampMs())
            ->count();
    }

    /**
     * Calculate the total runtime of all aired episodes in minutes.
     */
    public function getTotalRunTime(): int
    {
        // For simplicity, we assume the series runtime * number of aired episodes.
        // A more accurate approach would be summing individual episode runtimes if available,
        // but DuckieTV often just uses the series runtime.
        return $this->episodes()
            ->whereNotNull('firstaired')
            ->where('firstaired', '>', 0)
            ->where('firstaired', '<=', now()->getTimestampMs())
            ->where('seasonnumber', '>', 0)
            ->count() * ($this->runtime ?? 0);
    }

    /**
     * Calculate the total runtime of all watched episodes in minutes.
     */
    public function getTotalWatchedTime(): int
    {
        return $this->episodes()
            ->where('watched', 1)
            ->where('watched', 1)
            ->count() * ($this->runtime ?? 0);
    }

    public function getFormattedTotalRunTime(): string
    {
        return \Carbon\CarbonInterval::minutes($this->getTotalRunTime())->cascade()->forHumans();
    }

    public function getFormattedTotalWatchedTime(): string
    {
        return \Carbon\CarbonInterval::minutes($this->getTotalWatchedTime())->cascade()->forHumans();
    }

    public function getWatchedPercentage(): int
    {
        $total = $this->getTotalRunTime();
        return $total > 0 ? round(($this->getTotalWatchedTime() / $total) * 100) : 0;
    }

    /**
     * Strip "The ", "A " prefix for alphabetical sorting.
     * Ported from Serie.prototype.getSortName() in CRUD.entities.js.
     */
    public function getSortName(): string
    {
        return preg_replace('/^(The |A )/i', '', $this->name ?? '');
    }

    // ─── Query Helpers ──────────────────────────────────────────

    /**
     * Get all episodes keyed by their Trakt ID.
     * Ported from Serie.prototype.getEpisodesMap().
     *
     * @return array<int, Episode>
     */
    public function getEpisodesMap(): array
    {
        return $this->episodes->keyBy('trakt_id')->all();
    }

    /**
     * Get all seasons keyed by season number.
     * Ported from Serie.prototype.getSeasonsByNumber().
     *
     * @return array<int, Season>
     */
    public function getSeasonsByNumber(): array
    {
        return $this->seasons->keyBy('seasonnumber')->all();
    }

    /**
     * Get the season with the highest season number.
     * Ported from Serie.prototype.getLatestSeason().
     */
    public function getLatestSeason(): ?Season
    {
        return $this->seasons()->orderByDesc('seasonnumber')->first();
    }

    /**
     * Get the most recent season that has aired episodes (excluding specials).
     * Falls back to latest season if none found.
     * Ported from Serie.prototype.getActiveSeason().
     */
    public function getActiveSeason(): ?Season
    {
        $season = Season::where('serie_id', $this->id)
            ->whereHas('episodes', function ($q) {
                $q->where('seasonnumber', '>', 0)
                    ->where('firstaired', '<', now()->getTimestampMs());
            })
            ->orderByDesc('id')
            ->first();

        return $season ?? $this->getLatestSeason();
    }

    /**
     * Get the first season that has unwatched episodes (excluding specials).
     * Falls back to latest season if all watched.
     * Ported from Serie.prototype.getNotWatchedSeason().
     */
    public function getNotWatchedSeason(): ?Season
    {
        $season = Season::where('serie_id', $this->id)
            ->whereHas('episodes', function ($q) {
                $q->where('seasonnumber', '>', 0)
                    ->where('watched', 0);
            })
            ->orderBy('seasonnumber')
            ->first();

        return $season ?? $this->getLatestSeason();
    }

    /**
     * Get the next upcoming episode (first episode with firstaired > now, or firstaired = 0).
     * Ported from Serie.prototype.getNextEpisode().
     */
    public function getNextEpisode(): ?Episode
    {
        return $this->episodes()
            ->where(function ($q) {
                $q->where('firstaired', '>', now()->getTimestampMs())
                    ->orWhere('firstaired', 0);
            })
            ->orderByDesc('seasonnumber')
            ->orderBy('episodenumber')
            ->orderBy('firstaired')
            ->first();
    }

    /**
     * Get the most recently aired episode.
     * Ported from Serie.prototype.getLastEpisode().
     */
    public function getLastEpisode(): ?Episode
    {
        return $this->episodes()
            ->where('firstaired', '>', 0)
            ->where('firstaired', '<', now()->getTimestampMs())
            ->orderByDesc('seasonnumber')
            ->orderByDesc('episodenumber')
            ->orderByDesc('firstaired')
            ->first();
    }

    // ─── Actions ────────────────────────────────────────────────

    /**
     * Toggle the autoDownload flag and persist.
     * Ported from Serie.prototype.toggleAutoDownload().
     */
    public function toggleAutoDownload(): void
    {
        $this->update(['autoDownload' => !$this->autoDownload]);
    }

    /**
     * Toggle the calendar display flag and persist.
     * Ported from Serie.prototype.toggleCalendarDisplay().
     */
    public function toggleCalendarDisplay(): void
    {
        $this->update(['displaycalendar' => !$this->displaycalendar]);
    }

    /**
     * Mark all aired episodes as watched (and optionally as downloaded).
     * Ported from Serie.prototype.markSerieAsWatched().
     *
     * @param bool $watchedDownloadedPaired When true, also marks episodes as downloaded
     */
    public function markSerieAsWatched(bool $watchedDownloadedPaired = true): void
    {
        $this->episodes()
            ->where('watched', 0)
            ->whereNotNull('firstaired')
            ->where('firstaired', '>', 0)
            ->where('firstaired', '<=', now()->getTimestampMs())
            ->get()
            ->each(fn (Episode $ep) => $ep->markWatched($watchedDownloadedPaired));
    }

    /**
     * Mark all aired episodes as downloaded.
     * Ported from Serie.prototype.markSerieAsDownloaded().
     */
    public function markSerieAsDownloaded(): void
    {
        $this->episodes()
            ->where('downloaded', 0)
            ->whereNotNull('firstaired')
            ->where('firstaired', '>', 0)
            ->where('firstaired', '<=', now()->getTimestampMs())
            ->get()
            ->each(fn (Episode $ep) => $ep->markDownloaded());
    }

    /**
     * Mark all watched episodes as unwatched.
     * Ported from Serie.prototype.markSerieAsUnWatched().
     */
    public function markSerieAsUnWatched(): void
    {
        $this->episodes()
            ->where('watched', 1)
            ->get()
            ->each(fn (Episode $ep) => $ep->markNotWatched());
    }

    /**
     * Check if this series is classified as anime.
     * Ported from Serie.prototype.isAnime().
     */
    public function isAnime(): bool
    {
        return $this->genre && str_contains(strtolower($this->genre), 'anime');
    }
}
