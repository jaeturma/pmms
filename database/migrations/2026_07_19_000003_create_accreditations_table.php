<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->unique()->constrained('personnel')->cascadeOnDelete();
            $table->string('number', 20)->nullable()->unique();
            $table->foreignId('accredited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accredited_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditations');
    }
};
