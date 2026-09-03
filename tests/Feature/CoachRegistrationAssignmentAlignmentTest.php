<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Event;
use App\Models\FileUpload;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\School;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\User;
use App\Services\CoachAccessService;
use Inertia\Testing\AssertableInertia;

function coachApplicationContext(string $sportName = 'Swimming'): array
{
    $meet = Meet::factory()->registrationOpen()->create(['is_active' => true]);
    $sport = Sport::factory()->create(['name' => $sportName, 'code' => strtoupper($sportName)]);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $district = District::factory()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id, 'district_id' => $district->id, 'school_id' => null]);
    $school = School::factory()->create(['district_id' => $district->id]);
    $events = Event::factory()->count(2)->create(['sport_id' => $sport->id, 'gender' => 'boys', 'age_division' => 'secondary']);
    $meet->events()->attach($events);

    return [$meet, $sport, $meetSport, $delegation, $school, $events];
}

test('coach index searches registrations and filters their status', function () {
    [, , $meetSport, $delegation, $school] = coachApplicationContext();
    $matchingCoach = User::factory()->create(['name' => 'Maria Santos']);
    $otherCoach = User::factory()->create(['name' => 'Pedro Reyes']);

    foreach ([[$matchingCoach, 'approved'], [$otherCoach, 'pending']] as [$coach, $status]) {
        CoachOnboardingRequest::query()->create([
            'user_id' => $coach->id,
            'meet_sport_id' => $meetSport->id,
            'delegation_id' => $delegation->id,
            'school_id' => $school->id,
            'district_id' => $school->district_id,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)
        ->get('/coach/assignment-requests?search=Maria&status=approved')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('registrations.data', 1)
            ->where('registrations.data.0.coach', 'Maria Santos')
            ->where('filters.search', 'Maria')
            ->where('filters.status', 'approved'));
});

test('coach self registration selects sport only and rejects self assigned event scope', function () {
    Setting::current()->forceFill(['coach_registration_enabled' => true])->save();
    [, , $meetSport, $delegation, $school, $events] = coachApplicationContext();
    $payload = [
        'name' => 'Sport Only Coach', 'email' => 'sport-only@example.test',
        'password' => 'Secure#Pass2026', 'password_confirmation' => 'Secure#Pass2026',
        'account_type' => 'coach', 'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'code_challenge' => 'ABC12',
    ];

    $this->get(route('register'));
    $this->post(route('register.store'), [...$payload, 'event_ids' => [$events->first()->id]])
        ->assertSessionHasErrors('event_ids');
    $this->get(route('register'));
    $this->post(route('register.store'), $payload)->assertSessionHasNoErrors();

    $application = CoachOnboardingRequest::query()->sole();
    expect($application->meet_sport_id)->toBe($meetSport->id)
        ->and($application->delegation_id)->toBe($delegation->id)
        ->and($application->school_id)->toBeNull()
        ->and($application->events)->toBeEmpty()
        ->and($application->user->role)->toBe(UserRole::Viewer)
        ->and(app(CoachAccessService::class)->eventIds($application->user))->toBeEmpty();
});

test('tournament ict reviews only its sport and assigns scope during approval', function () {
    [, , $swimmingMeetSport, $delegation, $school, $events] = coachApplicationContext();
    [, , $basketballMeetSport, $basketballDelegation, $basketballSchool] = coachApplicationContext('Basketball');
    $coach = User::factory()->create(['role' => UserRole::Viewer, 'approval_status' => 'pending']);
    $application = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id, 'meet_sport_id' => $swimmingMeetSport->id,
        'delegation_id' => $delegation->id, 'school_id' => $school->id,
        'district_id' => $school->district_id, 'status' => 'pending', 'submitted_at' => now(),
    ]);
    $swimmingIct = User::factory()->create();
    MeetSportAssignment::factory()->create(['user_id' => $swimmingIct->id, 'meet_sport_id' => $swimmingMeetSport->id, 'role' => MeetSportAssignmentRole::TournamentICT, 'status' => MeetSportAssignmentStatus::Active]);
    $basketballIct = User::factory()->create();
    MeetSportAssignment::factory()->create(['user_id' => $basketballIct->id, 'meet_sport_id' => $basketballMeetSport->id, 'role' => MeetSportAssignmentRole::TournamentICT, 'status' => MeetSportAssignmentStatus::Active]);
    $swimmingCoach = User::factory()->coach()->create();
    CoachAssignmentRequest::query()->create([
        'user_id' => $swimmingCoach->id, 'meet_sport_id' => $swimmingMeetSport->id,
        'delegation_id' => $delegation->id, 'school_id' => $school->id,
        'scope_type' => 'sport', 'status' => 'approved', 'assigned_at' => now(),
    ]);
    $basketballCoach = User::factory()->coach()->create();
    CoachAssignmentRequest::query()->create([
        'user_id' => $basketballCoach->id, 'meet_sport_id' => $basketballMeetSport->id,
        'delegation_id' => $basketballDelegation->id, 'school_id' => $basketballSchool->id,
        'scope_type' => 'sport', 'status' => 'approved', 'assigned_at' => now(),
    ]);

    $this->actingAs($swimmingIct)->get('/coach/assignment-requests')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('registrations.data', 1)
        ->has('requests.data', 1)
        ->where('requests.data.0.coach', $swimmingCoach->getRawOriginal('name'))
        ->where('sportOptions', fn ($sports) => collect($sports)->pluck('id')->all() === [$swimmingMeetSport->sport_id])
        ->where('registrations.per_page', 10));
    $this->actingAs($basketballIct)->get('/coach/assignment-requests')->assertInertia(fn (AssertableInertia $page) => $page
        ->has('registrations.data', 0)
        ->has('requests.data', 1)
        ->where('requests.data.0.coach', $basketballCoach->getRawOriginal('name')));
    $this->actingAs($basketballIct)->patch("/coach/onboarding-requests/{$application->id}", ['status' => 'approved', 'event_ids' => [$events->first()->id]])->assertForbidden();

    $this->actingAs($swimmingIct)->patch("/coach/onboarding-requests/{$application->id}", ['status' => 'approved', 'event_ids' => [$events->first()->id]])->assertSessionDoesntHaveErrors();
    expect($application->fresh()->profile_upload_id)->toBeNull();
    expect($coach->fresh()->role)->toBe(UserRole::Coach)
        ->and($coach->approvedCoachEventIdsForDelegation($delegation)->all())->toBe([$events->first()->id]);
    $coach->refresh();

    $otherDelegation = Delegation::factory()->create(['meet_id' => $delegation->meet_id]);
    expect($coach->hasApprovedCoachScope($delegation, $events->first()))->toBeTrue()
        ->and($coach->hasApprovedCoachScope($delegation, $events->last()))->toBeFalse()
        ->and($coach->hasApprovedCoachScope($otherDelegation, $events->first()))->toBeFalse();

    $this->actingAs($swimmingIct)->patch("/coach/onboarding-requests/{$application->id}", ['status' => 'approved', 'event_ids' => [$events->last()->id]])->assertSessionDoesntHaveErrors();
    expect($coach->approvedCoachEventIdsForDelegation($delegation))->toHaveCount(2);

    $firstAssignment = CoachAssignmentRequest::query()->where('event_id', $events->first()->id)->sole();
    $this->actingAs($swimmingIct)->patch("/coach/assignment-requests/{$firstAssignment->id}", ['status' => 'inactive'])->assertSessionDoesntHaveErrors();
    expect($firstAssignment->fresh()->ended_at)->not->toBeNull()
        ->and($coach->approvedCoachEventIdsForDelegation($delegation)->all())->toBe([$events->last()->id]);
});

