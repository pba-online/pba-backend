<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    protected $fillable = [
        'season_id',
        'description',
        'status',
        'created_by',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TradeTeam::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(TradePlayer::class);
    }

    public function draftPicks(): HasMany
    {
        return $this->hasMany(TradeDraftPick::class);
    }
}
