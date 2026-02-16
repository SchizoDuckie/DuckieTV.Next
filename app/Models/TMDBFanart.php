<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TMDBFanart - Stores artwork fetched from TheMovieDB (TMDB) for series and episodes.
 *
 * Ported from DuckieTV Angular CRUD.entities.js TMDBFanart entity.
 * Original table: "TMDBFanart" with 7 fields, no migrations.
 * Supports multiple entity types (series, seasons, episodes) via entity_type field.
 *
 * @property int $id Primary key
 * @property int $entity_type Type of entity: series, season, or episode (indexed)
 * @property int $tmdb_id TheMovieDB identifier (indexed)
 * @property string|null $poster Poster image URL
 * @property string|null $fanart Fanart/backdrop image URL
 * @property string|null $screenshot Episode screenshot/still image URL
 * @property \Carbon\Carbon|null $added Date the fanart was cached
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TMDBFanart extends Model
{
    protected $table = 'tmdb_fanart';

    protected $guarded = [];

    protected $casts = [
        'added' => 'date',
    ];
}