test('one coach can hold multiple event and sport scopes without duplicate accounts', function () {
    [, , $swimming, $delegation, $school, $swimmingEvents] = coachApplicationContext();
    [, , $athletics, , , $athleticsEvents] = coachApplicationContext('Athletics');
    $coach = User::factory()->coach()->create();
    foreach ([[$swimming, $swimmingEvents->first()], [$athletics, $athleticsEvents->first()]] as [$meetSport, $event]) {
        CoachAssignmentRequest::query()->create([
            'user_id' => $coach->id, 'meet_sport_id' => $meetSport->id, 'event_id' => $event->id,
            'delegation_id' => $delegation->id, 'school_id' => $school->id,
            'scope_type' => 'event', 'status' => 'approved', 'assigned_at' => now(),
        ]);
    }

    expect(User::query()->whereKey($coach->id)->count())->toBe(1)
        ->and($coach->approvedCoachEventIds())->toHaveCount(2);
});

test('coach selects events on a separate page and only ict approves the account', function () {
    [, , $meetSport, $delegation, , $events] = coachApplicationContext();
    $coach = User::factory()->create(['role' => UserRole::Viewer, 'approval_status' => 'pending']);
    $application = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id, 'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id, 'district_id' => $delegation->district_id,
        'status' => 'pending', 'submitted_at' => now(),
        'profile_upload_id' => FileUpload::factory()->create()->id,
    ]);
    $ict = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id, 'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($coach)->get("/coach/onboarding-requests/{$application->id}/assignments")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('coach/manage-assignments')
            ->where('registration.sport', $meetSport->sport->name)
            ->has('events', 2)->where('canApprove', false));
    $this->actingAs($coach)->put("/coach/onboarding-requests/{$application->id}/assignments", [
        'event_ids' => $events->modelKeys(),
    ])->assertSessionHasNoErrors();
    expect($application->fresh()->events)->toHaveCount(2)
        ->and($coach->fresh()->approval_status)->toBe('pending');

    $this->actingAs($coach)->patch("/coach/onboarding-requests/{$application->id}", ['status' => 'approved'])->assertForbidden();
    $this->actingAs($ict)->patch("/coach/onboarding-requests/{$application->id}", ['status' => 'approved'])->assertSessionHasNoErrors();
    expect($coach->fresh()->role)->toBe(UserRole::Coach)
        ->and($coach->fresh()->approvedCoachEventIdsForDelegation($delegation))->toHaveCount(2);
});

