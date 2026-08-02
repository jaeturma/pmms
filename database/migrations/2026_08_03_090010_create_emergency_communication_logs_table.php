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
        Schema::create('emergency_communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_incident_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->foreignId('sent_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_communication_logs');
    }
};
