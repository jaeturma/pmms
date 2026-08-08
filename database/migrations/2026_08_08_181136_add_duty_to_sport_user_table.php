<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a real `duty` label (e.g. "Referee", "Scorer", "Timekeeper") per
 * Technical Official assignment, for the public sport mini portal's
 * Technical Officials section. `sport_user` was chosen over
 * `meet_sport_assignments` (role=TechnicalOfficial) because it's the one
 * with real, live-authorization-backing data today (see
 * `MeetSportAssignment`'s own docblock). No admin UI sets this column
 * yet — same "real column, wired later" scope as `Sport::photo_upload_id`
 * — so it renders as a generic "Technical Official" until an admin form
 * exists to fill it in; it is never fabricated on the public portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_user', function (Blueprint $table) {
            $table->string('duty', 100)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('sport_user', function (Blueprint $table) {
            $table->dropColumn('duty');
        });
    }
};
