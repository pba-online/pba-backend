<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\CoachRating;
use App\Models\Contract;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Season;
use App\Models\TeamCoach;
use App\Models\TeamRoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreeAgentController extends Controller
{
    public function players(): JsonResponse
    {
        $seasonId = Season::current()?->id;
        $rosteredIds = TeamRoster::query()
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->pluck('player_id');

        $players = Player::query()
            ->whereNotIn('id', $rosteredIds)
            ->orderBy('last_name')
            ->get();

        $ratings = PlayerRating::query()
            ->whereIn('player_id', $players->pluck('id'))
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->get()
            ->keyBy('player_id');

        $lastSalaries = Contract::query()
            ->where('type', 'player')
            ->whereIn('player_id', $players->pluck('id'))
            ->with('years')
            ->get()
            ->groupBy('player_id')
            ->map(function ($contracts) {
                $latest = $contracts->sortByDesc('id')->first();

                return $latest?->years->sortByDesc('year_number')->first()?->salary
                    ?? $latest?->total_salary;
            });

        $data = $players->map(function (Player $p) use ($ratings, $lastSalaries) {
            $rating = $ratings->get($p->id);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'photo_url' => $p->photo_url,
                'age' => $p->age,
                'height' => $p->height,
                'height_inches' => $p->height_inches,
                'weight' => $p->weight_lbs,
                'weight_lbs' => $p->weight_lbs,
                'position' => $p->position,
                'overall' => $rating?->overall ?? 0,
                'ratings' => $rating?->toRatingsArray() ?? [],
                'last_salary' => $lastSalaries->get($p->id),
                'this_year_salary' => $lastSalaries->get($p->id),
                'salary' => $lastSalaries->get($p->id),
            ];
        })->values();

        return response()->json($data);
    }

    public function coaches(): JsonResponse
    {
        $seasonId = Season::current()?->id;
        $assignedIds = TeamCoach::query()
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->pluck('coach_id');

        $coaches = Coach::query()
            ->with('ratings')
            ->whereNotIn('id', $assignedIds)
            ->orderBy('name')
            ->get()
            ->map(function (Coach $c) use ($seasonId) {
                $rating = $c->ratings
                    ->when($seasonId, fn ($col) => $col->where('season_id', $seasonId))
                    ->sortByDesc('id')
                    ->first()
                    ?? $c->ratings->sortByDesc('id')->first();

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'role' => $c->role,
                    'photo_url' => $c->photo_url,
                    'offense_grade' => $rating?->offense_grade,
                    'defense_grade' => $rating?->defense_grade,
                    'player_development_grade' => $rating?->player_development_grade,
                    'leadership_grade' => $rating?->leadership_grade,
                    'overall_grade' => $rating?->overall_grade,
                ];
            });

        return response()->json($coaches);
    }

    public function storeCoach(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'photo_url' => ['nullable', 'string'],
            'offense_grade' => ['nullable', 'string', 'max:5'],
            'defense_grade' => ['nullable', 'string', 'max:5'],
            'player_development_grade' => ['nullable', 'string', 'max:5'],
            'leadership_grade' => ['nullable', 'string', 'max:5'],
            'overall_grade' => ['nullable', 'string', 'max:5'],
        ]);

        $coach = Coach::query()->create([
            'name' => $data['name'],
            'role' => $data['role'] ?? 'Head Coach',
            'photo_url' => $data['photo_url'] ?? null,
        ]);

        $season = Season::current();
        CoachRating::query()->create([
            'coach_id' => $coach->id,
            'season_id' => $season?->id,
            'offense_grade' => $data['offense_grade'] ?? null,
            'defense_grade' => $data['defense_grade'] ?? null,
            'player_development_grade' => $data['player_development_grade'] ?? null,
            'leadership_grade' => $data['leadership_grade'] ?? null,
            'overall_grade' => $data['overall_grade'] ?? null,
        ]);

        return response()->json($coach->load('ratings'), 201);
    }
}
