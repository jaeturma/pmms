<?php

use App\Enums\DelegationStatus;
use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Models\Accreditation;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Personnel;
use App\Models\School;
use App\Models\SchoolDistrict;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\TeamEntry;
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

function requiredCoachAthleteFields(): array
{
    return ['middle_name' => 'N/A', 'name_extension' => 'None'];
}

test('a coach can view and register athletes for their own delegation', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);

    $this->actingAs($coach)->get('/athletes')->assertOk();

    $this->actingAs($coach)
        ->post('/athletes', [
            ...requiredCoachAthleteFields(),
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
    $athlete = Athlete::query()->where('lrn', '123456789012')->sole();
    expect($athlete->eligibilityReview?->status)->toBe(EligibilityStatus::Pending)
        ->and($athlete->eligibilityDocuments()->count())->toBe(0);
});

test('an administrator can suspend athlete registration by coaches', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    Setting::current()->forceFill(['coach_athlete_registration_enabled' => false])->save();

    $this->actingAs($coach)
        ->post('/athletes', [
            ...requiredCoachAthleteFields(),
            'delegation_id' => $delegation->id,
            'school_id' => schoolForDelegation($delegation)->id,
            'first_name' => 'Blocked',
            'last_name' => 'Athlete',
            'sex' => 'male',
            'birthdate' => now()->subYears(15)->toDateString(),
            'lrn' => '998877665544',
            'grade_level' => 9,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('athletes', ['lrn' => '998877665544']);
});

test('a coach can edit an assigned athlete until accreditation is approved', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'registered_by' => $coach->id,
    ]);

    $this->actingAs($coach)
        ->put("/athletes/{$athlete->id}", [
            'first_name' => 'Before',
            'last_name' => 'Approval',
            'sex' => $athlete->sex->value,
            'birthdate' => $athlete->birthdate->toDateString(),
            'lrn' => $athlete->lrn,
            'grade_level' => $athlete->grade_level,
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    Accreditation::factory()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
    ]);

    $this->actingAs($coach)
        ->put("/athletes/{$athlete->id}", [
            'first_name' => 'After',
            'last_name' => 'Approval',
            'sex' => $athlete->sex->value,
            'birthdate' => $athlete->birthdate->toDateString(),
            'lrn' => $athlete->lrn,
            'grade_level' => $athlete->grade_level,
        ])
        ->assertForbidden();

    expect($athlete->fresh()->first_name)->toBe('Before');
});

test('a coach registers an athlete only in an assigned event with photos and accreditation documents', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $assignment = $coach->coachAssignmentRequests()->with('event')->where('delegation_id', $delegation->id)->firstOrFail();
    $assignment->event->forceFill(['gender' => 'boys', 'age_division' => 'secondary'])->save();

    $this->actingAs($coach)->post('/athletes', [
        ...requiredCoachAthleteFields(),
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
        'athlete_history' => UploadedFile::fake()->image('athlete-history.jpg'),
        'form_10' => UploadedFile::fake()->image('form-10.png'),
        'birth_certificate' => UploadedFile::fake()->image('birth-cert.jpg'),
        'parental_consent' => UploadedFile::fake()->image('parents-consent.png'),
        'medical_certificate' => UploadedFile::fake()->image('medical-certificate.jpg'),
    ])->assertSessionHasNoErrors();

    $athlete = Athlete::query()->where('lrn', '321654987012')->firstOrFail();
    expect($athlete->photo_upload_id)->not->toBeNull()
        ->and($athlete->sports_photo_upload_id)->not->toBeNull()
        ->and($athlete->entries()->where('event_id', $assignment->event_id)->exists())->toBeTrue()
        ->and($athlete->eligibilityDocuments()->count())->toBe(5)
        ->and($athlete->eligibilityReview?->status)->toBe(EligibilityStatus::Pending);

    $otherEvent = Event::factory()->create(['gender' => 'boys', 'age_division' => 'secondary']);
    $delegation->meet->events()->attach($otherEvent);
    $this->actingAs($coach)->post('/athletes', [
        ...requiredCoachAthleteFields(),
        'delegation_id' => $delegation->id, 'school_id' => schoolForDelegation($delegation)->id,
        'event_id' => $otherEvent->id, 'first_name' => 'Wrong', 'last_name' => 'Sport', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => '321654987013', 'grade_level' => 9,
    ])->assertSessionHasErrors('event_id');

    $this->actingAs(User::factory()->admin()->create())
        ->put("/athletes/{$athlete->id}", [
            'first_name' => 'Admin Updated', 'last_name' => 'Santos', 'sex' => 'male',
            'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => $athlete->lrn, 'grade_level' => 9,
        ])->assertSessionHasNoErrors();

    expect($athlete->fresh()->first_name)->toBe('Admin Updated');

    $this->actingAs($coach)->put("/athletes/{$athlete->id}", [
        'first_name' => 'Pedro Updated', 'last_name' => 'Santos', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => $athlete->lrn, 'grade_level' => 9,
    ])->assertSessionHasNoErrors();
    expect($athlete->fresh()->first_name)->toBe('Pedro Updated');

    $photoUploadId = $athlete->photo_upload_id;
    $this->actingAs($coach)->delete("/athletes/{$athlete->id}")->assertForbidden();
    $this->assertDatabaseHas('athletes', ['id' => $athlete->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('file_uploads', ['id' => $photoUploadId]);
});

test('a coach with multiple approved events must select one of their sports for the athlete', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $firstAssignment = $coach->coachAssignmentRequests()->where('delegation_id', $delegation->id)->firstOrFail();
    $secondEvent = Event::factory()->create([
        'sport_id' => $firstAssignment->meetSport->sport_id,
        'gender' => 'boys',
        'age_division' => 'secondary',
        'is_team_event' => false,
    ]);
    $delegation->meet->events()->attach($secondEvent);
    CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $firstAssignment->meet_sport_id,
        'event_id' => $secondEvent->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'approved',
    ]);

    $payload = [
        ...requiredCoachAthleteFields(),
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'first_name' => 'Multi',
        'last_name' => 'Event',
        'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(),
        'lrn' => '321654987099',
        'grade_level' => 9,
    ];

    $this->actingAs($coach)->post('/athletes', $payload)
        ->assertSessionHasErrors('event_id');

    $this->assertDatabaseMissing('athletes', ['lrn' => '321654987099']);

    $this->actingAs($coach)->post('/athletes', [
        ...$payload,
        'event_id' => $secondEvent->id,
    ])->assertSessionHasNoErrors();

    $athlete = Athlete::query()->where('lrn', '321654987099')->sole();
    expect($athlete->entries()->where('event_id', $secondEvent->id)->exists())->toBeTrue();
});

