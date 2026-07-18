<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('gender', 10);
            $table->string('age_division', 20);
            $table->boolean('is_team_event')->default(false);
            $table->unsignedSmallInteger('max_entries_per_delegation')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['sport_id', 'name', 'gender', 'age_division']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
