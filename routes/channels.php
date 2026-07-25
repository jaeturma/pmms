<?php

use App\Enums\UserRole;
use App\Models\EventMatch;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Mirrors the "Matches — list" authorization row (docs/authorization.md)
 * exactly: Admin/Organizer any match, Delegation Officer only a match
 * involving their own delegation, Viewer never.
 */
Broadcast::channel('match.{matchId}.scoring', function ($user, $matchId) {
    if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
        return true;
    }

    if ($user->role !== UserRole::DelegationOfficer) {
        return false;
    }

    return EventMatch::query()
        ->whereKey($matchId)
        ->whereHas(
            'entries.delegation.officers',
            fn ($officers) => $officers->whereKey($user->getKey()),
        )
        ->exists();
});
