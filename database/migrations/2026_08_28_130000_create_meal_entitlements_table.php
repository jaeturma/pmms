<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_schedules', function (Blueprint $table): void {
            $table->boolean('enforce_serving_time')->default(true)->after('ends_at');
        });

        Schema::create('meal_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('available');
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('consumed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('consumption_method', 20)->nullable();
            $table->text('consumption_notes')->nullable();
            $table->timestamps();
            $table->unique(['meal_schedule_id', 'user_id']);
            $table->index(['meal_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_entitlements');
        Schema::table('meal_schedules', fn (Blueprint $table) => $table->dropColumn('enforce_serving_time'));
    }
};
