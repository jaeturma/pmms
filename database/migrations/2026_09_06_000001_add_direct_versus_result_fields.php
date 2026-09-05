<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            // Null retains legacy non-medal place records without reinterpreting them as games.
            $table->string('result_type', 20)->nullable()->index();
            $table->string('measurement_type', 20)->nullable();
        });
        Schema::table('result_placements', function (Blueprint $table) {
            $table->decimal('result_value', 20, 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('result_placements', fn (Blueprint $table) => $table->dropColumn('result_value'));
        Schema::table('event_results', function (Blueprint $table) {
            $table->dropIndex(['result_type']);
            $table->dropColumn(['result_type', 'measurement_type']);
        });
    }
};
