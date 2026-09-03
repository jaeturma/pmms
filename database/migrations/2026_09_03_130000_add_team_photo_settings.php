<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meet_sport_assignments', function (Blueprint $table): void {
            $table->foreignId('photo_upload_id')->nullable()->after('user_id')->constrained('file_uploads')->nullOnDelete();
        });
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->string('team_photo_visibility', 20)->default('authenticated');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn('team_photo_visibility');
        });
        Schema::table('meet_sport_assignments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('photo_upload_id');
        });
    }
};
