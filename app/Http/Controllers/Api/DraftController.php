<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DraftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $draftYear = $request->integer('year') ?: null;
        $picks = DB::table('draft_picks as pick')
            ->join('drafts as draft', 'draft.id', '=', 'pick.draft_id')
            ->leftJoin('teams as current_team', 'current_team.id', '=', 'pick.current_team_id')
            ->leftJoin('teams as original_team', 'original_team.id', '=', 'pick.original_team_id')
            ->leftJoin('players as player', 'player.id', '=', 'pick.player_id')
            ->when($draftYear, fn ($query) => $query->where('draft.draft_year', $draftYear))
            ->orderByDesc('draft.draft_year')
            ->orderBy('pick.pick_number')
            ->get([
                'pick.id', 'pick.draft_id', 'pick.round_number', 'pick.pick_number',
                'pick.original_team_id', 'pick.current_team_id', 'pick.player_id', 'pick.status',
                'draft.draft_year', 'draft.status as draft_status',
                'current_team.name as team_name', 'original_team.name as original_team_name',
                'player.first_name', 'player.last_name',
            ])
            ->map(fn ($pick) => $this->formatPick($pick));

        return response()->json($picks);
    }

    public function adminIndex(): JsonResponse
    {
        $drafts = DB::table('drafts as draft')
            ->leftJoin('draft_picks as pick', 'pick.draft_id', '=', 'draft.id')
            ->groupBy('draft.id')
            ->orderByDesc('draft.draft_year')
            ->get([
                'draft.id', 'draft.draft_year', 'draft.season_id', 'draft.name',
                'draft.status', 'draft.started_at', DB::raw('COUNT(pick.id) as picks_count'),
            ]);

        return response()->json($drafts);
    }

    public function show(int $id): JsonResponse
    {
        $draft = DB::table('drafts')->where('id', $id)->first();
        abort_unless($draft !== null, 404);
        $picks = $this->draftPicks($draft->id);

        return response()->json(['draft' => $draft, 'picks' => $picks]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'draft_year' => ['required', 'integer', 'min:1900', 'max:2200', 'unique:drafts,draft_year'],
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);
        $teamIds = DB::table('teams')->orderBy('id')->pluck('id');
        if ($teamIds->count() !== 24) {
            throw ValidationException::withMessages(['teams' => ['A two-round draft requires exactly 24 teams.']]);
        }

        $draft = DB::transaction(function () use ($data, $teamIds) {
            $now = now();
            $draftId = DB::table('drafts')->insertGetId([
                'season_id' => $data['season_id'],
                'draft_year' => $data['draft_year'],
                'name' => $data['name'] ?? ('Draft '.$data['draft_year']),
                'status' => 'setup',
                'started_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $picks = [];
            foreach ([1, 2] as $round) {
                foreach ($teamIds as $index => $teamId) {
                    $picks[] = [
                        'draft_id' => $draftId,
                        'round_number' => $round,
                        'pick_number' => (($round - 1) * 24) + $index + 1,
                        'original_team_id' => $teamId,
                        'current_team_id' => $teamId,
                        'player_id' => null,
                        'status' => 'available',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            DB::table('draft_picks')->insert($picks);

            return DB::table('drafts')->where('id', $draftId)->first();
        });

        return response()->json(['draft' => $draft], 201);
    }

    public function start(int $id): JsonResponse
    {
        $draft = DB::table('drafts')->where('id', $id)->first();
        abort_unless($draft !== null, 404);
        if ($draft->status !== 'setup') {
            throw ValidationException::withMessages(['draft' => ['Only a draft in setup can be started.']]);
        }
        DB::table('drafts')->where('id', $id)->update([
            'status' => 'active', 'started_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['draft' => DB::table('drafts')->where('id', $id)->first()]);
    }

    public function updatePick(Request $request, int $draftId, int $pickId): JsonResponse
    {
        $data = $request->validate([
            'current_team_id' => ['required', 'integer', 'exists:teams,id'],
            'player_id' => ['sometimes', 'nullable', 'integer', 'exists:players,id'],
        ]);
        $pick = DB::transaction(function () use ($draftId, $pickId, $data) {
            $draft = DB::table('drafts')->where('id', $draftId)->lockForUpdate()->first();
            abort_unless($draft !== null, 404);
            if ($draft->status === 'completed') {
                throw ValidationException::withMessages(['draft' => ['Completed drafts cannot be changed.']]);
            }
            $pick = DB::table('draft_picks')->where('id', $pickId)->where('draft_id', $draftId)->lockForUpdate()->first();
            abort_unless($pick !== null, 404);

            $playerId = array_key_exists('player_id', $data) ? $data['player_id'] : $pick->player_id;
            if ($playerId && $draft->status !== 'active') {
                throw ValidationException::withMessages(['player_id' => ['Start the draft before selecting players.']]);
            }
            if ($playerId && (int) $playerId !== (int) $pick->player_id) {
                if (DB::table('draft_picks')->where('draft_id', $draftId)->where('player_id', $playerId)->exists()) {
                    throw ValidationException::withMessages(['player_id' => ['This player has already been selected in this draft.']]);
                }
                if (DB::table('team_rosters')->where('player_id', $playerId)->where('season_id', $draft->season_id)->exists()) {
                    throw ValidationException::withMessages(['player_id' => ['This player is already assigned to a team for this season.']]);
                }
                if (! DB::table('players')->where('id', $playerId)->where('status', 'free_agent')->exists()) {
                    throw ValidationException::withMessages(['player_id' => ['Only free-agent players can be drafted.']]);
                }
            }

            if ($pick->player_id && (int) $pick->player_id !== (int) $playerId) {
                DB::table('team_rosters')->where('player_id', $pick->player_id)->where('season_id', $draft->season_id)->delete();
            }
            if ($playerId) {
                $roster = DB::table('team_rosters')->where('player_id', $playerId)->where('season_id', $draft->season_id)->first();
                if ($roster) {
                    DB::table('team_rosters')->where('id', $roster->id)->update([
                        'team_id' => $data['current_team_id'], 'status' => 'active', 'updated_at' => now(),
                    ]);
                } else {
                    DB::table('team_rosters')->insert([
                        'team_id' => $data['current_team_id'], 'player_id' => $playerId,
                        'season_id' => $draft->season_id, 'jersey_number' => null,
                        'start_date' => null, 'end_date' => null, 'status' => 'active',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
            DB::table('draft_picks')->where('id', $pickId)->update([
                'current_team_id' => $data['current_team_id'], 'player_id' => $playerId,
                'status' => $playerId ? 'selected' : 'available', 'updated_at' => now(),
            ]);

            return DB::table('draft_picks as pick')
                ->leftJoin('teams as current_team', 'current_team.id', '=', 'pick.current_team_id')
                ->leftJoin('teams as original_team', 'original_team.id', '=', 'pick.original_team_id')
                ->leftJoin('players as player', 'player.id', '=', 'pick.player_id')
                ->where('pick.id', $pickId)
                ->first([
                    'pick.id', 'pick.draft_id', 'pick.round_number', 'pick.pick_number',
                    'pick.original_team_id', 'pick.current_team_id', 'pick.player_id', 'pick.status',
                    'current_team.name as team_name', 'original_team.name as original_team_name',
                    'player.first_name', 'player.last_name',
                ]);
        });

        return response()->json(['pick' => $this->formatPick($pick)]);
    }

    public function availablePlayers(int $draftId): JsonResponse
    {
        $draft = DB::table('drafts')->where('id', $draftId)->first();
        abort_unless($draft !== null, 404);
        $selectedInDraft = DB::table('draft_picks')->where('draft_id', $draftId)->whereNotNull('player_id')->pluck('player_id');
        $players = DB::table('players as player')
            ->where('player.status', 'free_agent')
            ->where(function ($query) use ($draft, $selectedInDraft) {
                $query->whereNotExists(function ($subquery) use ($draft) {
                    $subquery->selectRaw('1')->from('team_rosters as roster')
                        ->whereColumn('roster.player_id', 'player.id')->where('roster.season_id', $draft->season_id);
                })->orWhereIn('player.id', $selectedInDraft);
            })
            ->orderBy('player.last_name')->orderBy('player.first_name')
            ->get(['player.id', 'player.first_name', 'player.last_name', 'player.position', 'player.birth_date'])
            ->map(fn ($player) => [
                'id' => $player->id,
                'name' => trim($player->first_name.' '.$player->last_name),
                'position' => $player->position,
                'birth_date' => $player->birth_date,
            ]);

        return response()->json($players);
    }

    public function storePlayer(Request $request, int $draftId): JsonResponse
    {
        abort_unless(DB::table('drafts')->where('id', $draftId)->exists() === true, 404);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'height' => ['nullable', 'string', 'max:20'],
            'weight_lbs' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $playerId = DB::table('players')->insertGetId([
            ...$data, 'status' => 'free_agent', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['player' => DB::table('players')->where('id', $playerId)->first()], 201);
    }

    public function ownedPicks(Request $request): JsonResponse
    {
        $teamId = $request->integer('team_id') ?: null;
        $picks = DB::table('draft_picks as pick')
            ->join('drafts as draft', 'draft.id', '=', 'pick.draft_id')
            ->leftJoin('teams as original_team', 'original_team.id', '=', 'pick.original_team_id')
            ->leftJoin('teams as current_team', 'current_team.id', '=', 'pick.current_team_id')
            ->when($teamId, fn ($query) => $query->where('pick.current_team_id', $teamId))
            ->whereNull('pick.player_id')->orderBy('draft.draft_year')->orderBy('pick.pick_number')
            ->get([
                'pick.id', 'draft.draft_year as year', 'pick.round_number as round', 'pick.pick_number',
                'pick.original_team_id', 'pick.current_team_id as team_id', 'pick.status',
                'original_team.name as original_team_name', 'current_team.name as team_name',
            ])
            ->map(fn ($pick) => [
                'id' => $pick->id, 'year' => $pick->year, 'round' => $pick->round,
                'pick' => (($pick->pick_number - 1) % 24) + 1, 'overall_pick' => $pick->pick_number,
                'label' => $pick->year.' R'.$pick->round.' #'.((($pick->pick_number - 1) % 24) + 1),
                'status' => $pick->status, 'team_id' => $pick->team_id, 'team_name' => $pick->team_name,
                'original_team' => ['id' => $pick->original_team_id, 'name' => $pick->original_team_name],
                'original_team_name' => $pick->original_team_name,
            ]);

        return response()->json($picks);
    }

    private function draftPicks(int $draftId)
    {
        return DB::table('draft_picks as pick')
            ->leftJoin('teams as current_team', 'current_team.id', '=', 'pick.current_team_id')
            ->leftJoin('teams as original_team', 'original_team.id', '=', 'pick.original_team_id')
            ->leftJoin('players as player', 'player.id', '=', 'pick.player_id')
            ->where('pick.draft_id', $draftId)->orderBy('pick.pick_number')
            ->get([
                'pick.id', 'pick.draft_id', 'pick.round_number', 'pick.pick_number',
                'pick.original_team_id', 'pick.current_team_id', 'pick.player_id', 'pick.status',
                'current_team.name as team_name', 'original_team.name as original_team_name',
                'player.first_name', 'player.last_name',
            ])
            ->map(fn ($pick) => $this->formatPick($pick));
    }

    private function formatPick(object $pick): array
    {
        $playerName = trim(($pick->first_name ?? '').' '.($pick->last_name ?? ''));
        $roundPick = (($pick->pick_number - 1) % 24) + 1;

        return [
            'id' => $pick->id, 'draft_id' => $pick->draft_id,
            'year' => $pick->draft_year ?? null, 'draft_year' => $pick->draft_year ?? null,
            'round' => $pick->round_number, 'pick' => $roundPick,
            'overall_pick' => $pick->pick_number, 'pick_number' => $pick->pick_number,
            'team_id' => $pick->current_team_id,
            'team' => $pick->current_team_id ? ['id' => $pick->current_team_id, 'name' => $pick->team_name] : null,
            'team_name' => $pick->team_name,
            'original_team' => $pick->original_team_id ? ['id' => $pick->original_team_id, 'name' => $pick->original_team_name] : null,
            'original_team_id' => $pick->original_team_id, 'original_team_name' => $pick->original_team_name,
            'player_id' => $pick->player_id,
            'player' => $pick->player_id ? ['id' => $pick->player_id, 'name' => $playerName] : null,
            'player_name' => $playerName ?: null, 'name' => $playerName ?: null,
            'status' => $pick->status, 'draft_status' => $pick->draft_status ?? null,
        ];
    }

}
