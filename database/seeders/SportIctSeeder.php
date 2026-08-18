<?php

namespace Database\Seeders;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local test accounts for operating the Basketball, Baseball, and Boxing
 * scoreboards in the DdOPAA 2026 showcase meet. Each account receives one
 * active Tournament ICT assignment only; ScoringSessionController uses that
 * meet+sport assignment to prevent cross-sport scoreboard control.
 */
class SportIctSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->first();

        if ($meet === null) {
            $this->command?->warn('SportIctSeeder skipped: run Ddopaa2026ShowcaseSeeder first.');

            return;
        }

        $accounts = [
            'Basketball' => ['basketball.ict@ddopaa2026.test', 'Basketball Sport ICT (Demo)'],
            'Baseball' => ['baseball.ict@ddopaa2026.test', 'Baseball Sport ICT (Demo)'],
            'Boxing' => ['boxing.ict@ddopaa2026.test', 'Boxing Sport ICT (Demo)'],
        ];

        foreach ($accounts as $sportName => [$email, $name]) {
            $meetSport = MeetSport::query()
                ->where('meet_id', $meet->id)
                ->whereHas('sport', fn ($query) => $query->where('name', $sportName))
                ->firstOrFail();

            $user = User::query()->firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'password' => 'password',
                'role' => UserRole::Organizer,
                'email_verified_at' => now(),
            ])->save();

            // These dedicated accounts must never retain an ICT assignment
            // to a second sport after the seeder is rerun or changed.
            MeetSportAssignment::query()
                ->where('user_id', $user->id)
                ->where('role', MeetSportAssignmentRole::TournamentICT)
                ->where('meet_sport_id', '!=', $meetSport->id)
                ->delete();

            MeetSportAssignment::query()->updateOrCreate(
                [
                    'meet_sport_id' => $meetSport->id,
                    'user_id' => $user->id,
                    'role' => MeetSportAssignmentRole::TournamentICT->value,
                ],
                [
                    'sport_category_id' => null,
                    'is_lead' => false,
                    'status' => MeetSportAssignmentStatus::Active->value,
                ],
            );
        }

        $this->command?->info('Sport ICT accounts ready for Basketball, Baseball, and Boxing (password: password).');
    }
}
