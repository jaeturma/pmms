<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * MeetSportAssignment (WP-REALIGN-04/07) is model/schema only in this
 * phase — no controller, route, or policy exists yet, and it does not
 * yet feed ScoringSessionController/ResultController's authorization
 * (that cutover is a separate, later step gated on a backfill-strategy
 * decision — see the model's own docblock). Same scoping discipline as
 * MeetSportTest/SportCategoryTest.
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
