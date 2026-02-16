<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Episode - Represents a single episode of a TV series.
 *
 * Ported from DuckieTV Angular CRUD.entities.js Episode entity.
 * Original table: "Episodes" with 24 fields and 6 schema migrations (versions 8-16).
 * Unique constraint on trakt_id (ON CONFLICT REPLACE in original).
 *
 * This model contains the core business logic for watched/downloaded state management,
 * which in the original Angular app fired $rootScope.$broadcast events.
 * In Laravel, these will be replaced with Laravel Events (see TODO markers).
 *
 * @property int $id Primary key (was ID_Episode)
 * @property int $serie_id Foreign key to series table (was ID_Serie)
 * @property int|null $season_id Foreign key to seasons table (was ID_Season)
 * @property int|null $tvdb_id TheTVDB episode identifier
 * @property string|null $episodename Episode title (max 255 chars)
 * @property int|null $episodenumber Episode number within the season
 * @property int|null $seasonnumber Season number (0 = special)
 * @property int|null $firstaired Air date as millisecond timestamp (bigInteger, matches original WebSQL)
 * @property string|null $firstaired_iso Air date in ISO 8601 format (max 25 chars)
 * @property string|null $imdb_id IMDB episode identifier (max 20 chars)
 * @property string|null $language Episode language code (max 3 chars, e.g. "en")
 * @property string|null $overview Episode description/synopsis
 * @property int|null $rating Average rating
 * @property int|null $ratingcount Number of ratings
 * @property string|null $filename Associated filename (max 255 chars)
 * @property array|null $images Auto-serialized images data (stored as JSON TEXT)
 * @property int $watched Watched flag: 0=unwatched, 1=watched (default: 0)
 * @property int|null $watchedAt Timestamp (ms) when episode was marked as watched
 * @property int $downloaded Downloaded flag: 0=not downloaded, 1=downloaded (default: 0)
 * @property string|null $magnetHash Magnet link info hash for the associated torrent (max 40 chars)
 * @property int|null $trakt_id Trakt.tv episode identifier, UNIQUE
 * @property int $leaked Leaked flag: 0=normal, 1=leaked early (default: 0)
 * @property int|null $absolute Absolute episode number (for anime ordering)
 * @property int|null $tmdb_id TheMovieDB episode identifier
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read string $formatted_episode Computed: "s01e05" format (Blade accessor)
 * @property-read Serie  $serie
 * @property-read Season $season
 */
