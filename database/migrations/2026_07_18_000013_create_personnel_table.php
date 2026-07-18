<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegation_id')->constrained()->restrictOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('role', 20)->index();
            $table->string('phone', 30)->nullable();
            $table->string('email', 160)->nullable();
            $table->foreignId('photo_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