test('coach athlete registration synchronizes the submitted school district and municipality', function () {
    $municipality = District::factory()->create();
    $delegation = Delegation::factory()->create([
        'district_id' => $municipality->id,
        'school_id' => null,
        'status' => DelegationStatus::Draft,
    ]);
    $coach = coachFor($delegation);
    $schoolDistrict = SchoolDistrict::factory()->create(['district_id' => $municipality->id]);
    $school = School::factory()->create();

    $this->actingAs($coach)->post('/athletes', [
        ...requiredCoachAthleteFields(),
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'district_id' => $municipality->id,
        'school_district_id' => $schoolDistrict->id,
        'first_name' => 'District', 'last_name' => 'Athlete', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => '321654987097', 'grade_level' => 9,
    ])->assertSessionHasNoErrors();

    expect($school->fresh()->district_id)->toBe($municipality->id)
        ->and($school->fresh()->school_district_id)->toBe($schoolDistrict->id);
});

test('a sole team event assignment requires manual roster entry', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $assignment = $coach->coachAssignmentRequests()->with('event')->firstOrFail();
    $assignment->event->forceFill(['is_team_event' => true])->save();

    $this->actingAs($coach)->post('/athletes', [
        ...requiredCoachAthleteFields(),
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'first_name' => 'Team',
        'last_name' => 'Member',
        'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(),
        'lrn' => '321654987098',
        'grade_level' => 9,
    ])->assertSessionHasNoErrors();

    $athlete = Athlete::query()->where('lrn', '321654987098')->sole();
    expect($athlete->entries()->count())->toBe(0);
});

test('a coach can manually build a team event from their approved athletes', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $delegation->meet->forceFill(['medical_clearance_required' => false])->save();
    $coach = coachFor($delegation);
    $event = $coach->coachAssignmentRequests()->with('event')->firstOrFail()->event;
    $event->forceFill(['is_team_event' => true, 'team_size' => 2])->save();
    $athletes = Athlete::factory()->count(2)->create([
        'delegation_id' => $delegation->id,
        'registered_by' => $coach->id,
        'sex' => 'male',
        'grade_level' => 9,
    ]);
    $athletes->each(fn (Athlete $athlete) => EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]));
    $athletes->each(fn (Athlete $athlete) => Accreditation::factory()->create([
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
    ]));

    $this->actingAs($coach)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => $athletes->modelKeys(),
    ])->assertSessionHasNoErrors();

    $team = TeamEntry::query()->with('members')->sole();
    expect($team->members)->toHaveCount(2)
        ->and($athletes->every(fn (Athlete $athlete) => $athlete->entries()->where('event_id', $event->id)->exists()))->toBeTrue();
});

