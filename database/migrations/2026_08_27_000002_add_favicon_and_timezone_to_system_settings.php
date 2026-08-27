<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->foreignId('favicon_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->string('timezone', 64)->default('Asia/Manila');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('favicon_upload_id');
            $table->dropColumn('timezone');
        });
    }
};
