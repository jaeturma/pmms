<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MealType;
use App\Models\AuditLog;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\MealAnnouncement;
use App\Models\MealSchedule;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia;

/**
 * Food (WP-REALIGN-11) — meal schedule + internal announcements, no
 * DelegationOfficer tier (meet-wide operational info, not
 * delegation-specific). See docs/food-billeting-transport.md.
 */
function foodMember(?Meet $meet = null): User
{
    $meet ??= Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::Food]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $member->user;
}

// --- Model relationships ---

test('a meet has many meal schedules', function () {
    $meet = Meet::factory()->create();
    $schedule = MealSchedule::factory()->create(['meet_id' => $meet->id]);

    expect($meet->mealSchedules()->first()->id)->toBe($schedule->id);
});

test('a meet cannot have two schedule entries for the same meal on the same date', function () {
    $meet = Meet::factory()->create();
    MealSchedule::factory()->create(['meet_id' => $meet->id, 'meal_type' => MealType::Lunch, 'date' => '2026-09-01']);

    expect(fn () => MealSchedule::factory()->create(['meet_id' => $meet->id, 'meal_type' => MealType::Lunch, 'date' => '2026-09-01']))
        ->toThrow(QueryException::class);
});

// --- MealScheduleController ---

test('guests are redirected from the food page', function () {
    $this->get('/food')->assertRedirect('/login');
});

test('viewers without a food role cannot view the food page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/food')
        ->assertForbidden();
});

test('admins, organizers, and active food team members can view the food page', function () {
    $meet = Meet::factory()->create();
    MealSchedule::factory()->create(['meet_id' => $meet->id]);
    MealAnnouncement::factory()->create(['meet_id' => $meet->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/food')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('food/index')
            ->has('schedules', 1)
            ->has('announcements', 1));

    $this->actingAs(foodMember($meet))
        ->get('/food')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('schedules', 1));
});

test('organizers can add a meal schedule entry', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/meal-schedules', [
            'meet_id' => $meet->id,
            'meal_type' => MealType::Lunch->value,
            'date' => '2026-09-02',
            'starts_at' => '12:00',
            'ends_at' => '13:00',
        ])
        ->assertSessionHasNoErrors();

    $schedule = MealSchedule::query()->where('meet_id', $meet->id)->firstOrFail();
    expect($schedule->meal_type)->toBe(MealType::Lunch)
        ->and($schedule->date->toDateString())->toBe('2026-09-02');

    expect(AuditLog::query()->where('action', 'meal_schedule.created')->exists())->toBeTrue();
});

test('a food team member can add a schedule entry for their own meet', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(foodMember($meet))
        ->post('/meal-schedules', [
            'meet_id' => $meet->id,
            'meal_type' => MealType::Breakfast->value,
            'date' => '2026-09-02',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('meal_schedules', ['meet_id' => $meet->id, 'meal_type' => MealType::Breakfast->value]);
});

test('a food team member from a different meet cannot manage this meet\'s food data', function () {
    $meet = Meet::factory()->create();
    $otherMeetFoodMember = foodMember();

    $this->actingAs($otherMeetFoodMember)
        ->post('/meal-schedules', [
            'meet_id' => $meet->id,
            'meal_type' => MealType::Lunch->value,
            'date' => '2026-09-02',
        ])
        ->assertForbidden();
});

test('duplicate schedule entries fail with a field error', function () {
    $schedule = MealSchedule::factory()->create(['meal_type' => MealType::Dinner, 'date' => '2026-09-03']);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/meal-schedules', [
            'meet_id' => $schedule->meet_id,
            'meal_type' => MealType::Dinner->value,
            'date' => '2026-09-03',
        ])
        ->assertSessionHasErrors('meal_type');
});

test('organizers can update and remove a schedule entry', function () {
    $schedule = MealSchedule::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->put("/meal-schedules/{$schedule->id}", ['notes' => 'Served buffet-style.'])
        ->assertSessionHasNoErrors();

    expect($schedule->fresh()->notes)->toBe('Served buffet-style.');

    $this->actingAs(User::factory()->organizer()->create())
        ->delete("/meal-schedules/{$schedule->id}")
        ->assertSessionHasNoErrors();

    expect(MealSchedule::query()->whereKey($schedule->id)->exists())->toBeFalse();
});

// --- MealAnnouncementController ---

test('organizers can post an announcement, distinct from the public Announcement model', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/meal-announcements', [
            'meet_id' => $meet->id,
            'title' => 'Lunch delayed',
            'message' => 'Lunch will be served 30 minutes late today.',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('meal_announcements', ['meet_id' => $meet->id, 'title' => 'Lunch delayed']);
    expect(AuditLog::query()->where('action', 'meal_announcement.created')->exists())->toBeTrue();
});

test('non-managers cannot post an announcement', function (User $user) {
    $meet = Meet::factory()->create();

    $this->actingAs($user)
        ->post('/meal-announcements', ['meet_id' => $meet->id, 'title' => 'X', 'message' => 'Y'])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
    'coach' => fn () => User::factory()->coach()->create(),
]);