test('a coach cannot view or register athletes for another delegation', function () {
    $ownDelegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $otherDelegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($ownDelegation);

    $foreignAthlete = Athlete::factory()->create(['delegation_id' => $otherDelegation->id]);

    $this->actingAs($coach)->get("/athletes/{$foreignAthlete->id}")->assertForbidden();

    $this->actingAs($coach)
        ->post('/athletes', [
            ...requiredCoachAthleteFields(),
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

test('a coach sees only owned athletes within the assigned event scope', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $owned = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'registered_by' => $coach->id,
    ]);
    $unowned = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $outsideEvent = Event::factory()->create();
    $delegation->meet->events()->attach($outsideEvent);
    $outsideScope = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'registered_by' => $coach->id,
    ]);
    Entry::factory()->create([
        'athlete_id' => $outsideScope->id,
        'delegation_id' => $delegation->id,
        'event_id' => $outsideEvent->id,
    ]);

    $this->actingAs($coach)
        ->get('/athletes')
        ->assertInertia(fn ($page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.id', $owned->id));

    $this->actingAs($coach)
        ->get("/athletes/{$unowned->id}")
        ->assertForbidden();
});

test('approved onboarding events provide athlete and entry scope without assignment requests', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = User::factory()->coach()->create();
    Personnel::factory()->coach()->create([
        'delegation_id' => $delegation->id,
        'user_id' => $coach->id,
    ]);
    $event = Event::factory()->create(['gender' => 'boys', 'age_division' => 'secondary']);
    $meet->events()->attach($event);
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'district_id' => $delegation->district_id,
        'event_id' => $event->id,
        'status' => 'approved',
    ]);
    $onboarding->events()->attach($event);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'registered_by' => $coach->id,
        'sex' => 'male',
        'grade_level' => 9,
    ]);
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $meet->id,
    ]);

    expect($coach->coachAssignmentRequests()->count())->toBe(0);

    $this->actingAs($coach)
        ->get('/athletes')
        ->assertInertia(fn ($page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.id', $athlete->id));

    $this->actingAs($coach)
        ->get('/entries')
        ->assertInertia(fn ($page) => $page
            ->has('athleteOptions', 1)
            ->where('athleteOptions.0.id', $athlete->id)
            ->has('eventOptionsByMeet', 1)
            ->where('eventOptionsByMeet.0.id', $event->id));
});

test('a coach can upload an eligibility document and it goes to pending', function () {
    $delegation = Delegation::factory()->create(['status' => DelegationStatus::Draft]);
    $coach = coachFor($delegation);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs($coach)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->image('birth-cert.jpg'),
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
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $meet->id,
    ]);
    Accreditation::factory()->create([
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
    ]);
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

test('a coach sees approved own athletes and assigned events but not submitted competitors', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $ownDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = coachFor($ownDelegation);
    $assignment = $coach->coachAssignmentRequests()->where('status', 'approved')->firstOrFail();
    $ownAthlete = Athlete::factory()->create([
        'delegation_id' => $ownDelegation->id,
        'registered_by' => $coach->id,
    ]);
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $ownAthlete->id,
        'meet_id' => $meet->id,
    ]);
    $pendingAthlete = Athlete::factory()->create(['delegation_id' => $ownDelegation->id]);
    EligibilityReview::factory()->create([
        'athlete_id' => $pendingAthlete->id,
        'meet_id' => $meet->id,
    ]);

    $otherDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $otherAthlete = Athlete::factory()->create(['delegation_id' => $otherDelegation->id]);
    Entry::factory()->create([
        'delegation_id' => $otherDelegation->id,
        'athlete_id' => $otherAthlete->id,
        'event_id' => $assignment->event_id,
        'status' => EntryStatus::Submitted,
    ]);

    $this->actingAs($coach)
        ->get('/entries')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('entries.data', 0)
            ->has('athleteOptions', 1)
            ->where('athleteOptions.0.id', $ownAthlete->id)
            ->has('eventOptionsByMeet', 1)
            ->where('eventOptionsByMeet.0.id', $assignment->event_id)
            ->has('eventFilterOptions', 1)
            ->where('eventFilterOptions.0.id', $assignment->event_id));
});

test('coach dashboard hides another delegation submitted entries', function () {
    $meet = Meet::current();
    $ownDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = coachFor($ownDelegation);
    $assignment = $coach->coachAssignmentRequests()->where('status', 'approved')->firstOrFail();
    $ownAthlete = Athlete::factory()->create([
        'delegation_id' => $ownDelegation->id,
        'registered_by' => $coach->id,
    ]);
    $review = EligibilityReview::factory()->approved()->create([
        'athlete_id' => $ownAthlete->id,
        'meet_id' => $meet->id,
    ]);
    $otherDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $otherAthlete = Athlete::factory()->create(['delegation_id' => $otherDelegation->id]);
    Entry::factory()->create([
        'delegation_id' => $otherDelegation->id,
        'athlete_id' => $otherAthlete->id,
        'event_id' => $assignment->event_id,
        'status' => EntryStatus::Submitted,
    ]);

    $this->actingAs($coach)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('coachDashboard.eligibility_reviews', 1)
            ->where('coachDashboard.eligibility_reviews.0.id', $review->id)
            ->where('coachDashboard.eligibility_reviews.0.athlete', $ownAthlete->fullName())
            ->has('coachDashboard.submitted_entries', 0));
});

test('a coach cannot submit an athlete whose eligibility is not approved', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = coachFor($delegation);
    $assignment = $coach->coachAssignmentRequests()->where('status', 'approved')->firstOrFail();
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'sex' => 'male',
        'grade_level' => 9,
    ]);

    $this->actingAs($coach)
        ->post('/entries', [
            'athlete_id' => $athlete->id,
            'event_id' => $assignment->event_id,
        ])
        ->assertSessionHasErrors('athlete_id');

    expect($athlete->entries()->count())->toBe(0);
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
