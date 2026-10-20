<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PlayerController;
use App\Models\Player;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlayerRatingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('height')->nullable();
            $table->unsignedSmallInteger('weight_lbs')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });

        Schema::create('team_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id');
            $table->foreignId('season_id');
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->timestamps();
        });

        Schema::create('player_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id');
            $table->foreignId('season_id');
            foreach (array_unique(array_values(\App\Models\PlayerRating::RATING_FIELD_MAP)) as $key) {
                $table->unsignedTinyInteger($key)->default(0);
            }
            $table->timestamps();
            $table->unique(['player_id', 'season_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('player_ratings');
        Schema::dropIfExists('team_rosters');
        Schema::dropIfExists('players');
        Schema::dropIfExists('seasons');

        parent::tearDown();
    }

    public function test_rating_update_targets_the_requested_season_and_accepts_100(): void
    {
        $player = Player::query()->create(['first_name' => 'Sam', 'last_name' => 'Example']);
        $firstSeasonId = DB::table('seasons')->insertGetId(['name' => 'Season One']);
        $secondSeasonId = DB::table('seasons')->insertGetId(['name' => 'Season Two']);
        DB::table('player_ratings')->insert([
            'player_id' => $player->id,
            'season_id' => $firstSeasonId,
            'overall' => 75,
        ]);

        $request = Request::create('/api/players/'.$player->id.'/ratings', 'PUT', [
            'season_id' => $secondSeasonId,
            'overall' => 100,
            'inside_scoring' => 82,
            'interior_defense' => 71,
            'offensive_rebound' => 64,
            'consistency' => 88,
        ]);
        $response = (new PlayerController())->updateRatings($request, $player->id);

        $this->assertSame(100, $response->getData(true)['ratings']['overall']);
        $this->assertSame(82, $response->getData(true)['ratings']['inside_scoring']);
        $this->assertSame(71, $response->getData(true)['ratings']['interior_defense']);
        $this->assertSame(64, $response->getData(true)['ratings']['offensive_rebound']);
        $this->assertSame(88, $response->getData(true)['ratings']['consistency']);
        $this->assertDatabaseHas('player_ratings', [
            'player_id' => $player->id,
            'season_id' => $firstSeasonId,
            'overall' => 75,
        ]);
        $this->assertDatabaseHas('player_ratings', [
            'player_id' => $player->id,
            'season_id' => $secondSeasonId,
            'overall' => 100,
        ]);
    }

    public function test_rating_update_rejects_values_above_100(): void
    {
        $player = Player::query()->create(['first_name' => 'Sam', 'last_name' => 'Example']);
        $seasonId = DB::table('seasons')->insertGetId(['name' => 'Season']);
        $request = Request::create('/api/players/'.$player->id.'/ratings', 'PUT', [
            'season_id' => $seasonId,
            'overall' => 101,
        ]);

        $this->expectException(ValidationException::class);
        (new PlayerController())->updateRatings($request, $player->id);
    }

    public function test_admin_player_update_saves_profile_fields_and_season_jersey_number(): void
    {
        $player = Player::query()->create(['first_name' => 'Sam', 'last_name' => 'Example']);
        $seasonId = DB::table('seasons')->insertGetId(['name' => 'Season']);
        DB::table('team_rosters')->insert([
            'player_id' => $player->id,
            'season_id' => $seasonId,
            'jersey_number' => 8,
        ]);

        $request = Request::create('/api/players/'.$player->id, 'PUT', [
            'season_id' => $seasonId,
            'birth_date' => '2000-01-15',
            'height_inches' => 77,
            'weight_lbs' => 215,
            'position' => 'PG/SG',
            'jersey_number' => 24,
        ]);
        $response = (new PlayerController())->update($request, $player->id);

        $this->assertSame('2000-01-15', $response->getData(true)['player']['birth_date']);
        $this->assertSame(77, $response->getData(true)['player']['height_inches']);
        $this->assertSame('PG/SG', $response->getData(true)['player']['position']);
        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'birth_date' => '2000-01-15',
            'height' => "6'5",
            'weight_lbs' => 215,
            'position' => 'PG/SG',
        ]);
        $this->assertDatabaseHas('team_rosters', [
            'player_id' => $player->id,
            'season_id' => $seasonId,
            'jersey_number' => 24,
        ]);
    }
}