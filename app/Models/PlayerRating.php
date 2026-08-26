<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'steal',
        'block',
        'rebound',
        'iq',
    ];

    public const RATING_KEYS = [
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
        'steal',
        'block',
        'rebound',
        'iq',
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
        foreach (self::RATING_KEYS as $key) {
            $data[$key] = (int) ($this->{$key} ?? 0);
        }

        return $data;
    }
}
