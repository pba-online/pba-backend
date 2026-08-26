<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeDraftPick extends Model
{
    protected $fillable = [
        'trade_id',
        'draft_pick_id',
        'from_team_id',
        'to_team_id',
    ];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function draftPick(): BelongsTo
    {
        return $this->belongsTo(DraftPick::class);
    }

    public function fromTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'from_team_id');
    }

    public function toTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'to_team_id');
    }
}
