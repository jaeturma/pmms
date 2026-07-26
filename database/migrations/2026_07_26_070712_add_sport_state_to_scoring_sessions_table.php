<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_sessions', function (Blueprint $table) {
            $table->json('sport_state')->nullable()->after('status_note');
        });
    }

    public function down(): void
    {
        Schema::table('scoring_sessions', function (Blueprint $table) {
            $table->dropColumn('sport_state');
        });
    }
};
