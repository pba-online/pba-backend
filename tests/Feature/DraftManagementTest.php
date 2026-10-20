<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\DraftController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DraftManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('season_year');
        });
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('position')->nullable();
            $table->string('status')->default('free_agent');
        });
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id');
            $table->unsignedSmallInteger('draft_year')->unique();
            $table->string('name');
            $table->string('status')->default('setup');
            $table->timestampTz('started_at')->nullable();
            $table->timestamps();
        });
        Schema::create('draft_picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id');
            $table->unsignedTinyInteger('round_number');
            $table->unsignedSmallInteger('pick_number');
            $table->foreignId('original_team_id');
            $table->foreignId('current_team_id');
            $table->foreignId('player_id')->nullable();
            $table->string('status')->default('available');
            $table->unique(['draft_id', 'pick_number']);
            $table->timestampsTz();
        });
        Schema::create('team_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('player_id');
            $table->foreignId('season_id');
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        DB::table('seasons')->insert(['season_year' => '2026-2027']);
        for ($team = 1; $team <= 24; $team++) {
            DB::table('teams')->insert(['name' => 'Team '.$team]);
        }
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('team_rosters');
        Schema::dropIfExists('draft_picks');
        Schema::dropIfExists('drafts');
        Schema::dropIfExists('players');
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('teams');

        parent::tearDown();
    }

    public function test_draft_creation_creates_two_rounds_of_24_picks_and_can_start(): void
    {
        $controller = new DraftController();
        $request = Request::create('/api/drafts', 'POST', [
            'draft_year' => 2027,
            'season_id' => 1,
        ]);
        $response = $controller->store($request);
        $draftId = $response->getData(true)['draft']['id'];

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(48, DB::table('draft_picks')->where('draft_id', $draftId)->count());
        $this->assertSame(24, DB::table('draft_picks')->where('draft_id', $draftId)->where('round_number', 1)->count());
        $this->assertSame(24, DB::table('draft_picks')->where('draft_id', $draftId)->where('round_number', 2)->count());

        $controller->start($draftId);
        $this->assertDatabaseHas('drafts', ['id' => $draftId, 'status' => 'active']);
    }

    public function test_drafted_player_moves_to_roster_and_pick_can_be_read(): void
    {
        $controller = new DraftController();
        $created = $controller->store(Request::create('/api/drafts', 'POST', [
            'draft_year' => 2027,
            'season_id' => 1,
        ]))->getData(true);
        $draftId = $created['draft']['id'];
        $pick = DB::table('draft_picks')->where('draft_id', $draftId)->first();
        $playerId = DB::table('players')->insertGetId([
            'first_name' => 'Rookie',
            'last_name' => 'Player',
            'position' => 'PG/SG',
            'status' => 'free_agent',
        ]);
        $controller->start($draftId);

        $response = $controller->updatePick(Request::create('/api/drafts/'.$draftId.'/picks/'.$pick->id, 'PUT', [
            'current_team_id' => $pick->current_team_id,
            'player_id' => $playerId,
        ]), $draftId, $pick->id);

        $this->assertSame('Rookie Player', $response->getData(true)['pick']['player_name']);
        $this->assertDatabaseHas('team_rosters', [
            'player_id' => $playerId,
            'team_id' => $pick->current_team_id,
            'season_id' => 1,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('draft_picks', [
            'id' => $pick->id,
            'player_id' => $playerId,
            'status' => 'selected',
        ]);

        $newTeamId = DB::table('teams')->where('id', '!=', $pick->current_team_id)->value('id');
        $controller->updatePick(Request::create('/api/drafts/'.$draftId.'/picks/'.$pick->id, 'PUT', [
            'current_team_id' => $newTeamId,
        ]), $draftId, $pick->id);
        $this->assertDatabaseHas('draft_picks', ['id' => $pick->id, 'player_id' => $playerId]);
        $this->assertDatabaseHas('team_rosters', [
            'player_id' => $playerId,
            'team_id' => $newTeamId,
            'season_id' => 1,
        ]);
    }
}