<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_sport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['personnel_id', 'sport_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_sport');
    }
};
