<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Standing extends Model
{
    protected $fillable = [
        'season_id',
        'team_id',
        'conference_id',
        'wins',
        'losses',
        'pct',
        'gb',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'pct' => 'float',
            'gb' => 'float',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}