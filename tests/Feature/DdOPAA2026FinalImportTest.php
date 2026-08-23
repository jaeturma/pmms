<?php

use App\Actions\Fortify\CreateNewUser;
use App\Enums\UserRole;
use App\Models\AccountProvision;
use App\Models\CoachOnboardingRequest;
use App\Models\District;
use App\Models\DistrictSportsCoordinatorAssignment;
use App\Models\Event;
use App\Models\ManagementTeamMember;
use App\Models\MeetSportAssignment;
use App\Models\Person;
use App\Models\User;
use App\Services\DdOPAA2026Source;
use App\Services\RegistrationCodeChallenge;
use Database\Seeders\DdOPAA2026FinalSeeder;

test('the reviewed source fixture has the final expected record counts', function () {
    $source = app(DdOPAA2026Source::class);

    expect($source->people())->toHaveCount(780)
        ->and($source->twgUnits())->toHaveCount(25)
        ->and($source->twgMemberships())->toHaveCount(144)
        ->and($source->dscAssignments())->toHaveCount(18)
        ->and($source->sportPersonnelAssignments())->toHaveCount(623)
        ->and($source->accountProvisions())->toHaveCount(622);
});

test('the final import is idempotent and preserves one person with multiple assignments', function () {
    $this->seed(DdOPAA2026FinalSeeder::class);

    $first = [
        Person::count(), ManagementTeamMember::count(), DistrictSportsCoordinatorAssignment::count(),
        MeetSportAssignment::count(), AccountProvision::count(),
    ];
    $this->seed(DdOPAA2026FinalSeeder::class);

    expect([Person::count(), ManagementTeamMember::count(), DistrictSportsCoordinatorAssignment::count(), MeetSportAssignment::count(), AccountProvision::count()])
        ->toBe($first)
        // Includes 34 playing-venue coordinators normalized from Venues.xlsx
        // in addition to the 780 people in the original final workbook.
        ->and(Person::count())->toBe(814)
        ->and(ManagementTeamMember::count())->toBe(144)
        ->and(DistrictSportsCoordinatorAssignment::count())->toBe(18)
        ->and(MeetSportAssignment::count())->toBe(641)
        // The explicit 622-row account worksheet is supplemented by assigned
        // TWG/sport personnel so nobody with an operational role is omitted.
        ->and(AccountProvision::count())->toBe(762)
        ->and(Person::query()->whereHas('meetSportAssignments', fn ($q) => $q, '>', 1)->exists())->toBeTrue()
        ->and(AccountProvision::query()->where('status', 'pending')->count())->toBeGreaterThan(0);
});

test('imported twg members without accounts render on the management teams page', function () {
    $this->seed(DdOPAA2026FinalSeeder::class);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management-teams')
        ->assertOk();
});

test('imported sport personnel without accounts render on the assignments page', function () {
    $this->seed(DdOPAA2026FinalSeeder::class);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/meet-sport-assignments')
        ->assertOk();
});

test('requesting coach onboarding does not self grant coach authority', function () {
    $municipality = District::factory()->create();
    $event = Event::factory()->create();
    request()->setLaravelSession(app('session.store'));
    app(RegistrationCodeChallenge::class)->generate(request());
    $action = app(CreateNewUser::class);
    $user = $action->create([
        'name' => 'Pending Coach',
        'email' => 'pending-coach@example.test',
        'password' => 'Secure#Pass2026',
        'password_confirmation' => 'Secure#Pass2026',
        'account_type' => 'coach',
        'district_id' => $municipality->id,
        'event_ids' => [$event->id],
        'code_challenge' => 'ABC12',
    ]);

    expect($user->fresh()->role)->toBe(UserRole::Viewer)
        ->and(CoachOnboardingRequest::query()->where('user_id', $user->id)->where('status', 'pending')->exists())->toBeTrue();
});
