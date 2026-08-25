<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_onboarding_requests', function (Blueprint $table) {
            $table->foreignId('meet_sport_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('delegation_id')->nullable()->after('meet_sport_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->after('delegation_id')->constrained()->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('status');
        });

        Schema::table('coach_assignment_requests', function (Blueprint $table) {
            $table->foreignId('sport_category_id')->nullable()->after('event_id')->constrained()->restrictOnDelete();
            $table->string('scope_type', 20)->default('event')->after('sport_category_id');
            $table->foreignId('assigned_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('reviewed_at');
            $table->timestamp('ended_at')->nullable()->after('assigned_at');
            $table->index(['user_id', 'status', 'ended_at'], 'coach_active_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('coach_assignment_requests', function (Blueprint $table) {
            $table->dropIndex('coach_active_scope_index');
            $table->dropConstrainedForeignId('sport_category_id');
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn(['scope_type', 'assigned_at', 'ended_at']);
        });
        Schema::table('coach_onboarding_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meet_sport_id');
            $table->dropConstrainedForeignId('delegation_id');
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn('submitted_at');
        });
    }
};
