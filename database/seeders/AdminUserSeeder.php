<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial administrator account.
     *
     * Credentials come from the PMMS_ADMIN_* environment variables (see
     * .env.example). Outside production a missing password falls back to
     * "password" for local convenience; in production it must be set.
     */
    public function run(): void
    {
        $name = config('pmms.admin.name');
        $email = config('pmms.admin.email');
        $password = config('pmms.admin.password');

        if (! is_string($name) || ! is_string($email)) {
            throw new RuntimeException('PMMS admin seed configuration is incomplete.');
        }

        if (! is_string($password) || $password === '') {
            if (app()->isProduction()) {
                throw new RuntimeException('Set PMMS_ADMIN_PASSWORD before seeding the administrator in production.');
            }

            $password = 'password';
        }

        User::query()->firstOrNew(['email' => $email])->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ])->save();
    }
}
