<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('audit_logs')
            ->where('action', 'athlete.created')
            ->where('auditable_type', 'App\\Models\\Athlete')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->select(['auditable_id', 'user_id'])
            ->each(function (object $log): void {
                DB::table('athletes')
                    ->where('id', $log->auditable_id)
                    ->whereNull('registered_by')
                    ->update(['registered_by' => $log->user_id]);
            });
    }

    public function down(): void
    {
        // Ownership inferred from historical audit records is intentionally retained.
    }
};
