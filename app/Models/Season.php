<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Season - Represents a season of a TV series.
 *
 * Ported from DuckieTV Angular CRUD.entities.js Season entity.
 * Original table: "Seasons" with 11 fields and 8 schema migrations (versions 2-8).
 * Unique constraint on (serie_id, seasonnumber, trakt_id).
 *
 * @property int         $id               Primary key (was ID_Season)
 * @property int         $serie_id         Foreign key to series table (was ID_Serie)
 * @property string|null $poster           Season poster image URL (max 255 chars)
 * @property string|null $overview         Season description/synopsis
 * @property int         $seasonnumber     Season number (0 = specials)
 * @property int|null    $ratings          Average rating for this season
 * @property int|null    $ratingcount      Number of ratings for this season
 * @property bool        $watched          Whether all episodes in this season are watched (default: false)
 * @property int         $notWatchedCount  Cached count of unwatched episodes in this season (default: 0)
 * @property int|null    $trakt_id         Trakt.tv season identifier
 * @property int|null    $tmdb_id          TheMovieDB season identifier
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Serie $serie
 * @property-read \Illuminate\Database\Eloquent\Collection<Episode> $episodes
 */
class Season extends Model
{
    protected $table = 'seasons';

    protected $guarded = [];

    protected $casts = [
        'watched' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────────────

    /**
     * The series this season belongs to.
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(Serie::class, 'serie_id');
    }

    /**
     * All episodes in this season.
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'season_id');
    }

    // ─── Computed Properties ────────────────────────────────────

    /**
     * Count total episodes in this season.
     * Ported from Season.prototype.getEpisodes().length equivalent.
     */
    public function getEpisodeCount(): int
    {
        return $this->episodes()->count();
    }

    /**
     * Count unwatched episodes in this season.
     */
    public function getNotWatchedCount(): int
    {
        return $this->episodes()->where('watched', 0)->count();
    }

    // ─── Actions ────────────────────────────────────────────────

    /**
     * Mark all aired episodes in this season as watched (and optionally as downloaded).
     * Also sets the season's own watched flag.
     * Ported from Season.prototype.markSeasonAsWatched().
     *
     * @param bool $watchedDownloadedPaired When true, also marks episodes as downloaded
     */
    public function markSeasonAsWatched(bool $watchedDownloadedPaired = true): void
    {
        $this->episodes()
            ->where('watched', 0)
            ->get()
            ->filter(fn (Episode $ep) => $ep->hasAired())
            ->each(fn (Episode $ep) => $ep->markWatched($watchedDownloadedPaired));

        $this->update(['watched' => true]);
    }

    /**
     * Mark all episodes in this season as unwatched.
     * Also clears the season's own watched flag.
     * Ported from Season.prototype.markSeasonAsUnWatched().
     */
    public function markSeasonAsUnWatched(): void
    {
        $this->episodes()
            ->where('watched', 1)
            ->get()
            ->each(fn (Episode $ep) => $ep->markNotWatched());

        $this->update(['watched' => false]);
    }
}
