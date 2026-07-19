<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('round_label', 60);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('status', 20)->default('scheduled')->index();
            $table->timestamps();

            $table->index(['meet_id', 'event_id', 'sequence']);
        });

        Schema::create('match_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['match_id', 'entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_entries');
        Schema::dropIfExists('matches');
    }
};
