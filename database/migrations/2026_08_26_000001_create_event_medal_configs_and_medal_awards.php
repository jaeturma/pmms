<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_medal_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('awards_medals')->default(true);
            $table->string('award_type', 20)->default('INDIVIDUAL');
            $table->string('physical_quantity_mode', 30)->default('FIXED');
            $table->unsignedSmallInteger('gold_physical_quantity')->nullable();
            $table->unsignedSmallInteger('silver_physical_quantity')->nullable();
            $table->unsignedSmallInteger('bronze_physical_quantity')->nullable();
            $table->unsignedSmallInteger('gold_tally_quantity')->nullable();
            $table->unsignedSmallInteger('silver_tally_quantity')->nullable();
            $table->unsignedSmallInteger('bronze_tally_quantity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('medal_awards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_placement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delegation_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rank');
            $table->string('medal_type', 10);
            $table->unsignedSmallInteger('physical_quantity');
            $table->unsignedSmallInteger('tally_quantity');
            $table->unsignedInteger('result_version')->default(1);
            $table->foreignId('snapshotted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('snapshotted_at');
            $table->timestamps();
            $table->unique(['event_result_id', 'result_placement_id', 'rank'], 'medal_award_result_recipient_unique');
            $table->index(['delegation_id', 'medal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medal_awards');
        Schema::dropIfExists('event_medal_configs');
    }
};
