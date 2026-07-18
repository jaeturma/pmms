<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160)->unique();
            $table->string('school_year', 9);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('venue')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meets');
    }
};
