<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $player_id
 * @property int $season_id
 * @property int|null $overall
 * @property int|null $inside_scoring
 * @property int|null $mid_range
 * @property int|null $three_point
 * @property int|null $free_throw
 * @property int|null $finishing
 * @property int|null $dunking
 * @property int|null $passing
 * @property int|null $ball_handling
 * @property int|null $perimeter_defense
 * @property int|null $interior_defense
 * @property int|null $stealing
 * @property int|null $blocking
 * @property int|null $offensive_rebound
 * @property int|null $defensive_rebound
 * @property int|null $speed
 * @property int|null $acceleration
 * @property int|null $strength
 * @property int|null $stamina
 * @property int|null $vertical
 * @property int|null $consistency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Player|null $player
 * @property-read Season|null $season
 */
class PlayerRating extends Model
{
    protected $fillable = [
        'player_id',
        'season_id',
        'overall',
        'inside',
        'mid_range',
        'three_point',
        'free_throw',
        'dunk',
        'layup',
        'passing',
        'ball_handle',
        'speed',
        'acceleration',
        'strength',
        'vertical',
        'stamina',
        'defense',
        'interior_defense',
        'steal',
        'block',
        'offensive_rebound',
        'rebound',
        'iq',
        'consistency',
    ];

    public const RATING_KEYS = [
        'overall',
        'inside_scoring',
        'mid_range',
        'three_point',
        'free_throw',
        'finishing',
        'dunking',
        'passing',
        'ball_handling',
        'perimeter_defense',
        'interior_defense',
        'stealing',
        'blocking',
        'offensive_rebound',
        'defensive_rebound',
        'speed',
        'acceleration',
        'strength',
        'stamina',
        'vertical',
        'consistency',
    ];

    public const RATING_FIELD_MAP = [
        'overall' => 'overall',
        'inside_scoring' => 'inside',
        'mid_range' => 'mid_range',
        'three_point' => 'three_point',
        'free_throw' => 'free_throw',
        'finishing' => 'layup',
        'dunking' => 'dunk',
        'passing' => 'passing',
        'ball_handling' => 'ball_handle',
        'perimeter_defense' => 'defense',
        'interior_defense' => 'interior_defense',
        'stealing' => 'steal',
        'blocking' => 'block',
        'offensive_rebound' => 'offensive_rebound',
        'defensive_rebound' => 'rebound',
        'speed' => 'speed',
        'acceleration' => 'acceleration',
        'strength' => 'strength',
        'stamina' => 'stamina',
        'vertical' => 'vertical',
        'consistency' => 'consistency',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function toRatingsArray(): array
    {
        $data = [];
        foreach (self::RATING_FIELD_MAP as $key => $column) {
            $data[$key] = (int) ($this->{$column} ?? 0);
        }

        return $data;
    }
}
