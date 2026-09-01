<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\SportRosterMember;
use App\Models\User;

test('TM TO secretary and ICT see registration athletes only in their assigned sport', function (MeetSportAssignmentRole $role) {
    $meet = Meet::current();
    $assignedSport = MeetSport::factory()->create(['meet_id' => $meet->id]);
    $otherSport = MeetSport::factory()->create(['meet_id' => $meet->id]);
    $user = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $assignedSport->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $visibleAthlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
    ]);
    $hiddenAthlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
    ]);
    SportRosterMember::query()->create([
        'meet_sport_id' => $assignedSport->id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $visibleAthlete->id,
        'level' => 'secondary',
        'gender' => 'boys',
    ]);
    SportRosterMember::query()->create([
        'meet_sport_id' => $otherSport->id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $hiddenAthlete->id,
        'level' => 'secondary',
        'gender' => 'boys',
    ]);

    $this->actingAs($user)
        ->get('/athletes')
        ->assertInertia(fn ($page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.id', $visibleAthlete->id));

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.can_view_tournament_athletes', true));
})->with([
    'Tournament Manager' => MeetSportAssignmentRole::TournamentManager,
    'Technical Official' => MeetSportAssignmentRole::TechnicalOfficial,
    'Tournament Secretary' => MeetSportAssignmentRole::TournamentSecretary,
    'Tournament ICT' => MeetSportAssignmentRole::TournamentICT,
]);