test('applied sport shows catalog events even when the meet event pivot is missing', function () {
    [$meet, $sport, $meetSport, $delegation] = coachApplicationContext();
    $meet->events()->detach();
    $additionalEvents = Event::factory()->count(18)->create([
        'sport_id' => $sport->id,
        'gender' => 'boys',
        'age_division' => 'secondary',
        'active' => true,
    ]);
    $event = $additionalEvents->first();
    $coach = User::factory()->create(['role' => UserRole::Viewer, 'approval_status' => 'pending']);
    $application = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'district_id' => $delegation->district_id,
        'status' => 'pending',
    ]);

    $this->actingAs($coach)->get("/coach/onboarding-requests/{$application->id}/assignments")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('registration.sport', $sport->name)
            ->has('events', 20));

    $this->actingAs($coach)->put("/coach/onboarding-requests/{$application->id}/assignments", [
        'event_ids' => [$event->id],
    ])->assertSessionHasNoErrors();

    expect($meet->events()->whereKey($event)->exists())->toBeTrue()
        ->and($application->fresh()->meet_sport_id)->toBe($meetSport->id)
        ->and($application->fresh()->events->modelKeys())->toBe([$event->id]);
});

test('legacy coach registration with only an event derives its sport and repairs assignment scope', function () {
    [$meet, $sport, , $delegation, , $events] = coachApplicationContext();
    $coach = User::factory()->create(['role' => UserRole::Viewer, 'approval_status' => 'pending']);
    $application = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id, 'meet_sport_id' => null,
        'delegation_id' => $delegation->id, 'district_id' => $delegation->district_id,
        'event_id' => $events->first()->id, 'status' => 'pending', 'submitted_at' => now(),
    ]);
    $this->actingAs($coach)->get("/coach/onboarding-requests/{$application->id}/assignments")
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('coach/manage-assignments')
        ->where('registration.sport', $sport->name)
        ->where('selectedEventIds', [$events->first()->id])
        ->has('events', 2));
    $this->actingAs($coach)->put("/coach/onboarding-requests/{$application->id}/assignments", [
        'event_ids' => [$events->last()->id],
    ])->assertSessionHasNoErrors();

    expect($application->fresh()->meet_sport_id)->not->toBeNull()
        ->and($application->fresh()->meetSport->meet_id)->toBe($meet->id)
        ->and($application->fresh()->events->modelKeys())->toBe([$events->last()->id]);
});

test('an approved coach can assign another event without a school link', function () {
    [, , $meetSport, $delegation, , $events] = coachApplicationContext();
    $coach = User::factory()->coach()->create(['approval_status' => 'approved']);
    CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'school_id' => null,
        'district_id' => $delegation->district_id,
        'status' => 'approved',
    ]);

    $this->actingAs($coach)->get('/coach/assignment-requests')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canRequest', true)
            ->has('options', 2));

    $this->actingAs($coach)->post('/coach/assignment-requests', [
        'meet_sport_id' => $meetSport->id,
        'event_id' => $events->first()->id,
        'delegation_id' => $delegation->id,
    ])->assertSessionDoesntHaveErrors();

    $assignment = CoachAssignmentRequest::query()->sole();
    expect($assignment->status)->toBe('approved')
        ->and($assignment->school_id)->toBeNull()
        ->and($coach->approvedCoachEventIdsForDelegation($delegation)->all())
        ->toBe([$events->first()->id]);
});
