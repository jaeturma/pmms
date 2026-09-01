<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia;

/**
 * MeetSportAssignment (WP-REALIGN-04) model-level tests below, followed
 * by MeetSportAssignmentController (WP-REALIGN-07) route-level tests.
 * The controller/UI still does not feed
 * ScoringSessionController/ResultController's authorization — that
 * cutover is a separate, later step gated on a backfill-strategy
 * decision (see the model's own docblock); sport_user is untouched.
 */
test('a sport can have multiple tournament managers for the same meet sport', function () {
    $meetSport = MeetSport::factory()->create();

    $lead = MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentManager,
        'is_lead' => true,
    ]);
    $assistant = MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::AssistantTournamentManager,
    ]);

    expect($meetSport->assignments)->toHaveCount(2)
        ->and($lead->is_lead)->toBeTrue()
        ->and($lead->role)->toBe(MeetSportAssignmentRole::TournamentManager)
        ->and($assistant->role)->toBe(MeetSportAssignmentRole::AssistantTournamentManager)
        ->and($lead->status)->toBe(MeetSportAssignmentStatus::Pending);
});

test('a sport can have multiple technical officials, multiple secretaries, and multiple ICT personnel', function () {
    $meetSport = MeetSport::factory()->create();

    MeetSportAssignment::factory()->count(3)->sequence(
        ['role' => MeetSportAssignmentRole::TechnicalOfficial],
        ['role' => MeetSportAssignmentRole::TournamentSecretary],
        ['role' => MeetSportAssignmentRole::TournamentICT],
    )->create(['meet_sport_id' => $meetSport->id]);

    $roles = $meetSport->assignments()->pluck('role');

    expect($roles)->toHaveCount(3)
        ->and($roles->contains(MeetSportAssignmentRole::TechnicalOfficial))->toBeTrue()
        ->and($roles->contains(MeetSportAssignmentRole::TournamentSecretary))->toBeTrue()
        ->and($roles->contains(MeetSportAssignmentRole::TournamentICT))->toBeTrue();
});

test('a category tournament manager can be scoped to one specific sport category', function () {
    $meetSport = MeetSport::factory()->create();
    $category = SportCategory::factory()->create([
        'sport_id' => $meetSport->sport_id,
        'meet_sport_id' => $meetSport->id,
    ]);

    $assignment = MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'sport_category_id' => $category->id,
        'role' => MeetSportAssignmentRole::CategoryTournamentManager,
    ]);

    expect($assignment->sportCategory->id)->toBe($category->id)
        ->and($category->assignments()->first()->id)->toBe($assignment->id);
});

test('the same person cannot hold the same role twice for the same meet sport', function () {
    $meetSport = MeetSport::factory()->create();
    $user = User::factory()->create();

    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial,
    ]);

    expect(fn () => MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial,
    ]))->toThrow(QueryException::class);
});

test('the same person can hold two different roles for the same meet sport', function () {
    $meetSport = MeetSport::factory()->create();
    $user = User::factory()->create();

    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => MeetSportAssignmentRole::TournamentManager,
    ]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
    ]);

    expect($user->meetSportAssignments()->count())->toBe(2);
});

test('assignments are removed when the meet sport is removed, but survive their category being removed', function () {
    $meetSport = MeetSport::factory()->create();
    $category = SportCategory::factory()->create(['sport_id' => $meetSport->sport_id, 'meet_sport_id' => $meetSport->id]);
    $assignment = MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'sport_category_id' => $category->id,
    ]);

    $category->delete();
    expect($assignment->fresh()->sport_category_id)->toBeNull();

    $meetSport->delete();
    expect(MeetSportAssignment::query()->whereKey($assignment->id)->exists())->toBeFalse();
});

test('an assignment can move from pending through active to ended', function () {
    $assignment = MeetSportAssignment::factory()->create(['status' => MeetSportAssignmentStatus::Pending]);

    $assignment->forceFill(['status' => MeetSportAssignmentStatus::Active])->save();
    expect($assignment->fresh()->status)->toBe(MeetSportAssignmentStatus::Active);

    $assignment->forceFill(['status' => MeetSportAssignmentStatus::Ended, 'end_date' => now()->toDateString()])->save();
    expect($assignment->fresh())
        ->status->toBe(MeetSportAssignmentStatus::Ended)
        ->end_date->not->toBeNull();
});

// --- MeetSportAssignmentController (WP-REALIGN-07) ---

test('guests are redirected from the assignments page', function () {
    $this->get('/meet-sport-assignments')->assertRedirect('/login');
});

test('the assignments page is viewable by any authenticated role, including viewers', function () {
    $assignment = MeetSportAssignment::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/meet-sport-assignments')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('meet-sport-assignments/index')
            ->has('assignments', 13)
            ->where('canManage', false));

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/meet-sport-assignments')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', false));
});

