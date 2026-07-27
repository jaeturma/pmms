<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The real DepEd "school district" sub-unit — distinct from the
     * `districts` table, which (per docs/division.md) plays the role of
     * "municipality" in this deployment's Province division. A municipality
     * has one or more school districts; a school belongs to at most one.
     * `district_id` here means "municipality", matching that established
     * convention.
     */
    public function up(): void
    {
        Schema::create('school_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('nickname', 60)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['district_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_districts');
    }
};
