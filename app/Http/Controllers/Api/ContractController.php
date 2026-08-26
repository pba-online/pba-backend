<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->get('type', 'player');
        $teamId = $request->integer('team_id') ?: null;

        $contracts = Contract::query()
            ->with(['team', 'player', 'coach', 'years.season'])
            ->where('type', $type)
            ->where('is_active', true)
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->orderBy('team_id')
            ->get()
            ->map(function (Contract $c) {
                $years = $c->years->sortBy('year_number')->values()->map(fn ($y) => [
                    'id' => $y->id,
                    'season_id' => $y->season_id,
                    'season' => $y->season?->name,
                    'year' => $y->season?->year ?? $y->season?->name,
                    'season_year' => $y->season?->year ?? $y->season?->name,
                    'salary' => $y->salary,
                    'amount' => $y->salary,
                    'year_number' => $y->year_number,
                ]);

                $name = $c->type === 'coach'
                    ? ($c->coach?->name)
                    : ($c->player?->name);

                return [
                    'id' => $c->id,
                    'team_id' => $c->team_id,
                    'team' => $c->team ? ['id' => $c->team->id, 'name' => $c->team->name] : null,
                    'team_name' => $c->team?->name,
                    'type' => $c->type,
                    'player' => $c->player ? [
                        'id' => $c->player->id,
                        'name' => $c->player->name,
                        'first_name' => $c->player->first_name,
                        'last_name' => $c->player->last_name,
                    ] : null,
                    'coach' => $c->coach ? ['id' => $c->coach->id, 'name' => $c->coach->name] : null,
                    'name' => $name,
                    'person_name' => $name,
                    'years_count' => $years->count(),
                    'years' => $years,
                    'contract_years' => $years,
                    'total_salary' => $c->total_salary,
                    'total' => $c->total_salary,
                    'full_contract_salary' => $c->total_salary,
                ];
            });

        return response()->json($contracts);
    }
}
