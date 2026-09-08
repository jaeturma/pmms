<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A match roster row may now stand on a hand-typed name instead of a real
 * Entry — the operator's fallback when a team's registration link is
 * missing or broken at scoreboard time. `entry_id` becomes nullable and
 * `manual_name` carries the free-text player. The `(match_id, entry_id)`
 * unique index still blocks rostering the same real Entry twice; MySQL
 * treats each NULL as distinct, so any number of manual players can share
 * a match.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_roster_players', function (Blueprint $table): void {
            $table->string('manual_name', 60)->nullable()->after('entry_id');
        });

        Schema::table('match_roster_players', function (Blueprint $table): void {
            $table->foreignId('entry_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('match_roster_players')->whereNull('entry_id')->delete();

        Schema::table('match_roster_players', function (Blueprint $table): void {
            $table->foreignId('entry_id')->nullable(false)->change();
            $table->dropColumn('manual_name');
        });
    }
};
