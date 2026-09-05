<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table): void {
            $table->string('manual_score_a', 60)->nullable()->after('awards_medals');
            $table->string('manual_score_b', 60)->nullable()->after('manual_score_a');
            $table->foreignId('winner_delegation_id')->nullable()->after('manual_score_b')->constrained('delegations')->nullOnDelete();
            $table->text('notes')->nullable()->after('winner_delegation_id');
        });
        Schema::table('match_participant_slots', function (Blueprint $table): void {
            $table->boolean('is_selected')->default(false)->after('entry_id');
        });
        Schema::table('result_placements', function (Blueprint $table): void {
            $table->foreignId('delegation_id')->nullable()->after('team_entry_id')->constrained()->restrictOnDelete();
            $table->index(['event_result_id', 'delegation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('result_placements', function (Blueprint $table): void {
            $table->dropForeign(['delegation_id']);
            $table->dropIndex(['event_result_id', 'delegation_id']);
            $table->dropColumn('delegation_id');
        });
        Schema::table('match_participant_slots', fn (Blueprint $table) => $table->dropColumn('is_selected'));
        Schema::table('matches', function (Blueprint $table): void {
            $table->dropForeign(['winner_delegation_id']);
            $table->dropColumn(['manual_score_a', 'manual_score_b', 'winner_delegation_id', 'notes']);
        });
    }
};
