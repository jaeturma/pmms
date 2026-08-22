<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coach_onboarding_request_event')) {
            Schema::create('coach_onboarding_request_event', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('coach_onboarding_request_id');
                $table->unsignedBigInteger('event_id');
                $table->timestamps();
            });
        }

        $foreignKeys = collect(Schema::getForeignKeys('coach_onboarding_request_event'))
            ->pluck('name');

        Schema::table('coach_onboarding_request_event', function (Blueprint $table) use ($foreignKeys) {
            if (! $foreignKeys->contains('coach_onboarding_event_request_fk')) {
                $table->foreign('coach_onboarding_request_id', 'coach_onboarding_event_request_fk')
                    ->references('id')
                    ->on('coach_onboarding_requests')
                    ->cascadeOnDelete();
            }

            if (! $foreignKeys->contains('coach_onboarding_event_event_fk')) {
                $table->foreign('event_id', 'coach_onboarding_event_event_fk')
                    ->references('id')
                    ->on('events')
                    ->restrictOnDelete();
            }

            if (! Schema::hasIndex('coach_onboarding_request_event', 'coach_onboarding_event_unique')) {
                $table->unique(['coach_onboarding_request_id', 'event_id'], 'coach_onboarding_event_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_onboarding_request_event');
    }
};
