<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $series_id
 * @property int $game_number
 * @property int $home_team_id
 * @property int $away_team_id
 * @property \Illuminate\Support\Carbon|null $game_date
 * @property int|null $home_score
 * @property int|null $away_score
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
