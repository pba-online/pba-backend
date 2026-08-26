<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $seasonId = $request->integer('season_id') ?: Season::current()?->id;
        $teamId = $request->integer('team_id') ?: null;

        $games = Game::query()
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->when($teamId, function ($q) use ($teamId) {
                $q->where(function ($inner) use ($teamId) {
                    $inner->where('home_team_id', $teamId)
                        ->orWhere('away_team_id', $teamId);
                });
            })
            ->orderBy('game_date')
            ->get()
            ->map(fn (Game $g) => [
                'id' => $g->id,
                'season_id' => $g->season_id,
                'home_team_id' => $g->home_team_id,
                'away_team_id' => $g->away_team_id,
                'date' => optional($g->game_date)?->toDateString(),
                'game_date' => optional($g->game_date)?->toDateTimeString(),
                'home_score' => $g->home_score,
                'away_score' => $g->away_score,
                'status' => $g->status,
                'result' => $g->home_score !== null
                    ? "{$g->home_score}–{$g->away_score}"
                    : null,
                'home_team' => $g->homeTeam ? ['id' => $g->homeTeam->id, 'name' => $g->homeTeam->name] : null,
                'away_team' => $g->awayTeam ? ['id' => $g->awayTeam->id, 'name' => $g->awayTeam->name] : null,
                'home_team_name' => $g->homeTeam?->name,
                'away_team_name' => $g->awayTeam?->name,
                'venue' => $g->venue ? ['id' => $g->venue->id, 'name' => $g->venue->name] : null,
                'venue_name' => $g->venue?->name,
            ]);

        return response()->json($games);
    }
}
