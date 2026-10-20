<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\SeasonController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeasonCurrentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('season_year');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('seasons');

        parent::tearDown();
    }

    public function test_checking_a_season_makes_it_the_only_current_season(): void
    {
        $firstSeasonId = DB::table('seasons')->insertGetId([
            'season_year' => '2024-2025',
            'is_current' => true,
        ]);
        $secondSeasonId = DB::table('seasons')->insertGetId([
            'season_year' => '2025-2026',
            'is_current' => false,
        ]);

        $request = Request::create('/api/seasons/'.$secondSeasonId.'/current', 'PUT', ['is_current' => true]);
        (new SeasonController())->setCurrent($request, $secondSeasonId);

        $this->assertDatabaseHas('seasons', ['id' => $firstSeasonId, 'is_current' => false]);
        $this->assertDatabaseHas('seasons', ['id' => $secondSeasonId, 'is_current' => true]);
    }

    public function test_unchecking_current_season_clears_the_current_selection(): void
    {
        $seasonId = DB::table('seasons')->insertGetId([
            'season_year' => '2025-2026',
            'is_current' => true,
        ]);

        $request = Request::create('/api/seasons/'.$seasonId.'/current', 'PUT', ['is_current' => false]);
        (new SeasonController())->setCurrent($request, $seasonId);

        $this->assertDatabaseHas('seasons', ['id' => $seasonId, 'is_current' => false]);
    }
}