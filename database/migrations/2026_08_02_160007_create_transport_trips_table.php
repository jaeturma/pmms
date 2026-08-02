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
        Schema::create('transport_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            // Nullable: a trip need not be tied to one delegation (e.g.
            // an officials' shuttle).
            $table->foreignId('delegation_id')->nullable()->constrained()->nullOnDelete();
            // Nullable both ways: the request this trip fulfills — kept
            // so a trip's history survives if the request row is ever
            // removed.
            $table->foreignId('transport_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pickup_location', 200);
            $table->string('dropoff_location', 200);
            $table->string('status', 20)->default('dispatched');
            $table->timestamp('scheduled_at');
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_trips');
    }
};
