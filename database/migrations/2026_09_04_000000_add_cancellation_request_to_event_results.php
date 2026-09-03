<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table): void {
            $table->foreignId('cancellation_requested_by')->nullable()->after('return_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_requested_by');
            $table->text('cancellation_request_reason')->nullable()->after('cancellation_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancellation_requested_by');
            $table->dropColumn(['cancellation_requested_at', 'cancellation_request_reason']);
        });
    }
};
