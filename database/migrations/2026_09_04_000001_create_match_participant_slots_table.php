<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_participant_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('delegation_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->foreignId('entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['match_id', 'delegation_id', 'position']);
            $table->unique(['match_id', 'entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_participant_slots');
    }
};
