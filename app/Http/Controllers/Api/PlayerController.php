<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\TeamRoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PlayerController extends Controller
{
    public function update(Request $request, int $id): JsonResponse
    {
        $player = Player::query()->findOrFail($id);
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'height_inches' => ['sometimes', 'nullable', 'integer', 'min:48', 'max:96'],
            'weight_lbs' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
            'position' => ['sometimes', 'nullable', 'string', 'max:20'],
            'jersey_number' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99'],
            'season_id' => ['required_with:jersey_number', 'nullable', 'integer', 'exists:seasons,id'],
        ]);

        $updateJersey = array_key_exists('jersey_number', $data);
        $seasonId = $data['season_id'] ?? null;
        unset($data['jersey_number'], $data['season_id']);

        if (array_key_exists('height_inches', $data) && ! Schema::hasColumn('players', 'height_inches')) {
            $heightInches = $data['height_inches'];
            unset($data['height_inches']);

            if (! Schema::hasColumn('players', 'height')) {
                throw ValidationException::withMessages([
                    'height_inches' => ['The players table has no supported height column.'],
                ]);
            }

            $data['height'] = $heightInches === null
                ? null
                : intdiv((int) $heightInches, 12)."'".((int) $heightInches % 12);
        }

        $playerData = DB::transaction(function () use ($player, $data, $updateJersey, $seasonId, $request) {
            $player->fill($data);
            $player->save();

            $jerseyNumber = null;
            if ($updateJersey) {
                $roster = TeamRoster::query()
                    ->where('player_id', $player->id)
                    ->where('season_id', $seasonId)
                    ->lockForUpdate()
                    ->first();

                if (! $roster) {
                    throw ValidationException::withMessages([
                        'jersey_number' => ['The player has no roster assignment for the selected season.'],
                    ]);
                }

                $roster->jersey_number = $request->input('jersey_number');
                $roster->save();
                $jerseyNumber = $roster->jersey_number;
            }

            $birthDate = $player->birth_date ? Carbon::parse($player->birth_date) : null;

            return [
                'id' => $player->id,
                'first_name' => $player->first_name,
                'last_name' => $player->last_name,
                'name' => $player->name,
                'birth_date' => $birthDate?->toDateString(),
                'age' => $birthDate?->age ?? $player->age,
                'height' => $player->height,
                'height_inches' => $player->height_inches,
                'weight' => $player->weight_lbs,
                'weight_lbs' => $player->weight_lbs,
                'position' => $player->position,
                'jersey_number' => $updateJersey ? $jerseyNumber : null,
            ];
        });

        return response()->json(['message' => 'Player details updated', 'player' => $playerData]);
    }

    public function updateRatings(Request $request, int $id): JsonResponse
    {
        $player = Player::query()->findOrFail($id);
        $rules = ['season_id' => ['required', 'integer', 'exists:seasons,id']];
        foreach (PlayerRating::RATING_KEYS as $key) {
            $rules[$key] = ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'];
        }
        $data = $request->validate($rules);
        $seasonId = $data['season_id'];
        unset($data['season_id']);
        $attributes = [];
        foreach (PlayerRating::RATING_FIELD_MAP as $key => $column) {
            if (array_key_exists($key, $data)) {
                $attributes[$column] = $data[$key];
            }
        }

        $rating = DB::transaction(function () use ($player, $seasonId, $attributes) {
            $rating = PlayerRating::query()->firstOrNew([
                'player_id' => $player->id,
                'season_id' => $seasonId,
            ]);
            $rating->fill($attributes);
            $rating->save();

            return $rating;
        });

        return response()->json([
            'message' => 'Ratings updated',
            'player_id' => $player->id,
            'season_id' => $seasonId,
            'ratings' => $rating->toRatingsArray(),
        ]);
    }
}
