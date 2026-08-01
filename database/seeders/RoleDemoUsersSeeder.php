<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * One demo login per role, for local development and manual QA — distinct
 * from `AdminUserSeeder`'s production administrator account. Every account
 * shares the fixed password "password" via a fixed `role@pmms.test` email,
 * so this seeder refuses to run outside local/testing.
 */
class RoleDemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('RoleDemoUsersSeeder creates fixed-password demo accounts and must not run outside local/testing.');
        }

        foreach (UserRole::cases() as $role) {
            $email = "{$role->value}@pmms.test";

            User::query()->firstOrNew(['email' => $email])->forceFill([
                'name' => $role->label().' (Demo)',
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => $role,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
