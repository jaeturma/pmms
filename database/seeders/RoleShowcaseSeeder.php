<?php

namespace Database\Seeders;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\PersonnelRole;
use App\Enums\UserRole;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Personnel;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One login account per personnel-role type this app supports, on top of
 * `Ddopaa2026ShowcaseSeeder`'s own Admin/Organizer/Technical-Official
 * trio — so a reviewer can sign in as a Tournament Manager, Assistant
 * Tournament Manager, Tournament ICT, or Tournament Secretary
 * (`MeetSportAssignmentRole`, WP-REALIGN-07's per-meet-sport personnel
 * layer) or a Coach (`UserRole::Coach`, WP-REALIGN-05's roster-linked
 * login) without hand-creating anything first. Athletes are deliberately
 * not duplicated here — `Ddopaa2026ShowcaseSeeder` already seeds a full
 * roster (88 athletes across all 11 delegations) and athletes never get
 * a login account of their own (`Athlete` has no `user_id`), so there is
 * nothing further to seed for that role type; this seeder's coach
 * accounts link directly into that existing roster instead of creating a
 * second, redundant one.
 *
 * Requires `Ddopaa2026ShowcaseSeeder` to have already run (needs its meet
 * and delegations to exist) — no-ops with a warning if it hasn't.
 * Guarded to local/testing, same as every other demo seeder in this
 * project. Idempotent throughout (`firstOrNew`/`firstOrCreate`), safe to
 * re-run.
 */
class RoleShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->first();

        if ($meet === null) {
            $this->command->warn('RoleShowcaseSeeder skipped: run Ddopaa2026ShowcaseSeeder first (needs its meet and delegations).');

            return;
        }

        $this->tournamentPersonnel($meet);
        $this->coachAccounts();
    }

    /**
     * Tournament Manager / Assistant Tournament Manager / Tournament ICT
     * / Tournament Secretary, each a distinct login assigned against
     * Basketball's `MeetSport` row for this meet (the flagship live-scoring
     * sport the rest of the showcase dataset already features).
     * `MeetSportAssignment` only accepts Admin/Organizer/Technical-Official
     * accounts (`MeetSportAssignmentController::store()`'s own
     * `userOptions` scope), so each gets `UserRole::TechnicalOfficial` —
     * the same base access level the existing single Technical Official
     * demo account already uses; `role` on the assignment row (not the
     * user) is what actually distinguishes them. All four are marked
     * Active, not Pending, so they show up ready-to-use rather than
     * needing an extra confirmation step first.
     */
    private function tournamentPersonnel(Meet $meet): void
    {
        $basketball = Sport::query()->where('name', 'Basketball')->firstOrFail();

        $meetSport = MeetSport::query()
            ->where('meet_id', $meet->id)
            ->where('sport_id', $basketball->id)
            ->firstOrFail();

        // email => [name, assignment role, is_lead]
        $roles = [
            'tournament.manager@ddopaa2026.test' => ['Tournament Manager (Demo)', MeetSportAssignmentRole::TournamentManager, true, UserRole::TournamentManager],
            'assistant.manager@ddopaa2026.test' => ['Assistant Tournament Manager (Demo)', MeetSportAssignmentRole::AssistantTournamentManager, false, UserRole::TournamentManager],
            'ict@ddopaa2026.test' => ['Tournament ICT (Demo)', MeetSportAssignmentRole::TournamentICT, false, UserRole::Organizer],
            'secretary@ddopaa2026.test' => ['Tournament Secretary (Demo)', MeetSportAssignmentRole::TournamentSecretary, false, UserRole::Organizer],
        ];

        foreach ($roles as $email => [$name, $role, $isLead, $userRole]) {
            $user = $this->account($email, $name, $userRole);

            MeetSportAssignment::query()->firstOrCreate(
                ['meet_sport_id' => $meetSport->id, 'user_id' => $user->id, 'role' => $role->value],
                ['is_lead' => $isLead, 'status' => MeetSportAssignmentStatus::Active->value],
            );
        }
    }

    /**
     * Links real logins to Compostela's existing Coach and Assistant
     * Coach roster rows (the first municipality
     * `Ddopaa2026ShowcaseSeeder` seeds) rather than creating second,
     * redundant `Personnel` records. `user_id` is deliberately outside
     * `Personnel`'s Fillable (see the model's own docblock — linking a
     * roster row to a login is a controlled action, never
     * mass-assignable), so this goes through `forceFill()` directly, the
     * one place that's expected.
     */
    private function coachAccounts(): void
    {
        // email => [personnel role, account name]
        $coaches = [
            'coach@ddopaa2026.test' => [PersonnelRole::Coach, 'Coach (Demo)'],
            'assistant.coach@ddopaa2026.test' => [PersonnelRole::AssistantCoach, 'Assistant Coach (Demo)'],
        ];

        foreach ($coaches as $email => [$personnelRole, $accountLabel]) {
            $personnel = Personnel::query()
                ->whereHas('delegation.district', fn ($q) => $q->where('name', 'Compostela'))
                ->where('role', $personnelRole->value)
                ->first();

            if ($personnel === null) {
                continue;
            }

            $user = $this->account($email, "{$personnel->first_name} {$personnel->last_name} ({$accountLabel})", UserRole::Coach);

            if ($personnel->user_id !== $user->id) {
                $personnel->forceFill(['user_id' => $user->id])->save();
            }
        }
    }

    /**
     * `role`/`email_verified_at` aren't mass-assignable
     * (`docs/authorization.md`) — `firstOrNew()->forceFill()->save()` in
     * one shot, the same pattern `AdminUserSeeder`/
     * `Ddopaa2026ShowcaseSeeder` use for every account they create.
     * Password is the same documented local dev default, never used
     * outside local/testing (this whole seeder returns early otherwise).
     */
    private function account(string $email, string $name, UserRole $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'password',
            'role' => $role,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
