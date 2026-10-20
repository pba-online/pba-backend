<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $season_id
 * @property int|null $conference_id
 * @property int $team_id
 * @property int $wins
 * @property int $losses
 * @property float|null $win_percentage
 * @property float|null $games_behind
 * @property int|null $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Team|null $team
 */
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