<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('encoded')->index();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('encoded_at');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['meet_id', 'event_id']);
        });

        Schema::create('result_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('rank');
            $table->string('mark', 60)->nullable();
            $table->boolean('is_tie')->default(false);
            $table->timestamps();

            $table->unique(['event_result_id', 'entry_id']);
            $table->index(['event_result_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_placements');
        Schema::dropIfExists('event_results');
    }
};
