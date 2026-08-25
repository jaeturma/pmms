<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\School;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\User;
use App\Services\CoachAccessService;
use App\Services\RegistrationCodeChallenge;
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

test('coach self registration selects sport only and rejects self assigned event scope', function () {
    Setting::current()->forceFill(['coach_registration_enabled' => true])->save();
    [, , $meetSport, $delegation, $school, $events] = coachApplicationContext();
    $payload = [
        'name' => 'Sport Only Coach', 'email' => 'sport-only@example.test',
        'password' => 'Secure#Pass2026', 'password_confirmation' => 'Secure#Pass2026',
        'account_type' => 'coach', 'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id, 'school_id' => $school->id,
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
        ->and($application->school_id)->toBe($school->id)
        ->and($application->events)->toBeEmpty()
        ->and($application->user->role)->toBe(UserRole::Viewer)
        ->and(app(CoachAccessService::class)->eventIds($application->user))->toBeEmpty();
});

test('tournament ict reviews only its sport and assigns scope during approval', function () {
    [, , $swimmingMeetSport, $delegation, $school, $events] = coachApplicationContext();
    [, , $basketballMeetSport] = coachApplicationContext('Basketball');
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

    $this->actingAs($swimmingIct)->get('/coach/assignment-requests')->assertInertia(fn (AssertableInertia $page) => $page->has('registrations', 1));
    $this->actingAs($basketballIct)->get('/coach/assignment-requests')->assertInertia(fn (AssertableInertia $page) => $page->has('registrations', 0));
    $this->actingAs($basketballIct)->patch("/coach/onboarding-requests/{$application->id}", ['status' => 'approved', 'event_ids' => [$events->first()->id]])->assertForbidden();

    $this->actingAs($swimmingIct)->patch("/coach/onboarding-requests/{$application->id}", ['status' => 'approved', 'event_ids' => [$events->first()->id]])->assertSessionDoesntHaveErrors();
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
