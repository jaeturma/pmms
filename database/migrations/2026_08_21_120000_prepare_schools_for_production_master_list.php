<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the composite unique index as the supporting index
        // for schools.district_id's foreign key. Give that foreign key its
        // own index before removing the legacy uniqueness constraint.
        Schema::table('schools', function (Blueprint $table) {
            $table->index('district_id');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropUnique(['district_id', 'name']);
            $table->foreignId('district_id')->nullable()->change();
            $table->string('level', 20)->nullable()->change();
            $table->string('school_type', 20)->nullable()->after('school_id_code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('school_type');
            $table->foreignId('district_id')->nullable(false)->change();
            $table->string('level', 20)->nullable(false)->change();
            $table->unique(['district_id', 'name']);
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropIndex(['district_id']);
        });
    }
};
