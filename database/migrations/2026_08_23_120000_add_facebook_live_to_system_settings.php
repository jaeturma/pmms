<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('facebook_live_enabled')->default(false)->after('app_logo_upload_id');
            $table->text('facebook_live_url')->nullable()->after('facebook_live_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['facebook_live_enabled', 'facebook_live_url']);
        });
    }
};
