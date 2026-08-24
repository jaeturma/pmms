<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('user_registration_enabled')->default(true);
            $table->boolean('coach_registration_enabled')->default(true);
            $table->boolean('coach_athlete_registration_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'user_registration_enabled',
                'coach_registration_enabled',
                'coach_athlete_registration_enabled',
            ]);
        });
    }
};
