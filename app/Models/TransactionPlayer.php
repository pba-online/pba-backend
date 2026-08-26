<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
