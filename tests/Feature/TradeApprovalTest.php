<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TradeController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TradeApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('team_rosters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('season_id');
            $table->timestamps();
        });
        Schema::create('draft_picks', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->unsignedTinyInteger('round')->default(1);
            $table->unsignedSmallInteger('pick_number')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('original_team_id')->nullable();
            $table->unsignedBigInteger('player_id')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_future')->default(false);
            $table->timestamps();
        });
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('season_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('completed');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('trade_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_id');
            $table->unsignedBigInteger('team_id');
            $table->timestamps();
        });
        Schema::create('trade_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('from_team_id');
            $table->unsignedBigInteger('to_team_id');
            $table->timestamps();
        });
        Schema::create('trade_draft_picks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trade_id');
            $table->unsignedBigInteger('draft_pick_id');
            $table->unsignedBigInteger('from_team_id');
            $table->unsignedBigInteger('to_team_id');
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('season_id')->nullable();
            $table->string('type');
            $table->unsignedBigInteger('from_team_id')->nullable();
            $table->unsignedBigInteger('to_team_id')->nullable();
            $table->date('date')->nullable();
            $table->text('notes')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('transaction_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('player_id');
            $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password_hash')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'transaction_players',
            'transactions',
            'trade_draft_picks',
            'trade_players',
            'trade_teams',
            'trades',
            'draft_picks',
            'team_rosters',
            'players',
            'seasons',
            'teams',
            'accounts',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_four_team_proposal_stays_pending_until_admin_approval(): void
    {
        [$teamIds, $seasonId, $playerIds, $pickIds, $teamUser] = $this->createLeagueAssets();
        $controller = new TradeController();
        $request = Request::create('/api/trades', 'POST', [
            'teams' => $teamIds,
            'players' => [
                ['player_id' => $playerIds[0], 'to_team_id' => $teamIds[1]],
                ['player_id' => $playerIds[1], 'to_team_id' => $teamIds[3]],
            ],
            'draft_picks' => [
                ['draft_pick_id' => $pickIds[0], 'to_team_id' => $teamIds[1]],
                ['draft_pick_id' => $pickIds[1], 'to_team_id' => $teamIds[3]],
            ],
        ]);
        $request->setUserResolver(fn () => $teamUser);

        $response = $controller->store($request);
        $tradeId = $response->getData(true)['trade']['id'];

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('trades', ['id' => $tradeId, 'status' => 'pending']);
        $this->assertDatabaseHas('team_rosters', [
            'player_id' => $playerIds[0],
            'season_id' => $seasonId,
            'team_id' => $teamIds[0],
        ]);
        $this->assertDatabaseHas('draft_picks', ['id' => $pickIds[0], 'team_id' => $teamIds[0]]);
        $this->assertDatabaseCount('transactions', 0);

        $approval = $controller->approve(new Request(), $tradeId);

        $this->assertSame('approved', $approval->getData(true)['trade']['status']);
        $this->assertDatabaseHas('team_rosters', [
            'player_id' => $playerIds[0],
            'season_id' => $seasonId,
            'team_id' => $teamIds[1],
        ]);
        $this->assertDatabaseHas('draft_picks', ['id' => $pickIds[0], 'team_id' => $teamIds[1]]);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('transaction_players', 2);
    }

    public function test_declining_a_proposal_leaves_rosters_and_picks_unchanged(): void
    {
        [$teamIds, $seasonId, $playerIds, $pickIds, $teamUser] = $this->createLeagueAssets();
        $request = Request::create('/api/trades', 'POST', [
            'teams' => array_slice($teamIds, 0, 2),
            'players' => [['player_id' => $playerIds[0], 'to_team_id' => $teamIds[1]]],
            'draft_picks' => [['draft_pick_id' => $pickIds[0], 'to_team_id' => $teamIds[1]]],
        ]);
        $request->setUserResolver(fn () => $teamUser);
        $tradeId = (new TradeController())->store($request)->getData(true)['trade']['id'];

        $response = (new TradeController())->decline($tradeId);

        $this->assertSame('declined', $response->getData(true)['trade']['status']);
        $this->assertDatabaseHas('team_rosters', [
            'player_id' => $playerIds[0],
            'season_id' => $seasonId,
            'team_id' => $teamIds[0],
        ]);
        $this->assertDatabaseHas('draft_picks', ['id' => $pickIds[0], 'team_id' => $teamIds[0]]);
        $this->assertDatabaseCount('transactions', 0);
    }

    private function createLeagueAssets(): array
    {
        $teamIds = [];
        for ($index = 1; $index <= 4; $index++) {
            $teamIds[] = DB::table('teams')->insertGetId(['name' => 'Team '.$index]);
        }
        $seasonId = DB::table('seasons')->insertGetId(['name' => '2026', 'is_current' => true]);
        $playerIds = [];
        $pickIds = [];

        foreach ([0, 2] as $index => $teamIndex) {
            $playerIds[] = DB::table('players')->insertGetId([
                'first_name' => 'Player',
                'last_name' => (string) ($index + 1),
            ]);
            DB::table('team_rosters')->insert([
                'team_id' => $teamIds[$teamIndex],
                'player_id' => $playerIds[$index],
                'season_id' => $seasonId,
            ]);
            $pickIds[] = DB::table('draft_picks')->insertGetId([
                'year' => '2026',
                'round' => 1,
                'team_id' => $teamIds[$teamIndex],
            ]);
        }

        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Team account',
            'role' => 'team',
            'team_id' => $teamIds[0],
        ]);
        $teamUser = User::query()->findOrFail($accountId);

        return [$teamIds, $seasonId, $playerIds, $pickIds, $teamUser];
    }
}
