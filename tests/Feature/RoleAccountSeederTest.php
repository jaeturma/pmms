<?php

use App\Enums\UserRole;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\RoleAccountSeeder;

test('it seeds one usable account for every application role', function () {
    Sport::factory()->create(['active' => true, 'display_order' => 1]);

    $this->seed(RoleAccountSeeder::class);
    $this->seed(RoleAccountSeeder::class);

    foreach (UserRole::cases() as $role) {
        expect(User::query()->where('role', $role->value)->count())
            ->toBe(1, "Expected exactly one seeded {$role->value} account");
    }

    expect(User::query()->where('role', UserRole::TechnicalOfficial->value)->firstOrFail()->sports()->count())->toBe(1)
        ->and(Sport::query()->firstOrFail()->tournamentManager)
        ->not->toBeNull()
        ->role->toBe(UserRole::TournamentManager);
});
