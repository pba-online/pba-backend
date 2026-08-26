<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function seasons(): JsonResponse
    {
        $seasons = Season::query()->orderByDesc('id')->get();

        return response()->json($seasons);
    }

    public function divisions(): JsonResponse
    {
        return response()->json(Division::query()->orderBy('name')->get());
    }

    public function index(): JsonResponse
    {
        $teams = Team::query()
            ->with('division')
            ->orderBy('name')
            ->get()
            ->map(fn (Team $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'abbreviation' => $t->abbreviation,
                'city' => $t->city,
                'division_id' => $t->division_id,
                'division_name' => $t->division?->name,
                'venue_id' => $t->venue_id,
                'primary_color' => $t->primary_color,
                'secondary_color' => $t->secondary_color,
                'logo_url' => $t->logo_url,
                'status' => $t->status,
            ]);

        return response()->json($teams);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:255'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'venue_id' => ['nullable', 'exists:venues,id'],
        ]);

        $team = Team::query()->create($data);
        $team->load('division');

        return response()->json($team, 201);
    }
}