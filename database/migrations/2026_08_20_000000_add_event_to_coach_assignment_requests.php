<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_assignment_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('coach_assignment_requests', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('meet_sport_id')->constrained()->cascadeOnDelete();
            }
        });

        Schema::table('coach_assignment_requests', function (Blueprint $table) {
            $table->index('user_id', 'coach_requests_user_index');
            $table->dropUnique('coach_scope_request_unique');
            $table->unique(['user_id', 'event_id', 'delegation_id', 'school_id'], 'coach_event_team_request_unique');
        });
    }

    public function down(): void
    {
        Schema::table('coach_assignment_requests', function (Blueprint $table) {
            $table->dropUnique('coach_event_team_request_unique');
            $table->unique(['user_id', 'meet_sport_id', 'delegation_id', 'school_id'], 'coach_scope_request_unique');
            $table->dropIndex('coach_requests_user_index');
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
