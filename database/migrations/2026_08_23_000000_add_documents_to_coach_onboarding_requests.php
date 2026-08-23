<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_onboarding_requests', function (Blueprint $table) {
            $table->foreignId('profile_upload_id')->nullable()->after('event_id')->constrained('file_uploads')->nullOnDelete();
            $table->foreignId('certification_upload_id')->nullable()->after('profile_upload_id')->constrained('file_uploads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('coach_onboarding_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('certification_upload_id');
            $table->dropConstrainedForeignId('profile_upload_id');
        });
    }
};
