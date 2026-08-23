<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegation_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('submitted')->index();
            $table->timestamps();

            $table->unique(['delegation_id', 'event_id']);
        });

        Schema::create('team_entry_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('athlete_id')->constrained()->restrictOnDelete();
            $table->foreignId('entry_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['team_entry_id', 'athlete_id']);
            $table->unique(['team_entry_id', 'entry_id']);
        });

        Schema::table('result_placements', function (Blueprint $table) {
            $table->foreignId('team_entry_id')->nullable()->after('entry_id')->constrained()->restrictOnDelete();
            $table->index(['event_result_id', 'team_entry_id']);
        });

        Schema::table('result_placements', function (Blueprint $table) {
            $table->foreignId('entry_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('result_placements', function (Blueprint $table) {
            $table->dropForeign(['team_entry_id']);
            $table->dropIndex(['event_result_id', 'team_entry_id']);
            $table->dropColumn('team_entry_id');
        });

        Schema::dropIfExists('team_entry_members');
        Schema::dropIfExists('team_entries');
    }
};
