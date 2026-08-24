<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('login_splash_title', 180)->nullable();
            $table->foreignId('login_background_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('login_background_upload_id');
            $table->dropColumn('login_splash_title');
        });
    }
};
