<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $conferenceId = $request->integer('conference_id') ?: null;
        $seasonId = $request->integer('season_id') ?: null;

        $teamIds = null;
        if ($conferenceId) {
            $teamIds = Team::query()->where('conference_id', $conferenceId)->pluck('id');
        }

        $rows = Transaction::query()
            ->with(['fromTeam', 'toTeam', 'players'])
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->when($teamIds, function ($q) use ($teamIds) {
                $q->where(function ($inner) use ($teamIds) {
                    $inner->whereIn('from_team_id', $teamIds)
                        ->orWhereIn('to_team_id', $teamIds);
                });
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Transaction $tx) {
                $playerNames = $tx->players
                    ->map(fn ($p) => $p->name)
                    ->filter()
                    ->implode(', ');

                return [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'transaction_type' => $tx->type,
                    'date' => optional($tx->date)?->toDateString(),
                    'transaction_date' => optional($tx->date)?->toDateString(),
                    'notes' => $tx->notes,
                    'description' => $tx->description,
                    'from_team_id' => $tx->from_team_id,
                    'to_team_id' => $tx->to_team_id,
                    'from_team' => $tx->fromTeam ? ['id' => $tx->fromTeam->id, 'name' => $tx->fromTeam->name] : null,
                    'to_team' => $tx->toTeam ? ['id' => $tx->toTeam->id, 'name' => $tx->toTeam->name] : null,
                    'from_team_name' => $tx->fromTeam?->name,
                    'to_team_name' => $tx->toTeam?->name,
                    'player_name' => $playerNames,
                    'player_names' => $playerNames,
                    'players' => $tx->players->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'first_name' => $p->first_name,
                        'last_name' => $p->last_name,
                    ]),
                ];
            });

        return response()->json($rows);
    }
}
