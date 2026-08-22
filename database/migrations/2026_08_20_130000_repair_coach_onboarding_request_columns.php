<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coach_onboarding_requests')) {
            return;
        }

        if (! Schema::hasColumn('coach_onboarding_requests', 'district_id')) {
            Schema::table('coach_onboarding_requests', function (Blueprint $table) {
                $table->foreignId('district_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('coach_onboarding_requests', 'event_id')) {
            Schema::table('coach_onboarding_requests', function (Blueprint $table) {
                $table->foreignId('event_id')
                    ->nullable()
                    ->after('district_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // This repair migration intentionally does not remove columns that
        // may have been created by the original migrations.
    }
};
