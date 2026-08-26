<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeTeam extends Model
{
    protected $fillable = ['trade_id', 'team_id'];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
