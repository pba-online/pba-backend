<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $transaction_id
 * @property \Illuminate\Support\Carbon|null $trade_date
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TradePlayer> $players
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TradeTeam> $teams
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TradeDraftPick> $draftPicks
 */
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
