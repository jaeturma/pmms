<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->restrictOnDelete();
            $table->string('status', 20)->default('in_progress')->index();
            $table->string('side_a_label', 255);
            $table->string('side_b_label', 255);
            $table->unsignedInteger('score_a')->default(0);
            $table->unsignedInteger('score_b')->default(0);
            $table->string('period_label', 100)->nullable();
            $table->string('status_note', 500)->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('score_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scoring_session_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->json('payload')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_events');
        Schema::dropIfExists('scoring_sessions');
    }
};
