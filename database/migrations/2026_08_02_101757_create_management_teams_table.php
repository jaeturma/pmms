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
        Schema::create('management_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->string('team_type', 30);
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('forming');
            $table->timestamps();

            // At most one team of a given type per meet — e.g. one ICT
            // Team, one Medical Team. Not a mandate requirement in so
            // many words, but the natural real-world shape; revisit if a
            // deployment genuinely needs more than one.
            $table->unique(['meet_id', 'team_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('management_teams');
    }
};
