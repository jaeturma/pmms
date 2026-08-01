<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleDemoUsersSeeder;

test('it creates one demo account per role and is idempotent', function () {
    (new RoleDemoUsersSeeder)->run();

    foreach (UserRole::cases() as $role) {
        $user = User::query()->where('email', "{$role->value}@pmms.test")->firstOrFail();

        expect($user->role)->toBe($role)
            ->and($user->hasVerifiedEmail())->toBeTrue();
    }

    $countBefore = User::query()->count();
    (new RoleDemoUsersSeeder)->run();

    expect(User::query()->count())->toBe($countBefore);
});

test('it refuses to run outside local or testing environments', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new RoleDemoUsersSeeder)->run())->toThrow(RuntimeException::class);

    app()->detectEnvironment(fn () => 'testing');
});
