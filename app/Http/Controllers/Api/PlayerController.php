<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function updateRatings(Request $request, int $id): JsonResponse
    {
        $player = Player::query()->findOrFail($id);
        $season = Season::current();

        if (! $season) {
            return response()->json(['message' => 'No current season configured.'], 422);
        }

        $keys = PlayerRating::RATING_KEYS;
        $rules = [];
        foreach ($keys as $key) {
            $rules[$key] = ['nullable', 'integer', 'min:0', 'max:99'];
        }
        $data = $request->validate($rules);

        $rating = PlayerRating::query()->firstOrNew([
            'player_id' => $player->id,
            'season_id' => $season->id,
        ]);

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $rating->{$key} = $data[$key];
            }
        }
        $rating->save();

        return response()->json([
            'message' => 'Ratings updated',
            'player_id' => $player->id,
            'season_id' => $season->id,
            'ratings' => $rating->toRatingsArray(),
        ]);
    }
}
