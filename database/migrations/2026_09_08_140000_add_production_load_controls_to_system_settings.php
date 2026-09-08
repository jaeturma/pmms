<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System Administrator "Production Load Controls" — an application-level
 * emergency lever for the live meet:
 *
 * - `live_scoreboards_suspended` stops all PUBLIC live-scoreboard viewing
 *   and polling (authenticated ICT scoring is untouched);
 * - `authenticated_inactivity_expiry_enabled` turns the idle-session
 *   logout on (default OFF, so enabling this feature changes no
 *   behaviour until an admin flips it);
 * - `authenticated_inactivity_timeout_minutes` is the idle window, once
 *   enabled, after which a logged-in web session is expired on its next
 *   request (minimum 5, enforced in the request + the middleware).
 *
 * All live on the canonical single `system_settings` row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->boolean('live_scoreboards_suspended')->default(false)->after('medal_tally_official');
            $table->boolean('authenticated_inactivity_expiry_enabled')->default(false)->after('live_scoreboards_suspended');
            $table->unsignedSmallInteger('authenticated_inactivity_timeout_minutes')->default(5)->after('authenticated_inactivity_expiry_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'live_scoreboards_suspended',
                'authenticated_inactivity_expiry_enabled',
                'authenticated_inactivity_timeout_minutes',
            ]);
        });
    }
};
