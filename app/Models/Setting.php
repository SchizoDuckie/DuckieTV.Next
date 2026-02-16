<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Setting - Key-value store for user preferences and application configuration.
 *
 * Replaces the Angular SettingsService's localStorage-based storage.
 * Each row stores a single setting as a key-value pair in SQLite.
 * Complex values (arrays, objects) are stored as JSON strings.
 *
 * This model is primarily accessed through the SettingsService class and
 * the global settings() helper function, not directly.
 *
 * @property string      $key    Setting key (primary key, e.g. "torrenting.client")
 * @property string|null $value  Setting value (stored as text; arrays/objects JSON-encoded)
 *
 * @see \App\Services\SettingsService
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['key', 'value'];
}
