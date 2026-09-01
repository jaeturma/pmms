<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_roster_members', function (Blueprint $table): void {
            $table->dropForeign(['athlete_id']);
            $table->foreign('athlete_id')->references('id')->on('athletes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sport_roster_members', function (Blueprint $table): void {
            $table->dropForeign(['athlete_id']);
            $table->foreign('athlete_id')->references('id')->on('athletes')->restrictOnDelete();
        });
    }
};
