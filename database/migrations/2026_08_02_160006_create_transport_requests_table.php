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
        Schema::create('transport_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delegation_id')->constrained()->cascadeOnDelete();
            $table->string('pickup_location', 200);
            $table->string('dropoff_location', 200);
            // When the ride is needed — distinct from created_at.
            $table->timestamp('requested_at')->useCurrent();
            $table->unsignedInteger('passenger_count')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_requests');
    }
};