test('the assignment sport list includes every catalog sport and reflects renamed sports', function () {
    $sport = Sport::factory()->create(['name' => 'Old Sport Name']);
    $sport->update(['name' => 'Renamed Sport']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/meet-sport-assignments')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sportOptions', fn ($options) => collect($options)->contains(
                fn (array $option): bool => $option['id'] === $sport->id
                    && $option['label'] === 'Renamed Sport',
            )));
});

test('tournament assignments can be searched by person, sport, role, and status', function () {
    $target = MeetSportAssignment::factory()->create([
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);
    $target->user()->associate(User::factory()->create(['name' => 'Searchable ICT Officer']));
    $target->save();
    MeetSportAssignment::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/meet-sport-assignments?search=Searchable%20ICT')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.search', 'Searchable ICT')
            ->has('assignments.data', 1)
            ->where('assignments.data.0.id', $target->id));

    $this->actingAs(User::factory()->create())
        ->get('/meet-sport-assignments?search=Tournament%20ICT')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('assignments.data', 1)
            ->where('assignments.data.0.id', $target->id));
});

test('organizers can create an assignment', function () {
    $meetSport = MeetSport::factory()->create();
    $official = User::factory()->technicalOfficial()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/meet-sport-assignments', [
            'meet_sport_id' => $meetSport->id,
            'user_id' => $official->id,
            'role' => MeetSportAssignmentRole::TechnicalOfficial->value,
            'is_lead' => true,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('meet_sport_assignments', [
        'meet_sport_id' => $meetSport->id,
        'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial->value,
        'is_lead' => true,
        'status' => MeetSportAssignmentStatus::Pending->value,
    ]);

    expect(AuditLog::query()->where('action', 'meet_sport_assignment.created')->exists())->toBeTrue();
});

test('tournament ICT can manage manager secretary ICT and technical officials only for assigned sports', function () {
    $assignedMeetSport = MeetSport::factory()->create();
    $otherMeetSport = MeetSport::factory()->create(['meet_id' => $assignedMeetSport->meet_id]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $assignedMeetSport->id,
        'user_id' => $ict->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($ict)->get('/meet-sport-assignments')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('canManage', true)
        ->has('roleOptions', 4)
        ->where('roleOptions', fn ($roles) => collect($roles)->pluck('value')->sort()->values()->all() === collect([
            MeetSportAssignmentRole::TournamentManager->value,
            MeetSportAssignmentRole::TournamentSecretary->value,
            MeetSportAssignmentRole::TournamentICT->value,
            MeetSportAssignmentRole::TechnicalOfficial->value,
        ])->sort()->values()->all())
        ->where('sportOptions', fn ($sports) => collect($sports)->pluck('id')->all() === [$assignedMeetSport->sport_id]));

    foreach ([
        MeetSportAssignmentRole::TournamentManager,
        MeetSportAssignmentRole::TournamentSecretary,
        MeetSportAssignmentRole::TournamentICT,
        MeetSportAssignmentRole::TechnicalOfficial,
    ] as $role) {
        $this->actingAs($ict)->post('/meet-sport-assignments', [
            'meet_sport_id' => $assignedMeetSport->id,
            'user_id' => User::factory()->create()->id,
            'role' => $role->value,
        ])->assertSessionHasNoErrors();
    }

    $outside = MeetSportAssignment::factory()->create(['meet_sport_id' => $otherMeetSport->id]);
    $this->actingAs($ict)->post('/meet-sport-assignments', [
        'meet_sport_id' => $otherMeetSport->id,
        'user_id' => User::factory()->create()->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial->value,
    ])->assertForbidden();
    $this->actingAs($ict)->post('/meet-sport-assignments', [
        'meet_sport_id' => $assignedMeetSport->id,
        'user_id' => User::factory()->create()->id,
        'role' => MeetSportAssignmentRole::AssistantTournamentManager->value,
    ])->assertForbidden();
    $this->actingAs($ict)->delete("/meet-sport-assignments/{$outside->id}")->assertForbidden();

    $managed = MeetSportAssignment::query()
        ->where('meet_sport_id', $assignedMeetSport->id)
        ->where('role', MeetSportAssignmentRole::TechnicalOfficial->value)
        ->latest('id')->firstOrFail();
    $this->actingAs($ict)->patch("/meet-sport-assignments/{$managed->id}/status", [
        'status' => MeetSportAssignmentStatus::Active->value,
    ])->assertSessionHasNoErrors();
    $this->actingAs($ict)->delete("/meet-sport-assignments/{$managed->id}")->assertSessionHasNoErrors();
});

