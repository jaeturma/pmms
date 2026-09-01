<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->string('age_division', 80)->change());
        Schema::table('sport_categories', fn (Blueprint $table) => $table->string('level', 80)->nullable()->change());
        Schema::table('sport_roster_members', fn (Blueprint $table) => $table->string('level', 80)->change());
        Schema::table('sport_roster_limits', fn (Blueprint $table) => $table->string('level', 80)->change());

        Schema::table('athletes', function (Blueprint $table): void {
            $table->string('age_division', 80)->nullable()->after('grade_level');
            $table->foreignId('deletion_requested_by')->nullable()->after('registered_by')->constrained('users')->nullOnDelete();
            $table->timestamp('deletion_requested_at')->nullable()->after('deletion_requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deletion_requested_by');
            $table->dropColumn(['age_division', 'deletion_requested_at']);
        });

        Schema::table('events', fn (Blueprint $table) => $table->string('age_division', 20)->change());
        Schema::table('sport_categories', fn (Blueprint $table) => $table->string('level', 20)->nullable()->change());
        Schema::table('sport_roster_members', fn (Blueprint $table) => $table->string('level', 20)->change());
        Schema::table('sport_roster_limits', fn (Blueprint $table) => $table->string('level', 20)->change());
    }
};
