<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MealType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\PersonnelRole;
use App\Models\AuditLog;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\MealEntitlement;
use App\Models\MealSchedule;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

function mealEligibleUser(ManagementTeamType $type = ManagementTeamType::ICT): User
{
    $user = User::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => Meet::current()->id, 'team_type' => $type]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id, 'user_id' => $user->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $user;
}

function currentMeal(bool $enforce = true): MealSchedule
{
    return MealSchedule::factory()->create([
        'meet_id' => Meet::current()->id,
        'meal_type' => MealType::Lunch,
        'date' => now()->toDateString(),
        'starts_at' => now()->subHour()->format('H:i'),
        'ends_at' => now()->addHour()->format('H:i'),
        'enforce_serving_time' => $enforce,
    ]);
}

test('eligible user sees their own meal stub while coach and ordinary user cannot', function () {
    currentMeal();
    $eligible = mealEligibleUser();

    $this->actingAs($eligible)->get('/meal-stub')->assertInertia(fn (AssertableInertia $page) => $page
        ->component('meal-stub/show')->has('meals', 1)->where('person.name', $eligible->name));
    $this->actingAs(User::factory()->coach()->create())->get('/meal-stub')->assertForbidden();
    $this->actingAs(User::factory()->create())->get('/meal-stub')->assertForbidden();
});

test('dashboard shares Meals access for management and food users', function () {
    foreach ([ManagementTeamType::ICT, ManagementTeamType::Food, ManagementTeamType::MeetManagement] as $type) {
        $user = mealEligibleUser($type);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.can_access_meal_stub', true));
    }
});

test('dashboard shares Meals access for eligible tournament assignments', function () {
    foreach ([
        MeetSportAssignmentRole::TournamentManager,
        MeetSportAssignmentRole::AssistantTournamentManager,
        MeetSportAssignmentRole::TournamentICT,
        MeetSportAssignmentRole::TournamentSecretary,
        MeetSportAssignmentRole::TechnicalOfficial,
    ] as $role) {
        $user = User::factory()->create();
        $meetSport = MeetSport::factory()->create(['meet_id' => Meet::current()->id]);
        MeetSportAssignment::factory()->create([
            'meet_sport_id' => $meetSport->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => MeetSportAssignmentStatus::Active,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.can_access_meal_stub', true));
    }
});

test('coaches assistant coaches and unassigned users do not receive Meals access', function () {
    $coach = User::factory()->coach()->create();
    $assistantCoach = mealEligibleUser();
    $personnel = Personnel::factory()->create(['role' => PersonnelRole::AssistantCoach]);
    $personnel->forceFill(['user_id' => $assistantCoach->id])->save();
    $unassigned = User::factory()->create();

    foreach ([$coach, $assistantCoach, $unassigned] as $user) {
        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.can_access_meal_stub', false));
        $this->actingAs($user)->get('/meal-stub')->assertForbidden();
    }
});

test('meal display status is derived from the configured serving window', function (
    string $starts,
    string $ends,
    string $expectedStatus,
) {
    config(['app.timezone' => 'Asia/Manila']);
    $this->travelTo(Carbon::parse('2026-09-05 12:00:00', 'Asia/Manila'));
    MealSchedule::factory()->create([
        'meet_id' => Meet::current()->id,
        'date' => '2026-09-05',
        'starts_at' => $starts,
        'ends_at' => $ends,
    ]);
    $user = mealEligibleUser();

    $this->actingAs($user)->get('/meal-stub')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('meals.0.display_status', $expectedStatus));

    $this->travelBack();
})->with([
    'not yet available' => ['13:00', '14:00', 'upcoming'],
    'available' => ['11:00', '13:00', 'available'],
    'expired' => ['10:00', '11:00', 'expired'],
]);

test('consumed remains the effective status after the serving window ends', function () {
    config(['app.timezone' => 'Asia/Manila']);
    $this->travelTo(Carbon::parse('2026-09-05 10:30:00', 'Asia/Manila'));
    MealSchedule::factory()->create([
        'meet_id' => Meet::current()->id,
        'date' => '2026-09-05',
        'starts_at' => '09:30',
        'ends_at' => '11:30',
    ]);
    $user = mealEligibleUser();
    $this->actingAs($user)->get('/meal-stub');
    $entitlement = MealEntitlement::query()->where('user_id', $user->id)->sole();
    $this->actingAs($user)->post("/meal-stub/{$entitlement->id}/consume")->assertSessionHasNoErrors();

    $this->travelTo(Carbon::parse('2026-09-05 12:00:00', 'Asia/Manila'));
    $this->actingAs($user)->get('/meal-stub')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('meals.0.display_status', 'consumed'));

    $this->travelBack();
});

