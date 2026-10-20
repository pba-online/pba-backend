<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $player_id
 * @property int|null $from_team_id
 * @property int|null $to_team_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read Player|null $player
 */
class TransactionPlayer extends Model
{
    protected $fillable = ['transaction_id', 'player_id'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
