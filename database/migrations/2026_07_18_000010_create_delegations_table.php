<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('head_name', 160);
            $table->string('head_phone', 30)->nullable();
            $table->string('head_email', 160)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();

            $table->unique(['meet_id', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegations');
    }
};
