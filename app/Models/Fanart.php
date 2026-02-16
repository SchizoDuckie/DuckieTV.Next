<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fanart - Stores fanart images fetched from Fanart.tv for a TV series.
 *
 * Ported from DuckieTV Angular CRUD.entities.js Fanart entity.
 * Original table: "Fanart" with 4 fields, no migrations.
 * The json field stores the full Fanart.tv API response for a series,
 * allowing access to all available artwork types.
 *
 * @property int $id Primary key (was ID_Fanart)
 * @property int $tvdb_id TheTVDB identifier, indexed (used to look up fanart by series)
 * @property string|null $poster Primary poster image URL (max 255 chars)
 * @property array|null $json Full Fanart.tv API response (auto-serialized JSON)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Fanart extends Model
{
    protected $table = 'fanart';

    protected $guarded = [];

    protected $casts = [
        'json' => 'array',
    ];
}
