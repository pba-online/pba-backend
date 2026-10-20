<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\CoachRating;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    public function updateRatings(Request $request, int $id): JsonResponse
    {
        $coach = Coach::query()->findOrFail($id);
        $season = Season::current();

        if (! $season) {
            return response()->json(['message' => 'No current season configured.'], 422);
        }

        $grades = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-'];
        $data = $request->validate([
            'offense_grade' => ['required', 'string', 'in:'.implode(',', $grades)],
            'defense_grade' => ['required', 'string', 'in:'.implode(',', $grades)],
            'player_development_grade' => ['required', 'string', 'in:'.implode(',', $grades)],
            'leadership_grade' => ['required', 'string', 'in:'.implode(',', $grades)],
            'overall_grade' => ['required', 'string', 'in:'.implode(',', $grades)],
        ]);

        $rating = CoachRating::query()->updateOrCreate(
            ['coach_id' => $coach->id, 'season_id' => $season->id],
            $data
        );

        return response()->json([
            'coach_id' => $coach->id,
            'season_id' => $season->id,
            'ratings' => $rating->only(array_keys($data)),
        ]);
    }
}
