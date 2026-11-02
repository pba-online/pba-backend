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
use Illuminate\Validation\ValidationException;

class TradeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teams' => ['required_without:team_ids', 'array', 'min:2', 'max:4'],
            'teams.*' => ['required', 'integer', 'distinct', 'exists:teams,id'],
            'team_ids' => ['required_without:teams', 'array', 'min:2', 'max:4'],
            'team_ids.*' => ['required', 'integer', 'distinct', 'exists:teams,id'],
            'players' => ['sometimes', 'array'],
            'players.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'players.*.from_team_id' => ['sometimes', 'integer', 'exists:teams,id'],
            'players.*.to_team_id' => ['required', 'integer', 'exists:teams,id'],
            'draft_picks' => ['sometimes', 'array'],
            'draft_picks.*.draft_pick_id' => ['required', 'integer', 'exists:draft_picks,id'],
            'draft_picks.*.from_team_id' => ['sometimes', 'integer', 'exists:teams,id'],
            'draft_picks.*.to_team_id' => ['required', 'integer', 'exists:teams,id'],
            'moves' => ['sometimes', 'array'],
            'moves.*.player_id' => ['required', 'integer', 'exists:players,id'],
            'moves.*.from_team_id' => ['required', 'integer', 'exists:teams,id'],
            'moves.*.to_team_id' => ['required', 'integer', 'exists:teams,id'],
            'pick_moves' => ['sometimes', 'array'],
            'pick_moves.*.draft_pick_id' => ['required', 'integer', 'exists:draft_picks,id'],
            'pick_moves.*.from_team_id' => ['required', 'integer', 'exists:teams,id'],
            'pick_moves.*.to_team_id' => ['required', 'integer', 'exists:teams,id'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $teamIds = array_map('intval', $validated['team_ids'] ?? $validated['teams']);
        if (count(array_unique($teamIds)) !== count($teamIds)) {
            throw ValidationException::withMessages(['teams' => 'Select each team only once.']);
        }

        $user = $request->user();
        if ($user?->role === 'team' && ! in_array((int) $user->team_id, $teamIds, true)) {
            abort(403, 'Your team must be included in the proposed trade.');
        }

        $season = Season::current();
        $seasonId = $season?->id;
        $moves = [];
        $pickMoves = [];
        $seenPlayers = [];
        $seenPicks = [];

        foreach (array_merge($validated['players'] ?? [], $validated['moves'] ?? []) as $row) {
            $playerId = (int) $row['player_id'];
            $toTeamId = (int) $row['to_team_id'];
            $roster = $seasonId
                ? TeamRoster::query()
                    ->where('player_id', $playerId)
                    ->where('season_id', $seasonId)
                    ->first()
                : null;
            $fromTeamId = (int) ($roster?->team_id ?? 0);

            if (! $fromTeamId || (isset($row['from_team_id']) && (int) $row['from_team_id'] !== $fromTeamId)) {
                throw ValidationException::withMessages(['players' => 'A proposed player is not on the submitted roster.']);
            }
            $this->assertTradeMove($teamIds, $fromTeamId, $toTeamId, $seenPlayers, $playerId, 'players');
            $moves[] = [
                'player_id' => $playerId,
                'from_team_id' => $fromTeamId,
                'to_team_id' => $toTeamId,
            ];
        }

        foreach (array_merge($validated['draft_picks'] ?? [], $validated['pick_moves'] ?? []) as $row) {
            $pickId = (int) $row['draft_pick_id'];
            $toTeamId = (int) $row['to_team_id'];
            $pick = DraftPick::query()->find($pickId);
            $fromTeamId = (int) ($pick?->team_id ?? 0);

            if (! $fromTeamId || (isset($row['from_team_id']) && (int) $row['from_team_id'] !== $fromTeamId)) {
                throw ValidationException::withMessages(['draft_picks' => 'A proposed draft pick is not owned by the submitted team.']);
            }
            $this->assertTradeMove($teamIds, $fromTeamId, $toTeamId, $seenPicks, $pickId, 'draft_picks');
            $pickMoves[] = [
                'draft_pick_id' => $pickId,
                'from_team_id' => $fromTeamId,
                'to_team_id' => $toTeamId,
            ];
        }

        if ($moves === [] && $pickMoves === []) {
            throw ValidationException::withMessages(['trade' => 'Trade must include players or draft picks.']);
        }

        $trade = DB::transaction(function () use ($request, $teamIds, $moves, $pickMoves, $validated, $seasonId) {
            $trade = Trade::query()->create([
                'season_id' => $seasonId,
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
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
            }

            foreach ($pickMoves as $move) {
                TradeDraftPick::query()->create([
                    'trade_id' => $trade->id,
                    'draft_pick_id' => $move['draft_pick_id'],
                    'from_team_id' => $move['from_team_id'],
                    'to_team_id' => $move['to_team_id'],
                ]);
            }

            return $trade->load([
                'teams.team',
                'players.player',
                'players.fromTeam',
                'players.toTeam',
                'draftPicks.draftPick',
                'draftPicks.fromTeam',
                'draftPicks.toTeam',
            ]);
        });

        return response()->json([
            'message' => 'Trade submitted for admin review.',
            'trade' => $trade,
        ], 201);
    }

    public function pending(): JsonResponse
    {
        $trades = Trade::query()
            ->where('status', 'pending')
            ->with([
                'teams.team',
                'players.player',
                'players.fromTeam',
                'players.toTeam',
                'draftPicks.draftPick',
                'draftPicks.fromTeam',
                'draftPicks.toTeam',
                'creator',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Trade $trade) => $this->formatTrade($trade));

        return response()->json($trades);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $trade = DB::transaction(function () use ($request, $id) {
            $trade = Trade::query()->lockForUpdate()->findOrFail($id);
            if ($trade->status !== 'pending') {
                abort(409, 'This trade has already been reviewed.');
            }

            $movesByTeams = [];
            foreach ($trade->players as $move) {
                $roster = TeamRoster::query()
                    ->where('player_id', $move->player_id)
                    ->where('season_id', $trade->season_id)
                    ->lockForUpdate()
                    ->first();
                if (! $roster || (int) $roster->team_id !== (int) $move->from_team_id) {
                    abort(409, 'A player in this trade is no longer on the proposed team.');
                }

                $roster->update(['team_id' => $move->to_team_id]);
                $key = $move->from_team_id.':'.$move->to_team_id;
                $movesByTeams[$key] ??= [
                    'from_team_id' => $move->from_team_id,
                    'to_team_id' => $move->to_team_id,
                    'player_ids' => [],
                    'includes_pick' => false,
                ];
                $movesByTeams[$key]['player_ids'][] = $move->player_id;
            }

            foreach ($trade->draftPicks as $move) {
                $pick = DraftPick::query()->lockForUpdate()->find($move->draft_pick_id);
                if (! $pick || (int) $pick->team_id !== (int) $move->from_team_id) {
                    abort(409, 'A draft pick in this trade is no longer owned by the proposed team.');
                }

                $pick->update(['team_id' => $move->to_team_id]);
                $key = $move->from_team_id.':'.$move->to_team_id;
                $movesByTeams[$key] ??= [
                    'from_team_id' => $move->from_team_id,
                    'to_team_id' => $move->to_team_id,
                    'player_ids' => [],
                    'includes_pick' => false,
                ];
                $movesByTeams[$key]['includes_pick'] = true;
            }

            foreach ($movesByTeams as $move) {
                $transaction = Transaction::query()->create([
                    'season_id' => $trade->season_id,
                    'type' => 'trade',
                    'from_team_id' => $move['from_team_id'],
                    'to_team_id' => $move['to_team_id'],
                    'date' => now()->toDateString(),
                    'notes' => $trade->description ?: ($move['includes_pick'] ? 'Draft pick trade' : null),
                    'description' => $trade->description,
                    'created_by' => $trade->created_by,
                ]);
                if ($move['player_ids'] !== []) {
                    $transaction->players()->attach($move['player_ids']);
                }
            }

            $trade->update(['status' => 'approved']);

            return $trade->fresh()->load([
                'teams.team',
                'players.player',
                'players.fromTeam',
                'players.toTeam',
                'draftPicks.draftPick',
                'draftPicks.fromTeam',
                'draftPicks.toTeam',
                'creator',
            ]);
        });

        return response()->json([
            'message' => 'Trade approved.',
            'trade' => $this->formatTrade($trade),
        ]);
    }

    public function decline(int $id): JsonResponse
    {
        $trade = DB::transaction(function () use ($id) {
            $trade = Trade::query()->lockForUpdate()->findOrFail($id);
            if ($trade->status !== 'pending') {
                abort(409, 'This trade has already been reviewed.');
            }
            $trade->update(['status' => 'declined']);

            return $trade->fresh()->load(['teams.team', 'creator']);
        });

        return response()->json([
            'message' => 'Trade declined.',
            'trade' => $this->formatTrade($trade),
        ]);
    }

    private function assertTradeMove(array $teamIds, int $fromTeamId, int $toTeamId, array &$seenAssets, int $assetId, string $field): void
    {
        if (! in_array($fromTeamId, $teamIds, true) || ! in_array($toTeamId, $teamIds, true) || $fromTeamId === $toTeamId) {
            throw ValidationException::withMessages([$field => 'Each asset must move between two selected teams.']);
        }
        if (isset($seenAssets[$assetId])) {
            throw ValidationException::withMessages([$field => 'An asset can only appear once in a trade.']);
        }
        $seenAssets[$assetId] = true;
    }

    private function formatTrade(Trade $trade): array
    {
        return [
            'id' => $trade->id,
            'status' => $trade->status,
            'description' => $trade->description,
            'created_at' => $trade->created_at,
            'created_by' => $trade->creator?->name,
            'teams' => $trade->teams->map(fn (TradeTeam $entry) => [
                'id' => $entry->team?->id,
                'name' => $entry->team?->name,
            ])->values(),
            'players' => $trade->players->map(fn (TradePlayer $move) => [
                'id' => $move->player_id,
                'name' => $move->player?->name,
                'from_team_id' => $move->from_team_id,
                'from_team' => $move->fromTeam?->name,
                'to_team_id' => $move->to_team_id,
                'to_team' => $move->toTeam?->name,
            ])->values(),
            'draft_picks' => $trade->draftPicks->map(fn (TradeDraftPick $move) => [
                'id' => $move->draft_pick_id,
                'label' => $move->draftPick?->label,
                'from_team_id' => $move->from_team_id,
                'from_team' => $move->fromTeam?->name,
                'to_team_id' => $move->to_team_id,
                'to_team' => $move->toTeam?->name,
            ])->values(),
        ];
    }
}
