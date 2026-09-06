<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table): void {
            $table->foreignId('correction_requested_by')->nullable()->after('cancellation_request_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('correction_requested_at')->nullable()->after('correction_requested_by');
            $table->text('correction_request_reason')->nullable()->after('correction_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('correction_requested_by');
            $table->dropColumn(['correction_requested_at', 'correction_request_reason']);
        });
    }
};
