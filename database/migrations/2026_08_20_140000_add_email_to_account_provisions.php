<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('account_provisions', 'email')) {
            Schema::table('account_provisions', function (Blueprint $table) {
                $table->string('email')->nullable()->after('suggested_username')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('account_provisions', 'email')) {
            Schema::table('account_provisions', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }
    }
};
