<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'team_id',
        'player_id',
        'coach_id',
        'type',
        'start_season_id',
        'end_season_id',
        'total_salary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_salary' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function years(): HasMany
    {
        return $this->hasMany(ContractYear::class);
    }

    public function contractYears(): HasMany
    {
        return $this->hasMany(ContractYear::class);
    }

    public function startSeason(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'start_season_id');
    }

    public function endSeason(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'end_season_id');
    }
}
