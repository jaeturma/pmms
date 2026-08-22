<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('source_code', 100)->nullable()->unique()->after('id');
            $table->string('source_system', 60)->nullable()->after('source_code');
            $table->string('short_name', 100)->nullable()->after('name');
            $table->foreignId('municipality_id')->nullable()->after('address')->constrained('districts')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('municipality_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->text('public_notes')->nullable()->after('longitude');
            $table->text('internal_notes')->nullable()->after('public_notes');
            $table->string('readiness_status', 24)->default('planned')->after('internal_notes');
            $table->text('source_venue_text')->nullable()->after('readiness_status');
            $table->text('source_notes')->nullable()->after('source_venue_text');
        });

        Schema::create('meet_sport_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->string('source_code', 120)->nullable()->unique();
            $table->unsignedSmallInteger('expected_area_count')->nullable();
            $table->text('notes')->nullable();
            $table->text('source_area_text')->nullable();
            $table->text('source_coordinator_text')->nullable();
            $table->text('source_contact_text')->nullable();
            $table->string('import_status', 30)->default('ready_to_seed');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('status', 24)->default('planned');
            $table->timestamps();
            $table->unique(['meet_sport_id', 'venue_id']);
        });

        Schema::create('competition_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('source_code', 140)->nullable()->unique();
            $table->string('code', 40)->nullable();
            $table->string('name', 120);
            $table->string('area_type', 24)->default('playing_area');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('status', 24)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['venue_id', 'name']);
        });

        Schema::create('game_coordinator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->string('source_code', 160)->nullable()->unique();
            $table->boolean('is_lead')->default(false);
            $table->string('status', 24)->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('source_contact_text')->nullable();
            $table->timestamps();
            $table->unique(['meet_sport_id', 'venue_id', 'competition_area_id', 'person_id'], 'game_coordinator_scope_unique');
        });

        Schema::table('event_schedules', function (Blueprint $table) {
            $table->foreignId('competition_area_id')->nullable()->after('venue_id')->constrained()->nullOnDelete();
            $table->index(['competition_area_id', 'scheduled_date'], 'schedule_area_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->dropIndex('schedule_area_date_index');
            $table->dropConstrainedForeignId('competition_area_id');
        });
        Schema::dropIfExists('game_coordinator_assignments');
        Schema::dropIfExists('competition_areas');
        Schema::dropIfExists('meet_sport_venues');
        Schema::table('venues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('municipality_id');
            $table->dropUnique(['source_code']);
            $table->dropColumn([
                'source_code', 'source_system', 'short_name', 'latitude', 'longitude',
                'public_notes', 'internal_notes', 'readiness_status', 'source_venue_text', 'source_notes',
            ]);
        });
    }
};
