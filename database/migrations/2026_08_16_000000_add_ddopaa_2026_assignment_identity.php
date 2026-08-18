<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 190)->unique();
            $table->string('full_name');
            $table->string('normalized_name', 190)->index();
            $table->json('source_flags')->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('meet_sport_assignments', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('sport_category_id')->constrained()->cascadeOnDelete();
            $table->string('original_designation', 180)->nullable()->after('role');
            $table->string('assignment_scope', 120)->nullable()->after('original_designation');
            $table->unsignedInteger('source_sequence')->nullable()->after('assignment_scope');
            $table->string('source_district_text', 180)->nullable()->after('source_sequence');
            $table->foreignId('district_id')->nullable()->after('source_district_text')->constrained()->nullOnDelete();
            $table->foreignId('school_district_id')->nullable()->after('district_id')->constrained()->nullOnDelete();
            $table->boolean('requires_system_user')->default(false)->after('school_district_id');
            $table->unique(['meet_sport_id', 'person_id', 'role', 'source_sequence'], 'meet_sport_person_source_unique');
        });

        Schema::table('meet_sport_assignments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('management_teams', function (Blueprint $table) {
            // Keep an index for management_teams.meet_id's foreign key before
            // removing the composite unique index that MySQL was reusing.
            $table->index('meet_id', 'management_teams_meet_id_index');
            $table->string('team_type', 60)->change();
        });

        Schema::table('management_teams', function (Blueprint $table) {
            $table->dropUnique(['meet_id', 'team_type']);
            $table->string('source_code', 100)->nullable()->after('team_type');
            $table->unsignedSmallInteger('display_order')->default(0)->after('description');
            $table->unique(['meet_id', 'source_code']);
        });

        DB::table('management_teams')->whereNull('source_code')->update([
            'source_code' => DB::raw('UPPER(team_type)'),
        ]);

        Schema::table('management_team_members', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('management_team_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('source_sequence')->nullable()->after('responsibilities');
            $table->unique(['management_team_id', 'person_id', 'role_title'], 'management_team_person_role_unique');
        });

        Schema::table('management_team_members', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::create('district_sports_coordinator_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meet_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('school_district_id');
            $table->unsignedBigInteger('person_id');
            $table->boolean('is_lead')->default(true);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->foreign('meet_id', 'dsc_meet_fk')->references('id')->on('meets')->cascadeOnDelete();
            $table->foreign('district_id', 'dsc_municipality_fk')->references('id')->on('districts')->restrictOnDelete();
            $table->foreign('school_district_id', 'dsc_school_district_fk')->references('id')->on('school_districts')->restrictOnDelete();
            $table->foreign('person_id', 'dsc_person_fk')->references('id')->on('people')->cascadeOnDelete();
            $table->unique(['meet_id', 'school_district_id', 'person_id'], 'dsc_meet_school_district_person_unique');
        });

        Schema::create('account_provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('suggested_username', 190)->unique();
            $table->string('target_role', 80);
            $table->string('status', 30)->default('pending');
            $table->text('reason')->nullable();
            $table->string('activation_token_hash', 64)->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('coach_assignment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meet_sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delegation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'meet_sport_id', 'delegation_id', 'school_id'], 'coach_scope_request_unique');
        });

        Schema::create('coach_onboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_onboarding_requests');
        Schema::dropIfExists('coach_assignment_requests');
        Schema::dropIfExists('account_provisions');
        Schema::dropIfExists('district_sports_coordinator_assignments');

        Schema::table('management_team_members', function (Blueprint $table) {
            $table->dropUnique('management_team_person_role_unique');
            $table->dropConstrainedForeignId('person_id');
            $table->dropColumn('source_sequence');
        });
        Schema::table('management_teams', function (Blueprint $table) {
            $table->dropUnique(['meet_id', 'source_code']);
            $table->dropColumn(['source_code', 'display_order']);
            $table->unique(['meet_id', 'team_type']);
            $table->dropIndex('management_teams_meet_id_index');
            $table->string('team_type', 30)->change();
        });
        Schema::table('meet_sport_assignments', function (Blueprint $table) {
            $table->dropUnique('meet_sport_person_source_unique');
            $table->dropConstrainedForeignId('person_id');
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('school_district_id');
            $table->dropColumn(['original_designation', 'assignment_scope', 'source_sequence', 'source_district_text', 'requires_system_user']);
        });
        Schema::dropIfExists('people');
    }
};
