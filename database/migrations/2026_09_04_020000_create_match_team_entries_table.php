<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_team_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('team_entry_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['match_id', 'team_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_team_entries');
    }
};
