<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamCoach extends Model
{
    protected $fillable = [
        'team_id',
        'coach_id',
        'season_id',
        'role',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
