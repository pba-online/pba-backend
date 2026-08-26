<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayoffSeries extends Model
{
    protected $table = 'playoff_series';

    protected $fillable = [
        'playoff_id',
        'season_id',
        'conference_id',
        'round',
        'team1_id',
        'team2_id',
        'winner_team_id',
        'wins_team1',
        'wins_team2',
    ];

    protected static function booted(): void
    {
        static::saved(function (PlayoffSeries $series) {
            if ($series->round === 'finals' && $series->winner_team_id) {
                $series->syncLeagueHistory();
            }
        });
    }

    public function playoff(): BelongsTo
    {
        return $this->belongsTo(Playoff::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(PlayoffGame::class);
    }

    public function syncLeagueHistory(): void
    {
        LeagueHistory::query()->updateOrCreate(
            [
                'season_id' => $this->season_id,
                'type' => 'league_champion',
                'conference_id' => null,
            ],
            [
                'champion_team_id' => $this->winner_team_id,
            ]
        );

        $confFinals = PlayoffSeries::query()
            ->where('season_id', $this->season_id)
            ->where('round', 'conference_finals')
            ->whereNotNull('winner_team_id')
            ->get();

        foreach ($confFinals as $series) {
            LeagueHistory::query()->updateOrCreate(
                [
                    'season_id' => $series->season_id,
                    'conference_id' => $series->conference_id,
                    'type' => 'conference_champion',
                ],
                [
                    'champion_team_id' => $series->winner_team_id,
                ]
            );
        }
    }
}
