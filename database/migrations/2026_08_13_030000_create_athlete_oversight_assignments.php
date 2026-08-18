<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_oversight_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->string('authority_type', 40);
            $table->foreignId('district_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('school_district_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['user_id', 'meet_id', 'authority_type', 'district_id', 'school_district_id'], 'athlete_oversight_assignment_identity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_oversight_assignments');
    }
};
