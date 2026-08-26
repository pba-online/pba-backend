<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
