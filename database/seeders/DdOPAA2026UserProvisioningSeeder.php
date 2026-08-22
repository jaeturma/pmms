<?php

namespace Database\Seeders;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\AccountProvision;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ProductionAccountReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DdOPAA2026UserProvisioningSeeder extends Seeder
{
    public function run(): void
    {
        $initialPassword = config('pmms.accounts.default_reset_password');

        if (! is_string($initialPassword) || $initialPassword === '') {
            throw new RuntimeException('Set PMMS_DEFAULT_RESET_PASSWORD before provisioning production accounts.');
        }

        AccountProvision::query()
            ->with(['person.meetSportAssignments', 'person.managementTeamMemberships'])
            ->whereNotIn('status', ['skipped', 'failed', 'disabled'])
            ->orderBy('id')
            ->chunkById(100, function ($provisions) use ($initialPassword): void {
                foreach ($provisions as $provision) {
                    DB::transaction(function () use ($provision, $initialPassword): void {
                        $person = $provision->person;
                        $hasApprovedScope = $person->meetSportAssignments->isNotEmpty()
                            || $person->managementTeamMemberships->isNotEmpty();

                        if (! $hasApprovedScope) {
                            $provision->forceFill(['status' => 'failed'])->save();

                            return;
                        }

                        $user = $person->user;
                        if ($user === null && $provision->linked_user_id !== null) {
                            $user = User::query()->find($provision->linked_user_id);
                        }
                        if ($user === null) {
                            $user = User::query()
                                ->where('username', $provision->suggested_username)
                                ->when($provision->email !== null, fn ($query) => $query
                                    ->orWhere('email', $provision->email))
                                ->first();
                        }

                        $created = false;
                        if ($user === null) {
                            $user = User::query()->create([
                                'name' => $person->full_name,
                                'username' => $provision->suggested_username,
                                'email' => $provision->email,
                                'password' => Hash::make($initialPassword),
                            ]);
                            $user->forceFill([
                                'role' => $person->managementTeamMemberships->isNotEmpty()
                                    ? UserRole::Organizer
                                    : $this->primaryRole($person->meetSportAssignments->pluck('role')->all()),
                                'email_verified_at' => $provision->email === null ? null : now(),
                                'must_change_password' => false,
                            ])->save();
                            $created = true;
                        } else {
                            $user->forceFill([
                                'username' => $provision->suggested_username,
                                'must_change_password' => false,
                            ])->save();
                        }

                        $person->forceFill(['user_id' => $user->id])->save();
                        $person->meetSportAssignments()->update([
                            'status' => MeetSportAssignmentStatus::Active->value,
                        ]);
                        $person->meetSportAssignments
                            ->unique(fn ($assignment): string => $assignment->meet_sport_id.'|'.$assignment->role->value)
                            ->each(fn ($assignment) => $assignment->forceFill(['user_id' => $user->id])->save());
                        $person->managementTeamMemberships()->update(['user_id' => $user->id]);

                        $technicalSportIds = $person->meetSportAssignments()
                            ->where('role', MeetSportAssignmentRole::TechnicalOfficial->value)
                            ->join('meet_sports', 'meet_sports.id', '=', 'meet_sport_assignments.meet_sport_id')
                            ->pluck('meet_sports.sport_id');
                        $user->sports()->syncWithoutDetaching($technicalSportIds);

                        $provision->forceFill([
                            'linked_user_id' => $user->id,
                            'status' => 'active',
                            'activated_at' => $provision->activated_at ?? now(),
                        ])->save();

                        if ($created) {
                            AuditLog::query()->create([
                                'action' => 'user.provisioned',
                                'auditable_type' => $user->getMorphClass(),
                                'auditable_id' => $user->id,
                                'context' => [
                                    'person_id' => $person->id,
                                    'username' => $user->username,
                                    'sport_assignment_ids' => $person->meetSportAssignments->pluck('id')->all(),
                                    'management_membership_ids' => $person->managementTeamMemberships->pluck('id')->all(),
                                ],
                            ]);
                        }
                    });
                }
            });

        if (app()->environment('local')) {
            app(ProductionAccountReport::class)->generate();
        }
    }

    /** @param array<int, MeetSportAssignmentRole|string> $roles */
    private function primaryRole(array $roles): UserRole
    {
        $values = array_map(fn ($role): string => $role instanceof MeetSportAssignmentRole ? $role->value : $role, $roles);

        if (array_intersect($values, [
            MeetSportAssignmentRole::TournamentManager->value,
            MeetSportAssignmentRole::TrackTournamentManager->value,
            MeetSportAssignmentRole::FieldTournamentManager->value,
            MeetSportAssignmentRole::BoysTournamentManager->value,
            MeetSportAssignmentRole::GirlsTournamentManager->value,
            MeetSportAssignmentRole::CategoryTournamentManager->value,
        ]) !== []) {
            return UserRole::TournamentManager;
        }

        if (array_intersect($values, [
            MeetSportAssignmentRole::AssistantTournamentManager->value,
            MeetSportAssignmentRole::TournamentSecretary->value,
            MeetSportAssignmentRole::TournamentICT->value,
        ]) !== []) {
            return UserRole::Organizer;
        }

        return UserRole::TechnicalOfficial;
    }
}
