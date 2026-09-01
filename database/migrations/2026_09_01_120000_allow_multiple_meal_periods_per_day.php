<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_schedules', function (Blueprint $table): void {
            $table->unique(
                ['meet_id', 'meal_type', 'date', 'starts_at'],
                'meal_schedules_meal_period_unique',
            );
            $table->dropUnique(['meet_id', 'meal_type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_schedules', function (Blueprint $table): void {
            $table->unique(['meet_id', 'meal_type', 'date']);
            $table->dropUnique('meal_schedules_meal_period_unique');
        });
    }
};
