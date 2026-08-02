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
        Schema::create('meet_sport_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_sport_id')->constrained()->cascadeOnDelete();
            // Nullable: only some roles (e.g. Category Tournament Manager)
            // are scoped to one specific category; most are scoped to the
            // whole meet_sport. Deleting the category keeps the
            // assignment, just loses that extra scoping detail.
            $table->foreignId('sport_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40);
            $table->boolean('is_lead')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            // Prevents assigning the same person to the same role twice
            // for the same meet-sport. All three columns are non-nullable,
            // so this is exact — no MySQL NULL-uniqueness caveat (compare
            // docs/delegations.md's school_id/district_id note).
            $table->unique(['meet_sport_id', 'user_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meet_sport_assignments');
    }
};
