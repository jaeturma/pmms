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
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_consumable')->default(false);
            $table->timestamps();

            // Per-meet catalog, defined fresh each meet by that meet's
            // Supply Team — no division-wide durable-goods catalog in
            // this WP. Unique per (meet_id, name) mirrors
            // management_teams' (meet_id, team_type) uniqueness.
            $table->unique(['meet_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_categories');
    }
};
