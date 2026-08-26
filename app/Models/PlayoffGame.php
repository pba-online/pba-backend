<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayoffGame extends Model
{
    protected $fillable = [
        'playoff_series_id',
        'game_id',
        'game_number',
        'home_score',
        'away_score',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(PlayoffSeries::class, 'playoff_series_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
