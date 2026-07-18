<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegation_id')->constrained()->restrictOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('submitted')->index();
            $table->timestamps();

            $table->unique(['athlete_id', 'event_id']);
            $table->index(['delegation_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
