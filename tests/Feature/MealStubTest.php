<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MealType;
use App\Models\AuditLog;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\MealEntitlement;
use App\Models\MealSchedule;
use App\Models\Meet;
use App\Models\User;
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

test('user consumes only their own current meal once with self method', function () {
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
});

test('serving time is enforced for self consumption', function (string $starts, string $ends) {
    MealSchedule::factory()->create([
        'meet_id' => Meet::current()->id, 'date' => now()->toDateString(),
        'starts_at' => $starts, 'ends_at' => $ends, 'enforce_serving_time' => true,
    ]);
    $user = mealEligibleUser();
    $this->actingAs($user)->get('/meal-stub');
    $entitlement = MealEntitlement::query()->where('user_id', $user->id)->sole();
    $this->actingAs($user)->post("/meal-stub/{$entitlement->id}/consume")->assertSessionHasErrors('meal');
})->with([
    'before period' => fn () => [now()->addHour()->format('H:i'), now()->addHours(2)->format('H:i')],
    'after period' => fn () => [now()->subHours(2)->format('H:i'), now()->subHour()->format('H:i')],
]);

test('food staff can search and consume an eligible persons meal', function () {
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
