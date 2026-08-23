<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('app_title', 120)->nullable()->after('id');
            $table->foreignId('app_logo_upload_id')->nullable()->after('app_title')->constrained('file_uploads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('app_logo_upload_id');
            $table->dropColumn('app_title');
        });
    }
};
