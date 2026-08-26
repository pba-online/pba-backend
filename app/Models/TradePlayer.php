<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradePlayer extends Model
{
    protected $fillable = [
        'trade_id',
        'player_id',
        'from_team_id',
        'to_team_id',
    ];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
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
