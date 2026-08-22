<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_category_competition_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_area_id')->constrained()->cascadeOnDelete();
            $table->string('source_code', 180)->nullable()->unique();
            $table->string('status', 24)->default('active');
            $table->timestamps();

            $table->unique(
                ['meet_sport_id', 'sport_category_id', 'competition_area_id'],
                'category_competition_area_unique',
            );
            $table->index(['sport_category_id', 'venue_id'], 'category_venue_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_category_competition_areas');
    }
};
