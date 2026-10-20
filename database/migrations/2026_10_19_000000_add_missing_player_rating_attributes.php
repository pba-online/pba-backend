<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_ratings', function (Blueprint $table) {
            if (! Schema::hasColumn('player_ratings', 'interior_defense')) {
                $table->unsignedTinyInteger('interior_defense')->default(0);
            }
            if (! Schema::hasColumn('player_ratings', 'offensive_rebound')) {
                $table->unsignedTinyInteger('offensive_rebound')->default(0);
            }
            if (! Schema::hasColumn('player_ratings', 'consistency')) {
                $table->unsignedTinyInteger('consistency')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_ratings', function (Blueprint $table) {
            if (Schema::hasColumn('player_ratings', 'interior_defense')) {
                $table->dropColumn('interior_defense');
            }
            if (Schema::hasColumn('player_ratings', 'offensive_rebound')) {
                $table->dropColumn('offensive_rebound');
            }
            if (Schema::hasColumn('player_ratings', 'consistency')) {
                $table->dropColumn('consistency');
            }
        });
    }
};