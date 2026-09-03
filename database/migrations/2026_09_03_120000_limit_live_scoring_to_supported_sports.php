<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $supportedSportIds = DB::table('sports')
            ->whereIn(DB::raw('LOWER(name)'), ['basketball', 'softball', 'baseball', 'boxing'])
            ->select('id');

        DB::table('matches')
            ->whereNotIn('event_id', DB::table('events')->whereIn('sport_id', $supportedSportIds)->select('id'))
            ->update(['live_scoring_enabled' => false]);
    }

    public function down(): void
    {
        // Unsupported legacy live-scoring flags cannot be reconstructed safely.
    }
};
