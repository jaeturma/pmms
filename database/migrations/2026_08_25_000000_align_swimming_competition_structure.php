<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('event_no')->nullable()->after('code');
            $table->unsignedSmallInteger('distance_meters')->nullable()->after('distance');
            $table->string('stroke', 40)->nullable()->after('distance_meters');
            $table->unsignedTinyInteger('relay_legs')->nullable()->after('team_size');
            $table->unsignedSmallInteger('relay_leg_distance_meters')->nullable()->after('relay_legs');
            $table->unique(['sport_id', 'event_no']);
        });

        Schema::table('team_entry_members', function (Blueprint $table) {
            $table->unsignedTinyInteger('member_order')->nullable()->after('entry_id');
            $table->unique(['team_entry_id', 'member_order']);
        });

        Schema::create('sport_roster_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_sport_id')->constrained()->cascadeOnDelete();
            $table->string('level', 20);
            $table->string('gender', 10);
            $table->unsignedSmallInteger('max_athletes');
            $table->timestamps();
            $table->unique(['meet_sport_id', 'level', 'gender']);
        });

        Schema::create('sport_roster_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_sport_id')->constrained()->restrictOnDelete();
            $table->foreignId('delegation_id')->constrained()->restrictOnDelete();
            $table->foreignId('athlete_id')->constrained()->restrictOnDelete();
            $table->string('level', 20);
            $table->string('gender', 10);
            $table->timestamps();
            $table->unique(['meet_sport_id', 'athlete_id']);
            $table->index(['meet_sport_id', 'delegation_id', 'level', 'gender'], 'sport_roster_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_roster_members');
        Schema::dropIfExists('sport_roster_limits');
        Schema::table('team_entry_members', function (Blueprint $table) {
            $table->dropUnique(['team_entry_id', 'member_order']);
            $table->dropColumn('member_order');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['sport_id', 'event_no']);
            $table->dropColumn(['event_no', 'distance_meters', 'stroke', 'relay_legs', 'relay_leg_distance_meters']);
        });
    }
};
