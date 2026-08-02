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
        Schema::create('medical_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            // Exactly one of athlete_id/personnel_id is set — same
            // mutual-exclusivity shape Protest already enforces for
            // event_result_id/match_id, checked at the app level.
            $table->foreignId('athlete_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('conditions')->nullable();
            $table->string('emergency_contact_name', 120)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->boolean('consent_confirmed')->default(false);
            $table->timestamp('consent_confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // One clearance row per person per meet — NULL values don't
            // collide under a unique index, so these two constraints
            // coexist safely on the same nullable-pair columns.
            $table->unique(['meet_id', 'athlete_id']);
            $table->unique(['meet_id', 'personnel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_clearances');
    }
};
