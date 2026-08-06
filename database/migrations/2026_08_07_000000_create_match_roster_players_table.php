<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_roster_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained('entries')->cascadeOnDelete();
            $table->char('side', 1);
            $table->string('jersey_number', 10)->nullable();
            $table->boolean('is_starter')->default(false);
            $table->timestamps();

            $table->unique(['match_id', 'entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_roster_players');
    }
};
