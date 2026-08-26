<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueHistory extends Model
{
    protected $table = 'league_history';

    protected $fillable = [
        'season_id',
        'conference_id',
        'champion_team_id',
        'finals_mvp_id',
        'type',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'champion_team_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'champion_team_id');
    }

    public function finalsMvp(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'finals_mvp_id');
    }
}
