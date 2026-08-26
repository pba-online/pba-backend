<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DraftPick;
use App\Models\Season;
use App\Models\TeamRoster;
use App\Models\Trade;
use App\Models\TradeDraftPick;
use App\Models\TradePlayer;
use App\Models\TradeTeam;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TradeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Support both frontend payload and explicit contract payload
        $teamIds = $request->input('team_ids', $request->input('teams', []));
        $moves = $request->input('moves', []);
        $pickMoves = $request->input('pick_moves', []);
        $playersPayload = $request->input('players', []);
        $picksPayload = $request->input('draft_picks', []);
        $description = $request->input('description');

        if (! is_array($teamIds) || count($teamIds) < 2 || count($teamIds) > 4) {
            return response()->json(['message' => 'Select 2 to 4 teams'], 422);
        }

        $teamIds = array_values(array_unique(array_map('intval', $teamIds)));
        $season = Season::current();
        $seasonId = $season?->id;

        // Normalize frontend format (players/draft_picks with to_team_id only)
        if (empty($moves) && ! empty($playersPayload)) {
            foreach ($playersPayload as $row) {
                $playerId = (int) ($row['player_id'] ?? 0);
                $toTeamId = (int) ($row['to_team_id'] ?? 0);
                if (! $playerId || ! $toTeamId) {
                    continue;
                }
                $fromTeamId = (int) ($row['from_team_id'] ?? 0);
                if (! $fromTeamId && $seasonId) {
                    $fromTeamId = (int) TeamRoster::query()
                        ->where('player_id', $playerId)
                        ->where('season_id', $seasonId)
                        ->value('team_id');
                }
                if ($fromTeamId && $toTeamId && $fromTeamId !== $toTeamId) {
                    $moves[] = [
                        'player_id' => $playerId,
                        'from_team_id' => $fromTeamId,
                        'to_team_id' => $toTeamId,
                    ];
                }
            }
        }

        if (empty($pickMoves) && ! empty($picksPayload)) {
            foreach ($picksPayload as $row) {
                $pickId = (int) ($row['draft_pick_id'] ?? 0);
                $toTeamId = (int) ($row['to_team_id'] ?? 0);
                if (! $pickId || ! $toTeamId) {
                    continue;
                }
                $fromTeamId = (int) ($row['from_team_id'] ?? DraftPick::query()->where('id', $pickId)->value('team_id'));
                if ($fromTeamId && $toTeamId && $fromTeamId !== $toTeamId) {
                    $pickMoves[] = [
                        'draft_pick_id' => $pickId,
                        'from_team_id' => $fromTeamId,
                        'to_team_id' => $toTeamId,
                    ];
                }
            }
        }

        if (empty($moves) && empty($pickMoves)) {
            return response()->json(['message' => 'Trade must include players or draft picks'], 422);
        }

        $trade = DB::transaction(function () use ($request, $teamIds, $moves, $pickMoves, $description, $seasonId) {
            $trade = Trade::query()->create([
                'season_id' => $seasonId,
                'description' => $description,
                'status' => 'completed',
                'created_by' => $request->user()?->id,
            ]);

            foreach ($teamIds as $teamId) {
                TradeTeam::query()->create([
                    'trade_id' => $trade->id,
                    'team_id' => $teamId,
                ]);
            }

            foreach ($moves as $move) {
                $playerId = (int) $move['player_id'];
                $from = (int) $move['from_team_id'];
                $to = (int) $move['to_team_id'];

                TradePlayer::query()->create([
                    'trade_id' => $trade->id,
                    'player_id' => $playerId,
                    'from_team_id' => $from,
                    'to_team_id' => $to,
                ]);

                if ($seasonId) {
                    TeamRoster::query()
                        ->where('player_id', $playerId)
                        ->where('season_id', $seasonId)
                        ->update(['team_id' => $to]);
                }

                $tx = Transaction::query()->create([
                    'season_id' => $seasonId,
                    'type' => 'trade',
                    'from_team_id' => $from,
                    'to_team_id' => $to,
                    'date' => now()->toDateString(),
                    'notes' => $description,
                    'description' => $description,
                    'created_by' => $request->user()?->id,
                ]);
                $tx->players()->attach($playerId);
            }

            foreach ($pickMoves as $move) {
                $pickId = (int) $move['draft_pick_id'];
                $from = (int) $move['from_team_id'];
                $to = (int) $move['to_team_id'];

                TradeDraftPick::query()->create([
                    'trade_id' => $trade->id,
                    'draft_pick_id' => $pickId,
                    'from_team_id' => $from,
                    'to_team_id' => $to,
                ]);

                DraftPick::query()->where('id', $pickId)->update(['team_id' => $to]);
            }

            return $trade->load(['teams.team', 'players.player', 'draftPicks.draftPick']);
        });

        return response()->json([
            'message' => 'Trade completed',
            'trade' => $trade,
        ], 201);
    }
}
