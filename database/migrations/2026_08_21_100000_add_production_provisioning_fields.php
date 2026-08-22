<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 190)->nullable()->unique()->after('name');
            $table->boolean('must_change_password')->default(false)->after('password')->index();
            $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
            $table->timestamp('disabled_at')->nullable()->after('password_changed_at');
            $table->string('email')->nullable()->change();
        });

        Schema::table('account_provisions', function (Blueprint $table) {
            $table->foreignId('linked_user_id')->nullable()->after('person_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('account_provisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'must_change_password', 'password_changed_at', 'disabled_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
