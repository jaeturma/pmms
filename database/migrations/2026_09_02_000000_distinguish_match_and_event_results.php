<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table): void {
            $table->string('result_scope', 20)->default('event')->after('result_source')->index();
        });

        // Classification only: preserve every historical workflow status.
        DB::table('event_results')->whereNotNull('match_id')->update(['result_scope' => 'match']);
        DB::table('event_results')->whereNull('match_id')->update(['result_scope' => 'event']);
    }

    public function down(): void
    {
        Schema::table('event_results', fn (Blueprint $table) => $table->dropColumn('result_scope'));
    }
};
