<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegation_id')->constrained()->restrictOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('sex', 10);
            $table->date('birthdate');
            $table->string('lrn', 12)->unique();
            $table->unsignedTinyInteger('grade_level');
            $table->foreignId('photo_upload_id')->nullable()->constrained('file_uploads')->nullOnDelete();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};
