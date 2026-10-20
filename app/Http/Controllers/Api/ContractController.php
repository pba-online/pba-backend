<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->get('type', 'player');
        $teamId = $request->integer('team_id') ?: null;

        if ($type === 'coach') {
            return response()->json([]);
        }

        $contracts = DB::table('contracts as c')
            ->leftJoin('teams as t', 't.id', '=', 'c.team_id')
            ->leftJoin('players as p', 'p.id', '=', 'c.player_id')
            ->leftJoin('seasons as start_season', 'start_season.id', '=', 'c.start_season_id')
            ->leftJoin('seasons as end_season', 'end_season.id', '=', 'c.end_season_id')
            ->where('c.status', 'active')
            ->whereNotNull('c.player_id')
            ->when($teamId, fn ($query) => $query->where('c.team_id', $teamId))
            ->orderBy('c.team_id')
            ->orderBy('c.id')
            ->get([
                'c.id',
                'c.team_id',
                'c.player_id',
                'c.start_season_id',
                'c.end_season_id',
                'c.total_salary',
                'c.contract_type',
                't.name as team_name',
                'p.first_name',
                'p.last_name',
                'start_season.season_year as start_season_year',
                'end_season.season_year as end_season_year',
            ]);

        $yearRows = DB::table('contract_years')
            ->whereIn('contract_id', $contracts->pluck('id'))
            ->get()
            ->groupBy('contract_id');

        $data = $contracts->map(function ($contract) use ($yearRows) {
            $salaryYears = collect();
            foreach ($yearRows->get($contract->id, collect()) as $row) {
                for ($yearNumber = 1; $yearNumber <= 5; $yearNumber++) {
                    $column = 'year_'.$yearNumber;
                    $salary = $row->{$column};
                    if ($salary === null && $yearNumber === 1) {
                        $salary = $row->salary;
                    }
                    if ($salary === null) {
                        continue;
                    }

                    $seasonLabel = $this->seasonLabel($contract->start_season_year, $yearNumber);
                    $salaryYears->push([
                        'year' => $seasonLabel,
                        'season' => $seasonLabel,
                        'season_year' => $seasonLabel,
                        'salary' => $salary,
                        'amount' => $salary,
                        'year_number' => $yearNumber,
                        'player_option' => (bool) $row->player_option,
                        'team_option' => (bool) $row->team_option,
                    ]);
                }
            }

            $name = trim(($contract->first_name ?? '').' '.($contract->last_name ?? ''));
            $person = $name === '' ? null : [
                'id' => $contract->player_id,
                'name' => $name,
                'first_name' => $contract->first_name,
                'last_name' => $contract->last_name,
            ];

            return [
                'id' => $contract->id,
                'team_id' => $contract->team_id,
                'team' => $contract->team_id ? ['id' => $contract->team_id, 'name' => $contract->team_name] : null,
                'team_name' => $contract->team_name,
                'type' => 'player',
                'contract_type' => $contract->contract_type,
                'status' => 'active',
                'player' => $person,
                'coach' => null,
                'name' => $name ?: '—',
                'person_name' => $name ?: '—',
                'start_season_id' => $contract->start_season_id,
                'end_season_id' => $contract->end_season_id,
                'start_season_year' => $contract->start_season_year,
                'end_season_year' => $contract->end_season_year,
                'years_count' => $salaryYears->count(),
                'years' => $salaryYears->values(),
                'contract_years' => $salaryYears->values(),
                'total_salary' => $contract->total_salary,
                'total' => $contract->total_salary,
                'full_contract_salary' => $contract->total_salary,
            ];
        });

        return response()->json($data->values());
    }

    private function seasonLabel(?string $startSeason, int $offset): string
    {
        if (! $startSeason) {
            return 'Year '.$offset;
        }

        if (preg_match('/^(\d{4})-(\d{4})$/', $startSeason, $matches)) {
            $start = (int) $matches[1] + $offset - 1;
            $end = (int) $matches[2] + $offset - 1;

            return $start.'-'.$end;
        }

        return $offset === 1 ? $startSeason : $startSeason.' (Year '.$offset.')';
    }
}
