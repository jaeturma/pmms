<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Direct Event Result medal rows carry their own explicit medal type,
     * so a Sports Event can award any combination (Gold/Gold/Silver,
     * Gold/Silver/Bronze/Bronze, …) instead of an assumed one-each podium.
     * Null on every other placement (encoded results still derive the medal
     * from `rank`).
     */
    public function up(): void
    {
        Schema::table('result_placements', function (Blueprint $table): void {
            $table->string('medal_type', 10)->nullable()->after('rank');
        });
    }

    public function down(): void
    {
        Schema::table('result_placements', function (Blueprint $table): void {
            $table->dropColumn('medal_type');
        });
    }
};
