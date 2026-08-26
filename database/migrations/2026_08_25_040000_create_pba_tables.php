<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('year')->nullable();
            $table->boolean('is_current')->default(false);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('conferences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation', 10)->nullable();
            $table->string('city')->nullable();
            $table->foreignId('conference_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('logo_url')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('fan')->after('password');
            $table->foreignId('team_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('full_name')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('team_id');
        });

        Schema::create('team_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('company')->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('photo_url')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->unsignedSmallInteger('height_inches')->nullable();
            $table->unsignedSmallInteger('weight_lbs')->nullable();
            $table->string('position', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('player_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('overall')->default(0);
            $table->unsignedTinyInteger('inside')->default(0);
            $table->unsignedTinyInteger('mid_range')->default(0);
            $table->unsignedTinyInteger('three_point')->default(0);
            $table->unsignedTinyInteger('free_throw')->default(0);
            $table->unsignedTinyInteger('dunk')->default(0);
            $table->unsignedTinyInteger('layup')->default(0);
            $table->unsignedTinyInteger('passing')->default(0);
            $table->unsignedTinyInteger('ball_handle')->default(0);
            $table->unsignedTinyInteger('speed')->default(0);
            $table->unsignedTinyInteger('acceleration')->default(0);
            $table->unsignedTinyInteger('strength')->default(0);
            $table->unsignedTinyInteger('vertical')->default(0);
            $table->unsignedTinyInteger('stamina')->default(0);
            $table->unsignedTinyInteger('defense')->default(0);
            $table->unsignedTinyInteger('steal')->default(0);
            $table->unsignedTinyInteger('block')->default(0);
            $table->unsignedTinyInteger('rebound')->default(0);
            $table->unsignedTinyInteger('iq')->default(0);
            $table->timestamps();
            $table->unique(['player_id', 'season_id']);
        });

        Schema::create('team_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'player_id', 'season_id']);
        });

        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('photo_url')->nullable();
            $table->string('role')->default('Head Coach');
            $table->timestamps();
        });

        Schema::create('coach_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->string('offense_grade', 5)->nullable();
            $table->string('defense_grade', 5)->nullable();
            $table->string('player_development_grade', 5)->nullable();
            $table->string('leadership_grade', 5)->nullable();
            $table->string('overall_grade', 5)->nullable();
            $table->timestamps();
        });

        Schema::create('team_coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'coach_id', 'season_id']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('player'); // player|coach
            $table->foreignId('start_season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->foreignId('end_season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->bigInteger('total_salary')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('salary')->default(0);
            $table->unsignedTinyInteger('year_number')->nullable();
            $table->timestamps();
            $table->unique(['contract_id', 'season_id']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // sign|trade|waive|release
            $table->foreignId('from_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('to_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->date('date')->nullable();
            $table->text('notes')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transaction_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('trade_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['trade_id', 'team_id']);
        });

        Schema::create('trade_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('to_team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('draft_picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->nullable()->constrained()->nullOnDelete();
            $table->string('year');
            $table->unsignedTinyInteger('round')->default(1);
            $table->unsignedSmallInteger('pick_number')->nullable();
            $table->unsignedSmallInteger('overall_pick')->nullable();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete(); // current owner
            $table->foreignId('original_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notes')->nullable();
            $table->boolean('is_future')->default(false);
            $table->timestamps();
        });

        Schema::create('trade_draft_picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('draft_pick_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('to_team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('game_date')->nullable();
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->string('status')->default('scheduled'); // scheduled|final|postponed
            $table->timestamps();
        });

        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conference_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('losses')->default(0);
            $table->decimal('pct', 5, 3)->default(0);
            $table->decimal('gb', 5, 1)->default(0);
            $table->unsignedTinyInteger('rank')->nullable();
            $table->timestamps();
            $table->unique(['season_id', 'team_id']);
        });

        Schema::create('playoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('playoff_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playoff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conference_id')->nullable()->constrained()->nullOnDelete();
            $table->string('round'); // first_round|semifinals|conference_finals|finals
            $table->foreignId('team1_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team2_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedTinyInteger('wins_team1')->default(0);
            $table->unsignedTinyInteger('wins_team2')->default(0);
            $table->timestamps();
        });

        Schema::create('playoff_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playoff_series_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('game_number')->default(1);
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->timestamps();
        });

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('league_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conference_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('champion_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('finals_mvp_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('type')->default('conference_champion'); // conference_champion|league_champion
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_history');
        Schema::dropIfExists('awards');
        Schema::dropIfExists('playoff_games');
        Schema::dropIfExists('playoff_series');
        Schema::dropIfExists('playoffs');
        Schema::dropIfExists('standings');
        Schema::dropIfExists('games');
        Schema::dropIfExists('trade_draft_picks');
        Schema::dropIfExists('draft_picks');
        Schema::dropIfExists('drafts');
        Schema::dropIfExists('trade_players');
        Schema::dropIfExists('trade_teams');
        Schema::dropIfExists('trades');
        Schema::dropIfExists('transaction_players');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('contract_years');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('team_coaches');
        Schema::dropIfExists('coach_ratings');
        Schema::dropIfExists('coaches');
        Schema::dropIfExists('team_rosters');
        Schema::dropIfExists('player_ratings');
        Schema::dropIfExists('players');
        Schema::dropIfExists('team_owners');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['role', 'full_name', 'is_active']);
        });

        Schema::dropIfExists('teams');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('conferences');
        Schema::dropIfExists('seasons');
    }
};
