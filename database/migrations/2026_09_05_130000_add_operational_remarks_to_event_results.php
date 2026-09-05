<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table): void {
            $table->text('operational_remarks')->nullable()->after('result_scope');
        });
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table): void {
            $table->dropColumn('operational_remarks');
        });
    }
};
