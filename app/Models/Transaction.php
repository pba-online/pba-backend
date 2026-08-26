<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'season_id',
        'type',
        'from_team_id',
        'to_team_id',
        'date',
        'notes',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function fromTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'from_team_id');
    }

    public function toTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'to_team_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactionPlayers(): HasMany
    {
        return $this->hasMany(TransactionPlayer::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'transaction_players')->withTimestamps();
    }
}
