<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoachRating;
use App\Models\Contract;
use App\Models\ContractYear;
use App\Models\PlayerRating;
use App\Models\Season;
use App\Models\TeamCoach;
use App\Models\TeamRoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $seasonId = $request->integer('season_id') ?: Season::current()?->id;
        $teamId = $request->integer('team_id') ?: null;

        $query = TeamRoster::query()
            ->with(['player', 'team'])
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId));

        $rosters = $query->get();
        $playerIds = $rosters->pluck('player_id')->all();

        $ratings = PlayerRating::query()
            ->whereIn('player_id', $playerIds)
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->get()
            ->keyBy('player_id');

        $contracts = Contract::query()
            ->with('years')
            ->where('type', 'player')
            ->where('is_active', true)
            ->whereIn('player_id', $playerIds)
            ->get()
            ->keyBy('player_id');

        $currentSeasonId = $seasonId;

        $data = $rosters->map(function (TeamRoster $roster) use ($ratings, $contracts, $currentSeasonId) {
            $player = $roster->player;
            $rating = $ratings->get($player->id);
            $contract = $contracts->get($player->id);
            $years = $contract?->years ?? collect();
            $thisYear = $years->firstWhere('season_id', $currentSeasonId);
            $salaryThisYear = $thisYear?->salary ?? $years->sortBy('year_number')->first()?->salary;
            $totalSalary = $contract?->total_salary ?? $years->sum('salary');

            return [
                'id' => $player->id,
                'player_id' => $player->id,
                'roster_id' => $roster->id,
                'team_id' => $roster->team_id,
                'team_name' => $roster->team?->name,
                'team' => $roster->team ? ['id' => $roster->team->id, 'name' => $roster->team->name] : null,
                'name' => $player->name,
                'first_name' => $player->first_name,
                'last_name' => $player->last_name,
                'photo_url' => $player->photo_url,
                'age' => $player->age,
                'height' => $player->height,
                'height_inches' => $player->height_inches,
                'weight' => $player->weight_lbs,
                'weight_lbs' => $player->weight_lbs,
                'position' => $player->position,
                'jersey_number' => $roster->jersey_number,
                'overall' => $rating?->overall ?? 0,
                'ratings' => $rating?->toRatingsArray() ?? [],
                'salary_this_year' => $salaryThisYear,
                'this_year_salary' => $salaryThisYear,
                'current_salary' => $salaryThisYear,
                'total_salary' => $totalSalary,
                'full_contract_salary' => $totalSalary,
                'contract' => $contract ? [
                    'id' => $contract->id,
                    'this_year_salary' => $salaryThisYear,
                    'total_salary' => $totalSalary,
                    'years' => $years->map(fn (ContractYear $y) => [
                        'season_id' => $y->season_id,
                        'salary' => $y->salary,
                        'year_number' => $y->year_number,
                    ]),
                ] : null,
            ];
        })->values();

        return response()->json($data);
    }

    public function coaches(Request $request): JsonResponse
    {
        $seasonId = $request->integer('season_id') ?: Season::current()?->id;
        $teamId = $request->integer('team_id') ?: null;

        $assignments = TeamCoach::query()
            ->with(['coach.ratings', 'team'])
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->get();

        $data = $assignments->map(function (TeamCoach $row) use ($seasonId) {
            $coach = $row->coach;
            $rating = $coach->ratings
                ->when($seasonId, fn ($c) => $c->where('season_id', $seasonId))
                ->sortByDesc('id')
                ->first()
                ?? $coach->ratings->sortByDesc('id')->first();

            return [
                'id' => $coach->id,
                'name' => $coach->name,
                'photo_url' => $coach->photo_url,
                'role' => $row->role ?: $coach->role,
                'team_id' => $row->team_id,
                'team_name' => $row->team?->name,
                'team' => $row->team ? ['id' => $row->team->id, 'name' => $row->team->name] : null,
                'offense_grade' => $rating?->offense_grade,
                'defense_grade' => $rating?->defense_grade,
                'player_development_grade' => $rating?->player_development_grade,
                'leadership_grade' => $rating?->leadership_grade,
                'overall_grade' => $rating?->overall_grade,
            ];
        })->values();

        return response()->json($data);
    }
}
