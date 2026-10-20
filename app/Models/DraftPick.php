<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $draft_id
 * @property int $round_number
 * @property int|null $pick_number
 * @property int|null $original_team_id
 * @property int|null $current_team_id
 * @property int|null $player_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|null $pick
 * @property-read string $label
 * @property-read Draft|null $draft
 * @property-read Team|null $originalTeam
 * @property-read Player|null $player
 */
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
