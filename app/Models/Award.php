<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $season_id
 * @property string $name
 * @property int|null $player_id
 * @property int|null $coach_id
 * @property int|null $team_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Season|null $season
 * @property-read Player|null $player
 * @property-read Coach|null $coach
 * @property-read Team|null $team
 */
class Award extends Model
{
    protected $fillable = [
        'season_id',
        'name',
        'player_id',
        'coach_id',
        'team_id',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
