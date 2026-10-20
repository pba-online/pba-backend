<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $coach_id
 * @property int|null $season_id
 * @property string|null $offense_grade
 * @property string|null $defense_grade
 * @property string|null $player_development_grade
 * @property string|null $leadership_grade
 * @property string|null $overall_grade
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Coach|null $coach
 * @property-read Season|null $season
 */
class CoachRating extends Model
{
    protected $fillable = [
        'coach_id',
        'season_id',
        'offense_grade',
        'defense_grade',
        'player_development_grade',
        'leadership_grade',
        'overall_grade',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
