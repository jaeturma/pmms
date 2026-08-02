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
        Schema::create('equipment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_issue_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('condition_on_return', 20)->nullable();
            $table->foreignId('received_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('returned_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_returns');
    }
};
