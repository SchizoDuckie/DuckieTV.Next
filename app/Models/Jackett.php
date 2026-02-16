<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jackett - Stores Jackett/Prowlarr indexer configurations for torrent searching.
 *
 * Ported from DuckieTV Angular CRUD.entities.js Jackett entity.
 * Original table: "Jackett" with 7 fields and 1 migration (version 2).
 * Each row represents a configured Jackett indexer with its Torznab API endpoint.
 *
 * @property int         $id              Primary key (was ID_Jackett)
 * @property string|null $name            Indexer display name (max 40 chars)
 * @property string|null $torznab         Torznab API endpoint URL (max 200 chars)
 * @property int         $enabled         Whether the indexer is enabled: 0=disabled, 1=enabled (default: 0)
 * @property int         $torznabEnabled  Whether Torznab protocol is enabled: 0=disabled, 1=enabled (default: 0)
 * @property string|null $apiKey          Jackett API key for authentication (max 40 chars)
 * @property array|null  $json            Additional indexer configuration/capabilities (auto-serialized JSON)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Jackett extends Model
{
    protected $table = 'jackett';

    protected $guarded = [];

    protected $casts = [
        'json' => 'array',
    ];

    /**
     * Check if this indexer is enabled.
     * Ported from Jackett.prototype.isEnabled().
     */
    public function isEnabled(): bool
    {
        return (int) $this->enabled === 1;
    }

    /**
     * Enable this indexer and persist.
     * Ported from Jackett.prototype.setEnabled().
     */
    public function setEnabled(): self
    {
        $this->update(['enabled' => 1]);
        return $this;
    }

    /**
     * Disable this indexer and persist.
     * Ported from Jackett.prototype.setDisabled().
     */
    public function setDisabled(): self
    {
        $this->update(['enabled' => 0]);
        return $this;
    }
}
