<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftPick extends Model
{
    protected $fillable = [
        'draft_id',
        'year',
        'round',
        'pick_number',
        'overall_pick',
        'team_id',
        'original_team_id',
        'player_id',
        'notes',
        'is_future',
    ];

    protected function casts(): array
    {
        return [
            'is_future' => 'boolean',
        ];
    }

    protected $appends = ['label', 'pick'];

    public function getPickAttribute(): ?int
    {
        return $this->pick_number ?? $this->overall_pick;
    }

    public function getLabelAttribute(): string
    {
        $parts = array_filter([
            $this->year,
            $this->round ? 'R'.$this->round : null,
            $this->pick_number ? '#'.$this->pick_number : null,
        ]);

        return implode(' ', $parts) ?: 'Pick '.$this->id;
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function originalTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'original_team_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
