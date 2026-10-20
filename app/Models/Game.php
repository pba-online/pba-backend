<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $season_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int|null $venue_id
 * @property \Illuminate\Support\Carbon|null $game_date
 * @property string|null $game_time
 * @property int|null $home_score
 * @property int|null $away_score
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Team|null $homeTeam
 * @property-read Team|null $awayTeam
 * @property-read Venue|null $venue
 */
class Game extends Model
{
    protected $fillable = [
        'season_id',
        'home_team_id',
        'away_team_id',
        'venue_id',
        'game_date',
        'home_score',
        'away_score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'game_date' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