test('user consumes only their own current meal once with self method', function () {
    $this->travelTo(Carbon::parse('2026-09-05 12:00:00'));
    currentMeal();
    $user = mealEligibleUser();
    $other = mealEligibleUser();
    $this->actingAs($user)->get('/meal-stub');
    $own = MealEntitlement::query()->where('user_id', $user->id)->sole();
    $foreign = MealEntitlement::query()->where('user_id', $other->id)->sole();

    $this->actingAs($user)->post("/meal-stub/{$foreign->id}/consume")->assertForbidden();
    $this->actingAs($user)->post("/meal-stub/{$own->id}/consume")->assertSessionHasNoErrors();
    $this->actingAs($user)->post("/meal-stub/{$own->id}/consume")->assertSessionHasErrors('meal');

    expect($own->fresh()->status)->toBe('consumed')
        ->and($own->fresh()->consumption_method)->toBe('self')
        ->and($own->fresh()->consumed_by_user_id)->toBe($user->id)
        ->and(AuditLog::query()->where('action', 'meal.consumed')->exists())->toBeTrue();

    $this->travelBack();
});

test('serving time is enforced for self consumption', function (string $starts, string $ends) {
    $this->travelTo(Carbon::parse('2026-09-05 12:00:00'));
    MealSchedule::factory()->create([
        'meet_id' => Meet::current()->id, 'date' => now()->toDateString(),
        'starts_at' => $starts, 'ends_at' => $ends, 'enforce_serving_time' => true,
    ]);
    $user = mealEligibleUser();
    $this->actingAs($user)->get('/meal-stub');
    $entitlement = MealEntitlement::query()->where('user_id', $user->id)->sole();
    $this->actingAs($user)->post("/meal-stub/{$entitlement->id}/consume")->assertSessionHasErrors('meal');
    $this->travelBack();
})->with([
    'before period' => ['13:00', '14:00'],
    'after period' => ['10:00', '11:00'],
]);

test('food staff can search and consume an eligible persons meal', function () {
    $this->travelTo(Carbon::parse('2026-09-05 12:00:00'));
    currentMeal();
    $person = mealEligibleUser();
    $staff = mealEligibleUser(ManagementTeamType::Food);

    $this->actingAs($staff)->get('/food/distribution?search='.urlencode($person->name))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entitlements.data', 1)->where('entitlements.data.0.person', $person->name));
    $entitlement = MealEntitlement::query()->where('user_id', $person->id)->sole();
    $this->actingAs($staff)->post("/food/distribution/{$entitlement->id}/consume")
        ->assertSessionHasNoErrors();

    expect($entitlement->fresh()->consumption_method)->toBe('staff')
        ->and($entitlement->fresh()->consumed_by_user_id)->toBe($staff->id);

    $this->actingAs($staff)->get('/food/distribution')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('summary.expected', 2)
            ->where('summary.consumed', 1)
            ->where('summary.remaining', 1));

    $this->travelBack();
});

test('one person with multiple eligible roles receives one entitlement per meal', function () {
    currentMeal();
    $user = mealEligibleUser();
    $secondTeam = ManagementTeam::factory()->create([
        'meet_id' => Meet::current()->id,
        'team_type' => ManagementTeamType::Food,
    ]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $secondTeam->id,
        'user_id' => $user->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($user)->get('/meal-stub')->assertOk();

    expect(MealEntitlement::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('admin override requires a reason and records override method', function () {
    MealSchedule::factory()->create([
        'meet_id' => Meet::current()->id, 'date' => now()->toDateString(),
        'starts_at' => now()->addHour()->format('H:i'), 'ends_at' => now()->addHours(2)->format('H:i'),
        'enforce_serving_time' => true,
    ]);
    $person = mealEligibleUser();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get('/food/distribution');
    $entitlement = MealEntitlement::query()->where('user_id', $person->id)->sole();

    $this->actingAs($admin)->post("/food/distribution/{$entitlement->id}/consume", ['override' => true])->assertSessionHasErrors('reason');
    $this->actingAs($admin)->post("/food/distribution/{$entitlement->id}/consume", ['override' => true, 'reason' => 'Delayed delegation arrival'])->assertSessionHasNoErrors();
    expect($entitlement->fresh()->consumption_method)->toBe('admin_override');
});
