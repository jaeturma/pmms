<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\UserRole;
use App\Models\User;

/**
 * One definition of "does this user operate this sport" — a Technical
 * Official via the many-to-many `sport_user` pivot (`User::sports()`), a
 * Tournament Manager via the 1:1 `sports.tournament_manager_id` FK
 * (`User::managedSport()`). Every other role operates no sport directly.
 * Extracted once this check reached three call sites (matching this
 * project's own `SearchesAndPaginates` extraction threshold).
 */
trait ScopesToAssignedSport
{
    protected function userOperatesSport(User $user, int $sportId): bool
    {
        return match ($user->role) {
            UserRole::TechnicalOfficial => $user->sports()->whereKey($sportId)->exists(),
            UserRole::TournamentManager => $user->managedSport?->id === $sportId,
            default => false,
        };
    }
}
