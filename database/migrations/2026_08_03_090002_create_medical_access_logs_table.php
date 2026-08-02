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
        Schema::create('medical_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_clearance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accessed_by_user_id')->constrained('users')->cascadeOnDelete();
            // Required — a break-glass view of sensitive data always
            // needs a stated cause, same discipline
            // inventory_adjustments.reason already established.
            $table->text('reason');
            $table->timestamp('accessed_at')->useCurrent();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_access_logs');
    }
};
