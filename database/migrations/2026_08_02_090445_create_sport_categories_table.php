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
        Schema::create('sport_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            // Nullable: a category may be catalog-wide (no meet_sport_id,
            // reusable across every meet a sport is included in) or
            // meet-specific — see docs/architecture/pmms-approved-organizational-model.md §2.
            $table->foreignId('meet_sport_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('level', 20)->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('discipline', 60)->nullable();
            $table->string('event_type', 20)->nullable();
            $table->string('display_name', 120);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_categories');
    }
};
