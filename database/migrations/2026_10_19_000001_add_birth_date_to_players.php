<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('players', 'birth_date')) {
            Schema::table('players', function (Blueprint $table) {
                $table->date('birth_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('players', 'birth_date')) {
            Schema::table('players', function (Blueprint $table) {
                $table->dropColumn('birth_date');
            });
        }
    }
};