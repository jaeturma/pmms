<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->string('playing_area_type', 16)->default('venue');
            $table->unsignedSmallInteger('playing_area_count')->default(1);
            $table->timestamps();
            $table->unique(['event_id', 'venue_id']);
        });

        Schema::create('event_venue_coordinators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['event_venue_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_venue_coordinators');
        Schema::dropIfExists('event_venues');
    }
};
