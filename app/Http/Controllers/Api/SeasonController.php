<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    /**
     * Get seasons for the Season Filter.
     */
    public function seasons(): JsonResponse
    {
        $seasons = Season::query()
            ->orderByDesc('season_year')
            ->orderByDesc('id')
            ->get([
                'id',
                'season_year',
                'conference_season',
                'conference_id',
                'is_current',
            ]);

        return response()->json($seasons);
    }

    /**
     * Create a new season.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'season_year' => ['required', 'string', 'max:20'],
            'conference_season' => ['required', 'string', 'max:255'],
            'conference_id' => [
                'required',
                'integer',
                'exists:conferences,id'
            ],
            'is_current' => ['boolean'],
        ]);

        if (!empty($data['is_current'])) {
            Season::query()->update([
                'is_current' => false
            ]);
        }

        $season = Season::query()->create($data);

        return response()->json($season, 201);
    }
}