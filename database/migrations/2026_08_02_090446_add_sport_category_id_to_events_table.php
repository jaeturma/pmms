<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Additive context only — gender/age_division remain the
            // authoritative, always-set columns; see
            // docs/architecture/pmms-data-migration-plan.md §2/§6.
            $table->foreignId('sport_category_id')->nullable()->after('sport_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sport_category_id');
        });
    }
};
