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
        Schema::create('billeting_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billeting_venue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delegation_id')->constrained()->cascadeOnDelete();
            // Redundant with delegation.meet_id but kept explicit per the
            // migration plan's own wording — FK'd to management_teams +
            // meets + delegations, all three.
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->string('room_detail', 120)->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('status', 20)->default('assigned');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // A delegation is billeted at one place at a time.
            $table->unique(['meet_id', 'delegation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billeting_assignments');
    }
};
