<?php

use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\RequirementStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\User;

test('approved coach scope limits registration and DSAC accreditation confirms the official entry', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id, 'gender' => 'boys', 'age_division' => 'secondary']);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id, 'status' => DelegationStatus::Draft]);
    $school = schoolForDelegation($delegation);
    $coach = User::factory()->coach()->create();
    $manager = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id, 'user_id' => $manager->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($coach)->post('/coach/assignment-requests', [
        'meet_sport_id' => $meetSport->id, 'event_id' => $event->id,
        'delegation_id' => $delegation->id, 'school_id' => $school->id,
    ])->assertSessionHasNoErrors();
    $request = CoachAssignmentRequest::query()->sole();
    $this->actingAs($manager)->patch("/coach/assignment-requests/{$request->id}", ['status' => 'approved'])->assertSessionHasNoErrors();

    $athletePayload = [
        'delegation_id' => $delegation->id, 'school_id' => $school->id,
        'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => '123456789012', 'grade_level' => 9,
    ];
    $this->actingAs($coach)->post('/athletes', $athletePayload)->assertSessionHasNoErrors();
    $athlete = Athlete::query()->sole();
    $this->actingAs($coach)->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])->assertSessionHasNoErrors();
    expect(Entry::query()->sole()->status)->toBe(EntryStatus::Submitted);

    $delegation->forceFill(['status' => DelegationStatus::Approved])->save();
    $athlete->eligibilityReview()->update([
        'status' => 'approved',
        'decided_at' => now(),
    ]);
    EligibilityDocument::factory()->create(['athlete_id' => $athlete->id, 'status' => RequirementStatus::Verified]);
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::DivisionScreeningAndAccreditation]);
    $dsac = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'role_title' => 'Team Leader',
        'status' => ManagementTeamMemberStatus::Active,
    ])->user;
    $this->actingAs($dsac)->post('/accreditations', ['athlete_id' => $athlete->id])->assertSessionHasNoErrors();

    expect($athlete->accreditation()->exists())->toBeTrue()
        ->and(Entry::query()->sole()->fresh()->status)->toBe(EntryStatus::Confirmed);
});

test('an active ICT team member can review and approve a coach registration', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = User::factory()->coach()->create();
    $coachRequest = CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'pending',
    ]);

    $ict = User::factory()->create();
    $ictTeam = ManagementTeam::factory()->create([
        'meet_id' => $meet->id,
        'team_type' => ManagementTeamType::ICT,
    ]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $ictTeam->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($ict)
        ->get('/coach/assignment-requests')
        ->assertOk();

    $this->actingAs($ict)
        ->patch("/coach/assignment-requests/{$coachRequest->id}", ['status' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($coachRequest->fresh()->status)->toBe('approved')
        ->and($coach->fresh()->role)->toBe(UserRole::Coach);
});

test('an inactive ICT team membership cannot approve a coach registration', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coachRequest = CoachAssignmentRequest::query()->create([
        'user_id' => User::factory()->coach()->create()->id,
        'meet_sport_id' => $meetSport->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'pending',
    ]);
    $ict = User::factory()->create();
    $ictTeam = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $ictTeam->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Ended,
    ]);

    $this->actingAs($ict)
        ->patch("/coach/assignment-requests/{$coachRequest->id}", ['status' => 'approved'])
        ->assertForbidden();
});