test('assigning a catalog sport adds it to the current tournament', function () {
    $meet = Meet::factory()->create();
    $sport = Sport::factory()->create();
    $official = User::factory()->technicalOfficial()->create();

    expect(MeetSport::query()
        ->where('meet_id', $meet->id)
        ->where('sport_id', $sport->id)
        ->exists())->toBeFalse();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/meet-sport-assignments', [
            'sport_id' => $sport->id,
            'user_id' => $official->id,
            'role' => MeetSportAssignmentRole::TournamentICT->value,
        ])
        ->assertSessionHasNoErrors();

    $meetSport = MeetSport::query()
        ->where('meet_id', $meet->id)
        ->where('sport_id', $sport->id)
        ->sole();

    $this->assertDatabaseHas('meet_sport_assignments', [
        'meet_sport_id' => $meetSport->id,
        'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TournamentICT->value,
    ]);
});

test('non-managers cannot create an assignment', function (User $user) {
    $meetSport = MeetSport::factory()->create();

    $this->actingAs($user)
        ->post('/meet-sport-assignments', [
            'meet_sport_id' => $meetSport->id,
            'user_id' => User::factory()->technicalOfficial()->create()->id,
            'role' => MeetSportAssignmentRole::TechnicalOfficial->value,
        ])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
    'coach' => fn () => User::factory()->coach()->create(),
]);

test('a newly created user account can be assigned regardless of its base role', function () {
    $meetSport = MeetSport::factory()->create();
    $officer = User::factory()->delegationOfficer()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/meet-sport-assignments', [
            'meet_sport_id' => $meetSport->id,
            'user_id' => $officer->id,
            'role' => MeetSportAssignmentRole::TournamentSecretary->value,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('meet_sport_assignments', [
        'meet_sport_id' => $meetSport->id,
        'user_id' => $officer->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary->value,
    ]);
});

test('disabled accounts are not available or assignable', function () {
    $disabled = User::factory()->create(['disabled_at' => now()]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/meet-sport-assignments')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('userOptions', fn ($options) => collect($options)->doesntContain('id', $disabled->id)));

    $this->actingAs($admin)->post('/meet-sport-assignments', [
        'meet_sport_id' => MeetSport::factory()->create()->id,
        'user_id' => $disabled->id,
        'role' => MeetSportAssignmentRole::TournamentICT->value,
    ])->assertSessionHasErrors('user_id');
});

test('the same person cannot be assigned the same role twice for the same meet sport, with a field error', function () {
    $meetSport = MeetSport::factory()->create();
    $official = User::factory()->technicalOfficial()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/meet-sport-assignments', [
            'meet_sport_id' => $meetSport->id,
            'user_id' => $official->id,
            'role' => MeetSportAssignmentRole::TechnicalOfficial->value,
        ])
        ->assertSessionHasErrors('user_id');
});

test('organizers can update an assignment\'s status', function () {
    $assignment = MeetSportAssignment::factory()->create(['status' => MeetSportAssignmentStatus::Pending]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/meet-sport-assignments/{$assignment->id}/status", ['status' => MeetSportAssignmentStatus::Active->value])
        ->assertSessionHasNoErrors();

    expect($assignment->fresh()->status)->toBe(MeetSportAssignmentStatus::Active)
        ->and(AuditLog::query()->where('action', 'meet_sport_assignment.status_updated')->exists())->toBeTrue();
});

test('non-managers cannot update an assignment\'s status', function (User $user) {
    $assignment = MeetSportAssignment::factory()->create();

    $this->actingAs($user)
        ->patch("/meet-sport-assignments/{$assignment->id}/status", ['status' => MeetSportAssignmentStatus::Active->value])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
]);

test('organizers can remove an assignment', function () {
    $assignment = MeetSportAssignment::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/meet-sport-assignments/{$assignment->id}")
        ->assertSessionHasNoErrors();

    expect(MeetSportAssignment::query()->whereKey($assignment->id)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'meet_sport_assignment.deleted')->exists())->toBeTrue();
});

test('non-managers cannot remove an assignment', function (User $user) {
    $assignment = MeetSportAssignment::factory()->create();

    $this->actingAs($user)
        ->delete("/meet-sport-assignments/{$assignment->id}")
        ->assertForbidden();

    expect(MeetSportAssignment::query()->whereKey($assignment->id)->exists())->toBeTrue();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('syncing a meet\'s events keeps meet_sports current for newly attached sports', function () {
    $meet = Meet::factory()->create();
    $event = Event::factory()->create();

    expect(MeetSport::query()->where('meet_id', $meet->id)->where('sport_id', $event->sport_id)->exists())->toBeFalse();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/meets/{$meet->id}/events", ['event_ids' => [$event->id]])
        ->assertSessionHasNoErrors();

    expect(MeetSport::query()->where('meet_id', $meet->id)->where('sport_id', $event->sport_id)->exists())->toBeTrue();
});

test('syncing a meet\'s events does not duplicate an already-existing meet_sports row', function () {
    $meet = Meet::factory()->create();
    $event = Event::factory()->create();
    MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $event->sport_id]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/meets/{$meet->id}/events", ['event_ids' => [$event->id]])
        ->assertSessionHasNoErrors();

    expect(MeetSport::query()->where('meet_id', $meet->id)->where('sport_id', $event->sport_id)->count())->toBe(1);
});
