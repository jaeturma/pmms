<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_placements', function (Blueprint $table) {
            $table->foreignId('athlete_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::create('result_placement_athlete', function (Blueprint $table) {
            $table->foreignId('result_placement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->restrictOnDelete();
            $table->primary(['result_placement_id', 'athlete_id']);
        });
        Schema::create('result_placement_coach', function (Blueprint $table) {
            $table->foreignId('result_placement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role');
            $table->primary(['result_placement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_placement_coach');
        Schema::dropIfExists('result_placement_athlete');
        Schema::table('result_placements', fn (Blueprint $table) => $table->dropConstrainedForeignId('athlete_id'));
    }
};
