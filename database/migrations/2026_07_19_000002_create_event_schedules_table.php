<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->date('scheduled_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['venue_id', 'scheduled_date']);
            $table->index(['meet_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_schedules');
    }
};
