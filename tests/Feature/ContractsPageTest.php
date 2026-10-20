<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ContractController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
        });
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('season_year');
        });
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('player_id')->nullable();
            $table->foreignId('start_season_id')->nullable();
            $table->foreignId('end_season_id')->nullable();
            $table->decimal('total_salary', 12, 2)->default(0);
            $table->string('contract_type');
            $table->string('status');
        });
        Schema::create('contract_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id');
            $table->decimal('salary', 12, 2)->default(0);
            $table->boolean('player_option')->default(false);
            $table->boolean('team_option')->default(false);
            for ($year = 1; $year <= 5; $year++) {
                $table->decimal('year_'.$year, 12, 2)->nullable();
            }
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contract_years');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('players');
        Schema::dropIfExists('teams');

        parent::tearDown();
    }

    public function test_contracts_use_live_schema_columns_and_yearly_salary_fields(): void
    {
        $teamId = DB::table('teams')->insertGetId(['name' => 'Harbor Hawks']);
        $playerId = DB::table('players')->insertGetId(['first_name' => 'Jamie', 'last_name' => 'Reed']);
        $seasonId = DB::table('seasons')->insertGetId(['season_year' => '2025-2026']);
        $contractId = DB::table('contracts')->insertGetId([
            'team_id' => $teamId,
            'player_id' => $playerId,
            'start_season_id' => $seasonId,
            'end_season_id' => $seasonId,
            'total_salary' => 1800000,
            'contract_type' => 'standard',
            'status' => 'active',
        ]);
        DB::table('contract_years')->insert([
            'contract_id' => $contractId,
            'salary' => 1000000,
            'year_1' => 1000000,
            'year_2' => 800000,
            'player_option' => false,
            'team_option' => true,
        ]);

        $response = (new ContractController())->index(Request::create('/api/contracts', 'GET', ['type' => 'player']));
        $contract = $response->getData(true)[0];

        $this->assertSame('Jamie Reed', $contract['name']);
        $this->assertSame('Harbor Hawks', $contract['team']['name']);
        $this->assertSame(2, $contract['years_count']);
        $this->assertSame('2025-2026', $contract['years'][0]['year']);
        $this->assertSame('2026-2027', $contract['years'][1]['year']);
        $this->assertEquals(800000, $contract['years'][1]['salary']);
        $this->assertEquals(1800000, $contract['total_salary']);
    }
}