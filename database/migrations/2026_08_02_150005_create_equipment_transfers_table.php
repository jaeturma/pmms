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
        Schema::create('equipment_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            // Nullable: the item may be moving out of unassigned general
            // storage (no prior venue).
            $table->foreignId('from_venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->foreignId('to_venue_id')->constrained('venues')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->foreignId('transferred_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_transfers');
    }
};
