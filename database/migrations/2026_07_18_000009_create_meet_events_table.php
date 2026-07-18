<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meet_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['meet_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meet_events');
    }
};
