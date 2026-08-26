<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AwardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $seasonId = $request->integer('season_id') ?: null;

        $rows = Award::query()
            ->with(['season', 'player', 'coach', 'team'])
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->orderByDesc('season_id')
            ->orderBy('name')
            ->get()
            ->map(function (Award $a) {
                $winnerName = $a->player?->name ?? $a->coach?->name;

                return [
                    'id' => $a->id,
                    'name' => $a->name,
                    'award_name' => $a->name,
                    'title' => $a->name,
                    'season_id' => $a->season_id,
                    'season' => $a->season,
                    'season_name' => $a->season?->name,
                    'year' => $a->season?->year,
                    'player' => $a->player ? ['id' => $a->player->id, 'name' => $a->player->name] : null,
                    'winner' => $winnerName ? ['name' => $winnerName] : null,
                    'winner_name' => $winnerName,
                    'recipient_name' => $winnerName,
                    'team' => $a->team ? ['id' => $a->team->id, 'name' => $a->team->name] : null,
                    'team_name' => $a->team?->name,
                ];
            });

        return response()->json($rows);
    }
}
