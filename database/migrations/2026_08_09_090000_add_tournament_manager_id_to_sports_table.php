<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `tournament_manager_id` is a nullable FK, not a pivot, because exactly
 * one Tournament Manager per sport is a real 1:1 constraint the schema can
 * enforce for free — unlike Technical Official's genuine many-to-many
 * `sport_user` pivot. See `Sport::tournamentManager()`/`User::managedSport()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->foreignId('tournament_manager_id')->nullable()->after('photo_upload_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_manager_id');
        });
    }
};
