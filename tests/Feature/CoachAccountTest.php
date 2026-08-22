<?php

use App\Enums\DelegationStatus;
use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Personnel;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;

/**
 * WP-REALIGN-05 — a Coach is a first-class login (`UserRole::Coach`)
 * scoped through their own `Personnel.user_id` link
 * (`Delegation::hasCoach()`), not the `delegation_user` pivot a
 * Delegation Officer uses. Confirms parity with a Delegation Officer for
 * roster/registration actions and confirms the deliberate gaps (a coach
 * never decides eligibility, confirms entries, or manages delegation
 * administration — those stay manager-only).
 */
function coachFor(Delegation $delegation): User
{
    $coach = User::factory()->coach()->create();

    Personnel::factory()->coach()->create([
        'delegation_id' => $delegation->id,
        'user_id' => $coach->id,
    ]);

    $sport = Sport::factory()->create();
    $event = Event::factory()->create([
        'sport_id' => $sport->id,
        'gender' => 'boys',
        'age_division' => 'secondary',
    ]);
    $delegation->meet->events()->attach($event);
    CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => MeetSport::factory()->create(['meet_id' => $delegation->meet_id, 'sport_id' => $sport->id])->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'approved',
    ]);

    return $coach;
}

test('a coach can view and register athletes for their own delegation', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);

    $this->actingAs($coach)->get('/athletes')->assertOk();

    $this->actingAs($coach)
        ->post('/athletes', [
            'delegation_id' => $delegation->id,
            'school_id' => schoolForDelegation($delegation)->id,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'sex' => 'male',
            'birthdate' => now()->subYears(15)->toDateString(),
            'lrn' => '123456789012',
            'grade_level' => 9,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('athletes', ['delegation_id' => $delegation->id, 'first_name' => 'Juan']);
});

test('a coach registers an athlete only in an assigned event with photos and accreditation documents', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $assignment = $coach->coachAssignmentRequests()->with('event')->where('delegation_id', $delegation->id)->firstOrFail();
    $assignment->event->forceFill(['gender' => 'boys', 'age_division' => 'secondary'])->save();

    $this->actingAs($coach)->post('/athletes', [
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'event_id' => $assignment->event_id,
        'first_name' => 'Pedro',
        'last_name' => 'Santos',
        'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(),
        'lrn' => '321654987012',
        'grade_level' => 9,
        'photo' => UploadedFile::fake()->image('profile.jpg'),
        'sports_photo' => UploadedFile::fake()->image('sports.jpg'),
        'school_id_document' => UploadedFile::fake()->create('school-id.pdf', 100, 'application/pdf'),
        'birth_certificate' => UploadedFile::fake()->create('birth-cert.pdf', 100, 'application/pdf'),
        'report_card' => UploadedFile::fake()->create('report-card.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $athlete = Athlete::query()->where('lrn', '321654987012')->firstOrFail();
    expect($athlete->photo_upload_id)->not->toBeNull()
        ->and($athlete->sports_photo_upload_id)->not->toBeNull()
        ->and($athlete->entries()->where('event_id', $assignment->event_id)->exists())->toBeTrue()
        ->and($athlete->eligibilityDocuments()->count())->toBe(3)
        ->and($athlete->eligibilityReview?->status)->toBe(EligibilityStatus::Pending);

    $otherEvent = Event::factory()->create(['gender' => 'boys', 'age_division' => 'secondary']);
    $delegation->meet->events()->attach($otherEvent);
    $this->actingAs($coach)->post('/athletes', [
        'delegation_id' => $delegation->id, 'school_id' => schoolForDelegation($delegation)->id,
        'event_id' => $otherEvent->id, 'first_name' => 'Wrong', 'last_name' => 'Sport', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => '321654987013', 'grade_level' => 9,
    ])->assertSessionHasErrors('event_id');

    $this->actingAs(User::factory()->admin()->create())
        ->put("/athletes/{$athlete->id}", [
            'first_name' => 'Not', 'last_name' => 'Allowed', 'sex' => 'male',
            'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => $athlete->lrn, 'grade_level' => 9,
        ])->assertForbidden();

    $this->actingAs($coach)->put("/athletes/{$athlete->id}", [
        'first_name' => 'Pedro Updated', 'last_name' => 'Santos', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => $athlete->lrn, 'grade_level' => 9,
    ])->assertSessionHasNoErrors();
    expect($athlete->fresh()->first_name)->toBe('Pedro Updated');

    $photoUploadId = $athlete->photo_upload_id;
    $this->actingAs($coach)->delete("/athletes/{$athlete->id}")->assertRedirect('/athletes');
    $this->assertSoftDeleted('athletes', ['id' => $athlete->id]);
    $this->assertDatabaseHas('file_uploads', ['id' => $photoUploadId]);
});

test('a coach cannot view or register athletes for another delegation', function () {
    $ownDelegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $otherDelegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($ownDelegation);

    $foreignAthlete = Athlete::factory()->create(['delegation_id' => $otherDelegation->id]);

    $this->actingAs($coach)->get("/athletes/{$foreignAthlete->id}")->assertForbidden();

    $this->actingAs($coach)
        ->post('/athletes', [
            'delegation_id' => $otherDelegation->id,
            'school_id' => schoolForDelegation($otherDelegation)->id,
            'first_name' => 'X',
            'last_name' => 'Y',
            'sex' => 'male',
            'birthdate' => now()->subYears(15)->toDateString(),
            'lrn' => '999999999999',
            'grade_level' => 9,
        ])
        ->assertForbidden();
});

test('a coach can upload an eligibility document and it goes to pending', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs($coach)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->create('birth-cert.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasNoErrors();

    expect(EligibilityReview::query()->where('athlete_id', $athlete->id)->firstOrFail()->status)
        ->toBe(EligibilityStatus::Pending);
});

test('a coach can submit and withdraw an entry but cannot confirm it', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = coachFor($delegation);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'sex' => 'male',
        'grade_level' => 9,
    ]);
    $event = Event::factory()->create(['gender' => 'boys', 'age_division' => 'secondary']);
    $meet->events()->attach($event);
    CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $event->sport_id])->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'approved',
    ]);

    $this->actingAs($coach)
        ->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertSessionHasNoErrors();

    $entry = Entry::query()->where('athlete_id', $athlete->id)->firstOrFail();
    expect($entry->status)->toBe(EntryStatus::Submitted);

    $this->actingAs($coach)->patch("/entries/{$entry->id}/confirm")->assertForbidden();

    $this->actingAs($coach)->patch("/entries/{$entry->id}/withdraw")->assertSessionHasNoErrors();
    expect($entry->fresh()->status)->toBe(EntryStatus::Withdrawn);
});

