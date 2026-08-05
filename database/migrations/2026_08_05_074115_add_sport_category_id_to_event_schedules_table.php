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
        Schema::table('event_schedules', function (Blueprint $table) {
            // Additive context only — event_id/venue_id remain the
            // authoritative, always-set columns; see
            // docs/architecture/pmms-data-migration-plan.md §3
            // (WP-REALIGN-17), same shape as events.sport_category_id.
            $table->foreignId('sport_category_id')->nullable()->after('event_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sport_category_id');
        });
    }
};
