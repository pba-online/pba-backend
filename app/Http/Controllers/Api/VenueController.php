<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamOwner;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;

class VenueController extends Controller
{
    public function venues(): JsonResponse
    {
        $venues = Venue::query()
            ->with('team')
            ->orderBy('homecourt')
            ->get()
            ->map(fn (Venue $v) => [
                'id' => $v->id,
                'homecourt' => $v->homecourt,
                'city' => $v->city,
                'capacity' => $v->capacity,
                'team_id' => $v->team_id,
                'team' => $v->team ? ['id' => $v->team->id, 'name' => $v->team->name] : null,
                'team_name' => $v->team?->name,
            ]);

        return response()->json($venues);
    }

    public function owners(): JsonResponse
    {
        $owners = TeamOwner::query()
            ->with('team')
            ->orderBy('owner_name')
            ->get()
            ->map(fn (TeamOwner $o) => [
                'id' => $o->id,
                'owner_name' => $o->owner_name,
                'company' => $o->company,
                'team_id' => $o->team_id,
                'team' => $o->team ? ['id' => $o->team->id, 'name' => $o->team->name] : null,
                'team_name' => $o->team?->name,
            ]);

        return response()->json($owners);
    }

    // Combined view: one row per team with venue + ownership info together.
    public function homecourt(): JsonResponse
    {
        $teams = Team::query()
            ->with(['venue', 'owner', 'division'])
            ->orderBy('name')
            ->get()
            ->map(fn (Team $t) => [
                'id' => $t->id,
                'team_name' => $t->name,
                'division_id' => $t->division_id,
                'division_name' => $t->division?->name,
                'homecourt' => $t->venue?->homecourt,
                'capacity' => $t->venue?->capacity,
                'city' => $t->venue?->city ?? $t->city,
                'owner_name' => $t->owner?->owner_name,
                'company' => $t->owner?->company,
            ]);

        return response()->json($teams);
    }
}