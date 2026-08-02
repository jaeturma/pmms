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
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('quantity_delta');
            // Required — an inventory number changing outside the normal
            // issue/return/transfer flow always needs a stated cause,
            // mirrors ResultController::correct()'s reason requirement.
            $table->text('reason');
            $table->foreignId('adjusted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('adjusted_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
