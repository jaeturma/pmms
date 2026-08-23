<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('matches', 'live_scoring_enabled')) {
            Schema::table('matches', fn (Blueprint $table) => $table->boolean('live_scoring_enabled')->default(false)->after('sequence'));
        }
        if (! Schema::hasColumn('matches', 'awards_medals')) {
            Schema::table('matches', fn (Blueprint $table) => $table->boolean('awards_medals')->default(false)->after('live_scoring_enabled'));
        }
        if (! Schema::hasColumn('matches', 'competition_area')) {
            Schema::table('matches', fn (Blueprint $table) => $table->string('competition_area', 100)->nullable()->after('event_schedule_id'));
        }

        $indexes = collect(Schema::getIndexes('event_results'))->pluck('name');

        if (! $indexes->contains('event_results_meet_id_index')) {
            Schema::table('event_results', function (Blueprint $table) {
                // MySQL can reuse the meet/event unique index for meet_id's
                // foreign key. Preserve a dedicated supporting index before
                // replacing the event-level uniqueness with match uniqueness.
                $table->index('meet_id');
            });
        }

        if ($indexes->contains('event_results_meet_id_event_id_unique')) {
            Schema::table('event_results', fn (Blueprint $table) => $table->dropUnique(['meet_id', 'event_id']));
        }
        if (! Schema::hasColumn('event_results', 'match_id')) {
            Schema::table('event_results', fn (Blueprint $table) => $table->foreignId('match_id')->nullable()->after('event_id')->constrained('matches')->nullOnDelete());
        }
        if (! Schema::hasColumn('event_results', 'event_schedule_id')) {
            Schema::table('event_results', fn (Blueprint $table) => $table->foreignId('event_schedule_id')->nullable()->after('match_id')->constrained()->nullOnDelete());
        }
        if (! Schema::hasColumn('event_results', 'scoring_session_id')) {
            Schema::table('event_results', fn (Blueprint $table) => $table->foreignId('scoring_session_id')->nullable()->after('event_schedule_id')->constrained()->nullOnDelete());
        }
        if (! Schema::hasColumn('event_results', 'result_source')) {
            Schema::table('event_results', fn (Blueprint $table) => $table->string('result_source', 20)->default('legacy')->after('scoring_session_id'));
        }
        if (! collect(Schema::getIndexes('event_results'))->pluck('name')->contains('event_results_match_id_unique')) {
            Schema::table('event_results', fn (Blueprint $table) => $table->unique('match_id'));
        }
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->dropUnique(['match_id']);
            $table->dropConstrainedForeignId('scoring_session_id');
            $table->dropConstrainedForeignId('event_schedule_id');
            $table->dropConstrainedForeignId('match_id');
            $table->dropColumn('result_source');
            $table->unique(['meet_id', 'event_id']);
        });

        Schema::table('event_results', function (Blueprint $table) {
            $table->dropIndex(['meet_id']);
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['live_scoring_enabled', 'awards_medals', 'competition_area']);
        });
    }
};
