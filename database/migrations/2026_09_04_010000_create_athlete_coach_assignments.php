<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_coach', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['athlete_id', 'coach_id']);
        });

        DB::table('athletes')->whereNotNull('registered_by')->orderBy('id')->each(function ($athlete): void {
            if (DB::table('users')->where('id', $athlete->registered_by)->where('role', 'coach')->exists()) {
                DB::table('athlete_coach')->insert([
                    'athlete_id' => $athlete->id,
                    'coach_id' => $athlete->registered_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_coach');
    }
};