test('a coach cannot decide an eligibility review', function () {
    $delegation = Delegation::factory()->create();
    $coach = coachFor($delegation);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $review = EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $delegation->meet_id]);

    $this->actingAs($coach)->patch("/eligibility/reviews/{$review->id}/approve")->assertForbidden();
    $this->actingAs($coach)->patch("/eligibility/reviews/{$review->id}/return")->assertForbidden();
});

test('a coach cannot manage delegation administration', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Submitted]);
    $coach = coachFor($delegation);

    $this->actingAs($coach)->patch("/delegations/{$delegation->id}/approve")->assertForbidden();
    $this->actingAs($coach)->patch("/delegations/{$delegation->id}/return")->assertForbidden();
    $this->actingAs($coach)->put("/delegations/{$delegation->id}/officers", ['user_ids' => []])->assertForbidden();
});

test('a coach cannot file a protest', function () {
    $delegation = Delegation::factory()->create();
    $coach = coachFor($delegation);
    $result = EventResult::factory()->create(['meet_id' => $delegation->meet_id]);

    $this->actingAs($coach)
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'event_result_id' => $result->id,
            'grounds' => 'Coaches should not be able to file this.',
        ])
        ->assertForbidden();
});

test('the same coach login can be linked to personnel rows in two different delegations over time', function () {
    $firstDelegation = Delegation::factory()->create();
    $secondDelegation = Delegation::factory()->create();
    $coach = User::factory()->coach()->create();

    Personnel::factory()->coach()->create(['delegation_id' => $firstDelegation->id, 'user_id' => $coach->id]);
    Personnel::factory()->coach()->create(['delegation_id' => $secondDelegation->id, 'user_id' => $coach->id]);

    expect($firstDelegation->hasCoach($coach))->toBeTrue()
        ->and($secondDelegation->hasCoach($coach))->toBeTrue();
});

test('the same coach login cannot be linked to two personnel rows in the same delegation', function () {
    $delegation = Delegation::factory()->create();
    $coach = User::factory()->coach()->create();

    Personnel::factory()->coach()->create(['delegation_id' => $delegation->id, 'user_id' => $coach->id]);

    expect(fn () => Personnel::factory()->create(['delegation_id' => $delegation->id, 'user_id' => $coach->id]))
        ->toThrow(QueryException::class);
});

test('linking a personnel row to a user is not mass-assignable through PersonnelController::update', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);
    $personnel = Personnel::factory()->create(['delegation_id' => $delegation->id]);
    $someUser = User::factory()->create();

    $this->actingAs($officer)
        ->put("/personnel/{$personnel->id}", [
            'delegation_id' => $delegation->id,
            'school_id' => $personnel->school_id,
            'first_name' => $personnel->first_name,
            'last_name' => $personnel->last_name,
            'role' => $personnel->role->value,
            'user_id' => $someUser->id,
        ])
        ->assertSessionHasNoErrors();

    expect($personnel->fresh()->user_id)->toBeNull();
});
