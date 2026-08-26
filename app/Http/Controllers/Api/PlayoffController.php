<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayoffSeries;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayoffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $seasonId = $request->integer('season_id') ?: Season::current()?->id;

        $rows = PlayoffSeries::query()
            ->with(['conference', 'team1', 'team2', 'winner', 'playoff'])
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->orderByRaw("CASE round
                WHEN 'first_round' THEN 1
                WHEN 'semifinals' THEN 2
                WHEN 'conference_finals' THEN 3
                WHEN 'finals' THEN 4
                ELSE 5 END")
            ->orderBy('id')
            ->get()
            ->map(fn (PlayoffSeries $s) => [
                'id' => $s->id,
                'season_id' => $s->season_id,
                'round' => $s->round,
                'round_name' => ucwords(str_replace('_', ' ', $s->round)),
                'conference_id' => $s->conference_id,
                'conference' => $s->conference,
                'conference_name' => $s->conference?->name,
                'team1' => $s->team1 ? ['id' => $s->team1->id, 'name' => $s->team1->name] : null,
                'team2' => $s->team2 ? ['id' => $s->team2->id, 'name' => $s->team2->name] : null,
                'home_team' => $s->team1 ? ['id' => $s->team1->id, 'name' => $s->team1->name] : null,
                'away_team' => $s->team2 ? ['id' => $s->team2->id, 'name' => $s->team2->name] : null,
                'team_a' => $s->team1?->name,
                'team_b' => $s->team2?->name,
                'wins_team1' => $s->wins_team1,
                'wins_team2' => $s->wins_team2,
                'result' => "{$s->wins_team1}–{$s->wins_team2}",
                'score' => "{$s->wins_team1}–{$s->wins_team2}",
                'winner' => $s->winner ? ['id' => $s->winner->id, 'name' => $s->winner->name] : null,
                'winner_name' => $s->winner?->name,
                'series' => [
                    'wins_team1' => $s->wins_team1,
                    'wins_team2' => $s->wins_team2,
                ],
            ]);

        return response()->json($rows);
    }
}
