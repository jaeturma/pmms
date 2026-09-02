<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_scenarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meet_id')->constrained()->restrictOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('request_token')->unique();
            $table->string('name');
            $table->string('template', 30)->default('head_to_head');
            $table->timestamps();
        });

        Schema::table('events', fn (Blueprint $table) => $table->foreignId('demo_scenario_id')->nullable()->index()->constrained()->restrictOnDelete());
        Schema::table('event_schedules', fn (Blueprint $table) => $table->foreignId('demo_scenario_id')->nullable()->index()->constrained()->restrictOnDelete());
        Schema::table('matches', fn (Blueprint $table) => $table->foreignId('demo_scenario_id')->nullable()->index()->constrained()->restrictOnDelete());
        Schema::table('event_results', fn (Blueprint $table) => $table->foreignId('demo_scenario_id')->nullable()->index()->constrained()->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('event_results', fn (Blueprint $table) => $table->dropConstrainedForeignId('demo_scenario_id'));
        Schema::table('matches', fn (Blueprint $table) => $table->dropConstrainedForeignId('demo_scenario_id'));
        Schema::table('event_schedules', fn (Blueprint $table) => $table->dropConstrainedForeignId('demo_scenario_id'));
        Schema::table('events', fn (Blueprint $table) => $table->dropConstrainedForeignId('demo_scenario_id'));
        Schema::dropIfExists('demo_scenarios');
    }
};
