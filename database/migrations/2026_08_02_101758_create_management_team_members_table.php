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
        Schema::create('management_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('management_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_title', 120)->nullable();
            $table->boolean('is_head')->default(false);
            $table->text('responsibilities')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            // One membership row per person per team — role_title/is_head/
            // responsibilities describe that one membership, not several.
            $table->unique(['management_team_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('management_team_members');
    }
};
