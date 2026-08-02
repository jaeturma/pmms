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
        Schema::create('equipment_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            // Where the equipment is being used — distinct from the
            // item's own storage venue_id, which a transfer moves
            // separately.
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('custodian_name', 120)->nullable();
            $table->foreignId('issued_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('purpose')->nullable();
            $table->string('status', 20)->default('issued');
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_issues');
    }
};
