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
        Schema::create('billeting_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meet_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('address')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_phone', 30)->nullable();
            // Purely informational — set only if this billeting site
            // happens to coincide with an existing competition Venue.
            // This row is the source of truth for lodging-specific
            // fields, not `venues`.
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['meet_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billeting_venues');
    }
};
