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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number', 20);
            $table->string('type', 30)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('driver_name', 120)->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Per-meet roster, not a division-wide fleet catalog — same
            // catalog-scope convention WP-REALIGN-10 established for
            // equipment.
            $table->unique(['meet_id', 'plate_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
