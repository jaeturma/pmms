<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->foreignId('tm_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tm_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tm_confirmed_by');
            $table->dropColumn('tm_confirmed_at');
        });
    }
};
