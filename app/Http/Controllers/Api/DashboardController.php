<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractYear;
use App\Models\DraftPick;
use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use App\Models\TeamRoster;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin(): JsonResponse
    {
        $seasonId = Season::current()?->id;
        $rostered = TeamRoster::query()
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->pluck('player_id');

        return response()->json([
            'teams_count' => Team::query()->count(),
            'teams' => Team::query()->count(),
            'players_count' => Player::query()->count(),
            'players' => Player::query()->count(),
            'free_agents_count' => Player::query()->whereNotIn('id', $rostered)->count(),
            'free_agents' => Player::query()->whereNotIn('id', $rostered)->count(),
            'transactions_count' => Transaction::query()->count(),
            'transactions' => Transaction::query()->count(),
        ]);
    }

    public function team(Request $request): JsonResponse
    {
        $user = $request->user();
        $teamId = $user?->team_id;
        $seasonId = Season::current()?->id;

        if (! $teamId) {
            return response()->json([
                'message' => 'No team linked to this account.',
                'roster_count' => 0,
                'cap_used' => 0,
                'draft_picks_count' => 0,
                'record' => '—',
            ]);
        }

        $rosterCount = TeamRoster::query()
            ->where('team_id', $teamId)
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->count();

        $contractIds = Contract::query()
            ->where('team_id', $teamId)
            ->where('type', 'player')
            ->where('is_active', true)
            ->pluck('id');

        $capUsed = $seasonId
            ? ContractYear::query()->whereIn('contract_id', $contractIds)->where('season_id', $seasonId)->sum('salary')
            : Contract::query()->whereIn('id', $contractIds)->sum('total_salary');

        $picks = DraftPick::query()
            ->where('team_id', $teamId)
            ->where(fn ($q) => $q->where('is_future', true)->orWhereNull('player_id'))
            ->count();

        $standing = Standing::query()
            ->where('team_id', $teamId)
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->first();

        $wins = $standing?->wins ?? 0;
        $losses = $standing?->losses ?? 0;

        return response()->json([
            'roster_count' => $rosterCount,
            'roster' => $rosterCount,
            'cap_used' => number_format((int) $capUsed),
            'salary_total' => number_format((int) $capUsed),
            'draft_picks_count' => $picks,
            'draft_picks' => $picks,
            'wins' => $wins,
            'losses' => $losses,
            'record' => "{$wins}–{$losses}",
        ]);
    }

    public function fan(): JsonResponse
    {
        $seasonId = Season::current()?->id;

        return response()->json([
            'teams_count' => Team::query()->count(),
            'teams' => Team::query()->count(),
            'games_count' => Game::query()
                ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
                ->where('status', 'final')
                ->count(),
            'games' => Game::query()
                ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
                ->where('status', 'final')
                ->count(),
        ]);
    }
}
