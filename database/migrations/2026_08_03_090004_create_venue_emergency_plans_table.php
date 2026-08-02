<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venue_emergency_plans', function (Blueprint $table) {
            $table->id();
            // The existing, division-wide competition Venue catalog,
            // reused unmodified — unlike BilletingVenue, an emergency
            // plan genuinely describes the competition venue itself.
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->text('plan_detail');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venue_emergency_plans');
    }
};
