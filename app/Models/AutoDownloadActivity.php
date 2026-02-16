<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoDownloadActivity extends Model
{
    protected $table = 'autodl_activities';

    protected $fillable = [
        'serie_id',
        'episode_id',
        'search',
        'search_provider',
        'search_extra',
        'status',
        'extra',
        'serie_name',
        'episode_formatted',
        'timestamp',
    ];

    /**
     * Get the series associated with this activity.
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Serie::class, 'serie_id', 'id');
    }

    /**
     * Get the episode associated with this activity.
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class, 'episode_id', 'id');
    }
}
