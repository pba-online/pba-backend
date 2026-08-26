<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use App\Models\DraftPick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = $request->get('year');

        // Flatten picks for frontend table; include year from draft
        $picks = DraftPick::query()
            ->with(['team', 'player', 'draft', 'originalTeam'])
            ->where(function ($q) {
                $q->where('is_future', false)->orWhereNotNull('player_id');
            })
            ->when($year, function ($q) use ($year) {
                $q->where(function ($inner) use ($year) {
                    $inner->where('year', $year)
                        ->orWhereHas('draft', fn ($d) => $d->where('year', $year));
                });
            })
            ->orderByDesc('year')
            ->orderBy('round')
            ->orderBy('pick_number')
            ->get()
            ->map(fn (DraftPick $p) => [
                'id' => $p->id,
                'year' => $p->year ?: $p->draft?->year,
                'draft_year' => $p->year ?: $p->draft?->year,
                'round' => $p->round,
                'pick' => $p->pick_number ?? $p->overall_pick,
                'overall_pick' => $p->overall_pick,
                'team' => $p->team ? ['id' => $p->team->id, 'name' => $p->team->name] : null,
                'team_name' => $p->team?->name,
                'player' => $p->player ? ['id' => $p->player->id, 'name' => $p->player->name] : null,
                'player_name' => $p->player?->name,
                'name' => $p->player?->name,
                'picks' => null,
            ]);

        // Also expose draft wrappers when no year filter produces empty (optional)
        if ($picks->isEmpty() && ! $year) {
            $drafts = Draft::query()->with(['picks.team', 'picks.player'])->orderByDesc('year')->get();

            return response()->json($drafts);
        }

        return response()->json($picks);
    }

    public function ownedPicks(Request $request): JsonResponse
    {
        $teamId = $request->integer('team_id') ?: null;

        $picks = DraftPick::query()
            ->with(['originalTeam', 'team'])
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->where(function ($q) {
                $q->where('is_future', true)->orWhereNull('player_id');
            })
            ->orderBy('year')
            ->orderBy('round')
            ->get()
            ->map(fn (DraftPick $p) => [
                'id' => $p->id,
                'year' => $p->year,
                'round' => $p->round,
                'pick' => $p->pick_number,
                'label' => $p->label,
                'notes' => $p->notes,
                'team_id' => $p->team_id,
                'original_team' => $p->originalTeam
                    ? ['id' => $p->originalTeam->id, 'name' => $p->originalTeam->name]
                    : null,
                'original_team_name' => $p->originalTeam?->name,
            ]);

        return response()->json($picks);
    }
}
