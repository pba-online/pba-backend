<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandingController extends Controller
{
   public function index(Request $request): JsonResponse
{
    $seasonId = $request->integer('season_id');

    // If no season was selected, use current season
    if (!$seasonId) {
        $seasonId = Season::current()?->id;
    }

    // Get the selected season
    $season = $seasonId
        ? Season::find($seasonId)
        : null;

    // Get the conference from the selected season
    $conferenceId = $season?->conference_id;

    // Get all teams
    $teams = Team::with('division')->get();

    // Get standings for THIS season
    // and THIS conference
    $standings = Standing::query()
        ->where('season_id', $seasonId)
        ->when(
            $conferenceId,
            fn ($query) =>
                $query->where('conference_id', $conferenceId)
        )
        ->get()
        ->keyBy('team_id');

    $rows = $teams->map(function (Team $team) use (
        $standings,
        $seasonId
    ) {
        $s = $standings->get($team->id);

        $wins = $s?->wins ?? 0;
        $losses = $s?->losses ?? 0;

        $pct = ($wins + $losses) > 0
            ? $wins / ($wins + $losses)
            : 0;

        return [
            'id' => $s?->id,

            'team_id' => $team->id,

            'season_id' => $seasonId,

            'conference_id' => $s?->conference_id,

            'rank' => $s?->rank,

            'wins' => $wins,
            'losses' => $losses,

            'w' => $wins,
            'l' => $losses,

            'pct' => number_format($pct, 3),
            'win_pct' => number_format($pct, 3),

            'gb' => $s?->gb ?? 0,
            'games_behind' => $s?->gb ?? 0,

            'team' => [
                'id' => $team->id,
                'name' => $team->name,
            ],

            'team_name' => $team->name,

            'division_name' =>
                $team->division?->name ?? 'Unassigned',
        ];
    });

    $grouped = $rows
        ->groupBy('division_name')
        ->map(fn ($group, $name) => [
            'division' => $name,

            'standings' => $group
                ->sortByDesc('wins')
                ->values(),
        ])
        ->values();

    return response()->json([
        'season' => $season
            ? [
                'id' => $season->id,
                'season_year' => $season->season_year,
                'conference_season' =>
                    $season->conference_season,
                'conference_id' =>
                    $season->conference_id,
            ]
            : null,

        'data' => $grouped,
    ]);
}

    // Creates the standings row if it doesn't exist yet, updates it if it does
    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'wins' => ['required', 'integer', 'min:0'],
            'losses' => ['required', 'integer', 'min:0'],
        ]);

        $standing = Standing::updateOrCreate(
            ['team_id' => $data['team_id'], 'season_id' => $data['season_id']],
            ['wins' => $data['wins'], 'losses' => $data['losses']]
        );

        $standing->load('team.division');
        $wins = $standing->wins;
        $losses = $standing->losses;
        $pct = ($wins + $losses) > 0 ? $wins / ($wins + $losses) : 0;

        return response()->json([
            'data' => [
                'id' => $standing->id,
                'team_id' => $standing->team_id,
                'season_id' => $standing->season_id,
                'rank' => $standing->rank,
                'wins' => $wins,
                'losses' => $losses,
                'w' => $wins,
                'l' => $losses,
                'pct' => number_format($pct, 3),
                'win_pct' => number_format($pct, 3),
                'gb' => $standing->gb ?? 0,
                'games_behind' => $standing->gb ?? 0,
                'team' => ['id' => $standing->team->id, 'name' => $standing->team->name],
                'team_name' => $standing->team->name,
                'division_name' => $standing->team->division?->name ?? 'Unassigned',
            ],
        ]);
    }
}