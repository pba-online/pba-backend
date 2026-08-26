<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeagueHistory;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeagueHistoryController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = LeagueHistory::query()
            ->with(['season', 'conference', 'champion', 'finalsMvp'])
            ->orderByDesc('season_id')
            ->orderBy('type')
            ->get()
            ->map(function (LeagueHistory $r) {
                $mvp = $r->finalsMvp;

                // Attach finals MVP onto conference rows for the same season when present on league_champion
                if (! $mvp && $r->type === 'conference_champion') {
                    $league = LeagueHistory::query()
                        ->with('finalsMvp')
                        ->where('season_id', $r->season_id)
                        ->where('type', 'league_champion')
                        ->first();
                    $mvp = $league?->finalsMvp;
                }

                return [
                    'id' => $r->id,
                    'season_id' => $r->season_id,
                    'conference_id' => $r->conference_id,
                    'type' => $r->type,
                    'season' => $r->season,
                    'season_name' => $r->season?->name,
                    'year' => $r->season?->year,
                    'conference' => $r->conference,
                    'conference_name' => $r->conference?->name ?? ($r->type === 'league_champion' ? 'League' : null),
                    'champion' => $r->champion ? ['id' => $r->champion->id, 'name' => $r->champion->name] : null,
                    'champion_name' => $r->champion?->name,
                    'team' => $r->champion ? ['id' => $r->champion->id, 'name' => $r->champion->name] : null,
                    'finals_mvp' => $mvp ? ['id' => $mvp->id, 'name' => $mvp->name] : null,
                    'finals_mvp_name' => $mvp?->name,
                    'mvp' => $mvp?->name,
                ];
            });

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'season_id' => ['nullable', 'exists:seasons,id'],
            'finals_mvp_id' => ['required', 'exists:players,id'],
            'champion_team_id' => ['nullable', 'exists:teams,id'],
            'conference_id' => ['nullable', 'exists:conferences,id'],
            'type' => ['nullable', 'in:league_champion,conference_champion'],
        ]);

        $seasonId = $data['season_id'] ?? Season::current()?->id;
        if (! $seasonId) {
            return response()->json(['message' => 'Season is required.'], 422);
        }

        $row = LeagueHistory::query()->updateOrCreate(
            [
                'season_id' => $seasonId,
                'type' => $data['type'] ?? 'league_champion',
                'conference_id' => $data['conference_id'] ?? null,
            ],
            [
                'finals_mvp_id' => $data['finals_mvp_id'],
                'champion_team_id' => $data['champion_team_id'] ?? null,
            ]
        );

        $row->load(['season', 'conference', 'champion', 'finalsMvp']);

        return response()->json($row, 201);
    }
}
