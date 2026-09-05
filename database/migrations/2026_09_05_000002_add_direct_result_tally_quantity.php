<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_placements', function (Blueprint $table): void {
            $table->unsignedSmallInteger('tally_quantity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('result_placements', function (Blueprint $table): void {
            $table->dropColumn('tally_quantity');
        });
    }
};
