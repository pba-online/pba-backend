<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $season_id
 * @property int|null $conference_id
 * @property int|null $conference_champion_team_id
 * @property int|null $league_champion_team_id
 * @property int|null $finals_mvp_player_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Season|null $season
 * @property-read Conference|null $conference
 */
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
