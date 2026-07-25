<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Athletes and personnel get their own home school, decoupled from
     * their delegation's registering unit — a municipal (Province)
     * delegation pools several schools, so the delegation alone can no
     * longer answer "which school is this person from." See
     * docs/delegations.md "Known interim gap" and docs/athletes.md.
     */
    public function up(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('delegation_id')
                ->constrained()->restrictOnDelete();
        });

        Schema::table('personnel', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('delegation_id')
                ->constrained()->restrictOnDelete();
        });

        // Backfill from the delegation's own school where one exists — the
        // only case a school is derivable without input (school-rooted,
        // i.e. today's City-style delegations). Municipal-delegation rows,
        // if any predate this migration, are left null and must be fixed
        // up manually — there is no correct value to infer.
        DB::statement('
            UPDATE athletes
            SET school_id = (
                SELECT school_id FROM delegations WHERE delegations.id = athletes.delegation_id
            )
            WHERE school_id IS NULL
        ');

        DB::statement('
            UPDATE personnel
            SET school_id = (
                SELECT school_id FROM delegations WHERE delegations.id = personnel.delegation_id
            )
            WHERE school_id IS NULL
        ');

        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable(false)->change();
        });

        Schema::table('personnel', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('personnel', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
