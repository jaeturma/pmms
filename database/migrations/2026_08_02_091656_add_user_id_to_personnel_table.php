<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            // Nullable and deliberately not globally unique: a real coach
            // who returns across multiple meets gets a new Personnel row
            // per meet's delegation (delegation_id is meet-scoped) but
            // should be able to keep the same login — see
            // docs/architecture/pmms-approved-organizational-model.md §4
            // (OQ-2). The (delegation_id, user_id) pair is unique, which
            // only blocks the real data-entry mistake: the same login
            // linked to two roster rows within one delegation.
            $table->foreignId('user_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
            $table->unique(['delegation_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->dropUnique(['delegation_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
