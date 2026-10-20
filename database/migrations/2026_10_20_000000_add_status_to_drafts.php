<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drafts', function (Blueprint $table) {
            if (! Schema::hasColumn('drafts', 'status')) {
                $table->string('status', 20)->default('setup');
            }
            if (! Schema::hasColumn('drafts', 'started_at')) {
                $table->timestampTz('started_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('drafts', function (Blueprint $table) {
            if (Schema::hasColumn('drafts', 'started_at')) {
                $table->dropColumn('started_at');
            }
            if (Schema::hasColumn('drafts', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};