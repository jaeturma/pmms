<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Creates one predictable login for every application-level role.
 *
 * These accounts are intentionally separate from meet personnel assignments:
 * assignments are meet data, while a user's role controls application access.
 * The two sport-scoped roles are attached to the first active sport when one
 * exists so their seeded accounts are useful immediately after a full seed.
 */
class RoleAccountSeeder extends Seeder
{
    public function run(): void
    {
        $adminName = config('pmms.admin.name');
        $adminEmail = config('pmms.admin.email');
        $adminPassword = config('pmms.admin.password');

        if (! is_string($adminName) || ! is_string($adminEmail)) {
            throw new RuntimeException('PMMS admin seed configuration is incomplete.');
        }

        if (! is_string($adminPassword) || $adminPassword === '') {
            if (app()->isProduction()) {
                throw new RuntimeException('Set PMMS_ADMIN_PASSWORD before seeding role accounts in production.');
            }

            $adminPassword = 'password';
        }

        $accounts = [
            UserRole::Admin->value => [$adminName, $adminEmail, $adminPassword],
            UserRole::Organizer->value => ['Meet Organizer', 'organizer@pmms.test', $adminPassword],
            UserRole::DelegationOfficer->value => ['Delegation Officer', 'delegation.officer@pmms.test', $adminPassword],
            UserRole::TechnicalOfficial->value => ['Technical Official', 'technical.official@pmms.test', $adminPassword],
            UserRole::TournamentManager->value => ['Tournament Manager', 'tournament.manager@pmms.test', $adminPassword],
            UserRole::Coach->value => ['Coach', 'coach@pmms.test', $adminPassword],
            UserRole::Viewer->value => ['Viewer', 'viewer@pmms.test', $adminPassword],
        ];

        $users = [];

        foreach (UserRole::cases() as $role) {
            [$name, $email, $password] = $accounts[$role->value];
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'email_verified_at' => now(),
            ])->save();
            $users[$role->value] = $user;
        }

        $sport = Sport::query()->where('active', true)->orderBy('display_order')->first();

        if ($sport !== null) {
            $technicalOfficial = $users[UserRole::TechnicalOfficial->value];
            $technicalOfficial->sports()->syncWithoutDetaching([$sport->id]);

            $sport->forceFill([
                'tournament_manager_id' => $users[UserRole::TournamentManager->value]->id,
            ])->save();
        }
    }
}