class Episode extends Model
{
    protected $table = 'episodes';

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
    ];

    // ─── Relationships ──────────────────────────────────────────

    /**
     * The series this episode belongs to.
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(Serie::class, 'serie_id');
    }

    /**
     * The season this episode belongs to.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    // ─── Formatting ─────────────────────────────────────────────

    /**
     * Format this episode as "s01e05" with optional absolute number "(35)".
     * Ported from Episode.prototype.getFormattedEpisode() in CRUD.entities.js.
     */
    public function getFormattedEpisode(): string
    {
        return self::formatEpisode($this->seasonnumber, $this->episodenumber, $this->absolute);
    }

    /**
     * Static formatter: produce "s01e05" or "s02e10(35)" string from season/episode/absolute numbers.
     * Ported from Episode.prototype.formatEpisode() in CRUD.entities.js.
     *
     * @param  int|null  $season  Season number (zero-padded to 2 digits)
     * @param  int|null  $episode  Episode number (zero-padded to 2 digits)
     * @param  int|null  $absolute  Absolute episode number (for anime), shown in parentheses
     */
    public static function formatEpisode(?int $season, ?int $episode, ?int $absolute = null): string
    {
        $sn = str_pad($season ?? 0, 2, '0', STR_PAD_LEFT);
        $en = str_pad($episode ?? 0, 2, '0', STR_PAD_LEFT);
        $abs = '';
        if ($absolute !== null) {
            $abs = '('.str_pad($absolute, 2, '0', STR_PAD_LEFT).')';
        }

        return "s{$sn}e{$en}{$abs}";
    }

    /**
     * Blade accessor: allows using $episode->formatted_episode in templates.
     */
    public function getFormattedEpisodeAttribute(): string
    {
        return $this->getFormattedEpisode();
    }

    /**
     * Get the air date as a Carbon instance, or '?' if unknown.
     * Ported from Episode.prototype.getAirDate().
     *
     * @return \Carbon\Carbon|string
     */
    public function getAirDate(): mixed
    {
        if (! $this->firstaired || $this->firstaired == 0) {
            return '?';
        }

        return \Carbon\Carbon::createFromTimestampMs($this->firstaired);
    }

    /**
     * Get the air time as "HH:MM" string, or '?' if unknown.
     * Ported from Episode.prototype.getAirTime().
     */
    public function getAirTime(): string
    {
        if (! $this->firstaired || $this->firstaired == 0) {
            return '?';
        }

        return \Carbon\Carbon::createFromTimestampMs($this->firstaired)->format('H:i');
    }

    // ─── State Checks ───────────────────────────────────────────

    /**
     * Check if this episode has already aired.
     * Ported from Episode.prototype.hasAired(): firstaired !== 0 && firstaired <= now.
     */
    public function hasAired(): bool
    {
        return $this->firstaired
            && $this->firstaired != 0
            && $this->firstaired <= now()->getTimestampMs();
    }

    /**
     * Check if this episode has been marked as watched.
     * Ported from Episode.prototype.isWatched().
     */
    public function isWatched(): bool
    {
        return (int) $this->watched === 1;
    }

    /**
     * Check if this episode was leaked (aired early/before official date).
     * Ported from Episode.prototype.isLeaked().
     */
    public function isLeaked(): bool
    {
        return (int) $this->leaked === 1;
    }

    /**
     * Check if this episode has been marked as downloaded.
     * Ported from Episode.prototype.isDownloaded().
     */
    public function isDownloaded(): bool
    {
        return (int) $this->downloaded === 1;
    }

    // ─── State Mutations ────────────────────────────────────────

    /**
     * Mark this episode as watched (and optionally as downloaded).
     * Sets watchedAt to current timestamp in milliseconds.
     *
     * Ported from Episode.prototype.markWatched().
     * Original fired: $rootScope.$broadcast('episode:marked:watched', this)
     * and optionally $rootScope.$broadcast('episode:marked:downloaded', this)
     *
     * @param  bool  $watchedDownloadedPaired  When true (default), also marks as downloaded.
     *                                         Logic: "if you watched it, you must have downloaded it"
     */
    public function markWatched(bool $watchedDownloadedPaired = true): self
    {
        $data = [
            'watched' => 1,
            'watchedAt' => now()->getTimestampMs(),
        ];

        if ($watchedDownloadedPaired) {
            $data['downloaded'] = 1;
        }

        $this->update($data);

        // TODO: Fire Laravel events (EpisodeMarkedWatched, EpisodeMarkedDownloaded) — Phase 5

        return $this;
    }

    /**
     * Mark this episode as not watched. Clears watchedAt timestamp.
     *
     * Ported from Episode.prototype.markNotWatched().
     * Original fired: $rootScope.$broadcast('episode:marked:notwatched', this)
     */
    public function markNotWatched(): self
    {
        $this->update([
            'watched' => 0,
            'watchedAt' => null,
        ]);

        // TODO: Fire Laravel event (EpisodeMarkedNotWatched) — Phase 5

        return $this;
    }

    /**
     * Mark this episode as downloaded.
     *
     * Ported from Episode.prototype.markDownloaded().
     * Original fired: $rootScope.$broadcast('episode:marked:downloaded', this)
     */
    public function markDownloaded(): self
    {
        $this->update(['downloaded' => 1]);

        // TODO: Fire Laravel event (EpisodeMarkedDownloaded) — Phase 5

        return $this;
    }

    /**
     * Mark this episode as not downloaded.
     * When paired (default), also clears watched state and magnetHash.
     *
     * Ported from Episode.prototype.markNotDownloaded().
     * Original fired: $rootScope.$broadcast('episode:marked:notdownloaded', this)
     * and optionally $rootScope.$broadcast('episode:marked:notwatched', this)
     *
     * @param  bool  $watchedDownloadedPaired  When true (default), also marks as not watched
     *                                         and clears magnetHash.
     *                                         Logic: "if it's not downloaded, you can't have watched it"
     */
    public function markNotDownloaded(bool $watchedDownloadedPaired = true): self
    {
        $data = ['downloaded' => 0];

        if ($watchedDownloadedPaired) {
            $data['watched'] = 0;
            $data['watchedAt'] = null;
            $data['magnetHash'] = null;
        }

        $this->update($data);

        // TODO: Fire Laravel events (EpisodeMarkedNotDownloaded, EpisodeMarkedNotWatched) — Phase 5

        return $this;
    }

    /**
     * Mark this episode as leaked.
     */
    public function markLeaked(): self
    {
        $this->update(['leaked' => 1]);

        // TODO: Fire Laravel event (EpisodeMarkedLeaked) — Phase 5
        return $this;
    }

    /**
     * Mark this episode as not leaked.
     */
    public function markNotLeaked(): self
    {
        $this->update(['leaked' => 0]);

        // TODO: Fire Laravel event (EpisodeMarkedNotLeaked) — Phase 5
        return $this;
    }
}
