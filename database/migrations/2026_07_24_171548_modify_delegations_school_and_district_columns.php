<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A delegation registers under a School (City division) or a District
     * — presented as "Municipality" in a Province division (see
     * App\Enums\DivisionType) — never both. See docs/division.md and
     * docs/delegations.md.
     */
    public function up(): void
    {
        Schema::table('delegations', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->change();
        });

        Schema::table('delegations', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('school_id')
                ->constrained()->restrictOnDelete();
            $table->unique(['meet_id', 'district_id']);
        });
    }

    public function down(): void
    {
        Schema::table('delegations', function (Blueprint $table) {
            $table->dropUnique(['meet_id', 'district_id']);
            $table->dropConstrainedForeignId('district_id');
        });

        Schema::table('delegations', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable(false)->change();
        });
    }
};
