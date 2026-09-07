<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DAVRAA Report — the Tournament ICT's manually-built "List of
 * Recommended Qualifiers to DAVRAA". A group is an ICT-defined bundle of
 * Sports Events + hand-picked qualifiers; membership references existing
 * `athletes` / `users` by id (never duplicating a person record) and
 * carries a field snapshot so a finalized report stays stable even if
 * the underlying roster/assignments change later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('davraa_report_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->string('name', 160);
            $table->string('division', 10);   // App\Enums\GenderCategory — boys / girls / mixed
            $table->string('level', 12);      // App\Enums\DavraaReportLevel — elementary / secondary / sned
            $table->string('status', 12)->default('draft')->index(); // App\Enums\DavraaReportStatus
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['meet_id', 'sport_id']);
        });

        Schema::create('davraa_report_group_event', function (Blueprint $table): void {
            $table->foreignId('davraa_report_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->primary(['davraa_report_group_id', 'event_id'], 'davraa_group_event_primary');
        });

        Schema::create('davraa_report_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('davraa_report_group_id')->constrained()->cascadeOnDelete();
            $table->string('designation', 20); // App\Enums\DavraaReportDesignation
            // Exactly one of these identifies the person; both null is only
            // permitted for a manually-typed CHAPERONE with no account.
            $table->foreignId('athlete_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coach_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            // Field snapshot captured at save time — the report renders from
            // these so it never silently changes after finalization.
            $table->string('last_name', 120)->nullable();
            $table->string('given_names', 160)->nullable();
            $table->string('middle_initial', 8)->nullable();
            $table->string('lrn', 20)->nullable();
            $table->string('school_name', 200)->nullable();
            $table->string('district_name', 200)->nullable();
            $table->timestamps();

            $table->index(['davraa_report_group_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('davraa_report_members');
        Schema::dropIfExists('davraa_report_group_event');
        Schema::dropIfExists('davraa_report_groups');
    }
};
