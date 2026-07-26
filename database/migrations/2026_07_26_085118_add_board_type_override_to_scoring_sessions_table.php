<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scoring_sessions', function (Blueprint $table) {
            $table->string('board_type_override', 20)->nullable()->after('sport_state');
        });
    }

    public function down(): void
    {
        Schema::table('scoring_sessions', function (Blueprint $table) {
            $table->dropColumn('board_type_override');
        });
    }
};
