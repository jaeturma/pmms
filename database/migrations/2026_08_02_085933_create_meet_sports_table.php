<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meet_sports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['meet_id', 'sport_id']);
        });

        $this->backfill();
    }

    /**
     * Derives each meet's initial sport set from its already-attached
     * events (meet_events -> events.sport_id) — deterministic, unlike the
     * sport_user -> meet_sport_assignments backfill this table's own
     * follow-up work package needs, which has no derivable meet_id at
     * all (see docs/architecture/pmms-data-migration-plan.md §5). Plain
     * query-builder SQL, not Eloquent models, per Laravel migration
     * convention — models can change shape over time, migrations must
     * not depend on that.
     */
    private function backfill(): void
    {
        $pairs = DB::table('meet_events')
            ->join('events', 'events.id', '=', 'meet_events.event_id')
            ->select('meet_events.meet_id', 'events.sport_id')
            ->distinct()
            ->get();

        if ($pairs->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('meet_sports')->insert($pairs->map(fn ($pair): array => [
            'meet_id' => $pair->meet_id,
            'sport_id' => $pair->sport_id,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meet_sports');
    }
};
