<?php

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\RequirementStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Accreditation;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\FileUpload;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Personnel;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\Sport;
use App\Models\SportRosterMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

function athleteOfficerFor(Delegation $delegation): User
{
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    return $officer;
}

function validAthletePayload(Delegation $delegation): array
{
    return [
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'first_name' => 'Ana',
        'middle_name' => 'N/A',
        'last_name' => 'Reyes',
        'name_extension' => 'None',
        'sex' => 'female',
        'birthdate' => now()->subYears(12)->toDateString(),
        'lrn' => '123456789012',
        'grade_level' => 6,
    ];
}

test('guests are redirected from the athlete registry', function () {
    $this->get('/athletes')->assertRedirect('/login');
});

test('viewers have no access to athlete data', function () {
    $this->actingAs(User::factory()->create())
        ->get('/athletes')
        ->assertForbidden();
});

test('assigned tournament operations can manually approve an athlete with incomplete documents', function (MeetSportAssignmentRole $role) {
    $athlete = Athlete::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $athlete->delegation->meet_id]);
    SportRosterMember::query()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $athlete->delegation_id,
        'meet_sport_id' => $meetSport->id,
        'level' => $athlete->ageDivision(),
        'gender' => $athlete->sex->value === 'male' ? 'boys' : 'girls',
    ]);
    $ict = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id,
        'meet_sport_id' => $meetSport->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($ict)->get("/athletes/{$athlete->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('athlete.can_mark_eligible', true));

    $this->actingAs($ict)->patch("/athletes/{$athlete->id}/eligibility")->assertRedirect();

    $review = EligibilityReview::query()->where('athlete_id', $athlete->id)->sole();
    expect($review->status)->toBe(EligibilityStatus::Approved)
        ->and($review->reviewer_id)->toBe($ict->id)
        ->and($review->remarks)->toContain('incomplete document')
        ->and(AuditLog::query()->where('action', 'eligibility.approved')->latest('id')->firstOrFail()->context['source'])
        ->toBe('tournament_operations_manual_override');

    $this->actingAs($ict)->get("/athletes/{$athlete->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('athlete.eligibility_status', 'Eligible — Incomplete documents')
            ->where('athlete.eligibility_documents_incomplete', true)
            ->where('athlete.can_mark_eligible', false));
})->with([
    'Tournament ICT' => MeetSportAssignmentRole::TournamentICT,
    'Tournament Secretary' => MeetSportAssignmentRole::TournamentSecretary,
]);

test('unassigned ict cannot manually mark an athlete eligible', function () {
    $athlete = Athlete::factory()->create();
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);

    $this->actingAs($ict)->patch("/athletes/{$athlete->id}/eligibility")->assertForbidden();
    expect(EligibilityReview::query()->where('athlete_id', $athlete->id)->exists())->toBeFalse();
});

test('athlete and coach names display in uppercase everywhere', function () {
    $athlete = Athlete::factory()->create([
        'first_name' => 'Ana Maria',
        'middle_name' => 'Dela Cruz',
        'last_name' => 'Reyes',
        'name_extension' => 'Jr.',
    ]);
    $coach = User::factory()->coach()->create(['name' => 'Coach Maria Santos']);
    $viewer = User::factory()->create(['name' => 'Regular Viewer']);
    $personnel = Personnel::factory()->coach()->create([
        'first_name' => 'Juan',
        'last_name' => 'Coach',
    ]);

    expect($athlete->first_name)->toBe('ANA MARIA')
        ->and($athlete->middle_name)->toBe('DELA CRUZ')
        ->and($athlete->last_name)->toBe('REYES')
        ->and($athlete->fullName())->toBe('ANA MARIA DELA CRUZ REYES JR.')
        ->and($coach->name)->toBe('COACH MARIA SANTOS')
        ->and($personnel->fullName())->toBe('JUAN COACH')
        ->and($viewer->name)->toBe('Regular Viewer');
});

test('officers see only their own athletes while managers see all', function () {
    $mine = Delegation::factory()->create();
    $officer = athleteOfficerFor($mine);
    Athlete::factory()->create(['delegation_id' => $mine->id]);
    Athlete::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/athletes')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('athletes/index')
            ->has('athletes.data', 2));

    $this->actingAs($officer)
        ->get('/athletes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1));
});

test('an officer assigned to a municipal delegation sees the whole pooled roster', function () {
    // Division initiative, reviewed WP7: AthletePolicy scopes officers by
    // delegation, not by an athlete's own school_id — accepted/intended,
    // see docs/delegations.md "Officer roster scope". Proven here: one
    // officer, one municipal delegation, two different schools, both
    // athletes visible.
    $district = District::factory()->create();
    $schoolA = School::factory()->create(['district_id' => $district->id]);
    $schoolB = School::factory()->create(['district_id' => $district->id]);

    $delegation = Delegation::factory()->create([
        'school_id' => null,
        'district_id' => $district->id,
    ]);
    $officer = athleteOfficerFor($delegation);

    Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $schoolA->id]);
    Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $schoolB->id]);
    Athlete::factory()->create(); // foreign delegation, must stay invisible

    $this->actingAs($officer)
        ->get('/athletes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 2));
});

test('the registry can be searched by name and lrn', function () {
    Athlete::factory()->create(['first_name' => 'Ana', 'last_name' => 'Reyes']);
    Athlete::factory()->create(['first_name' => 'Ben', 'last_name' => 'Cruz', 'lrn' => '999888777666']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/athletes?search=Reyes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.name', 'ANA REYES'));

    $this->actingAs($admin)
        ->get('/athletes?search=999888')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.name', 'BEN CRUZ'));
});

test('a delegation officer cannot register an athlete', function () {
    $delegation = Delegation::factory()->create();
    $officer = athleteOfficerFor($delegation);

    $this->actingAs($officer)
        ->post('/athletes', validAthletePayload($delegation))
        ->assertForbidden();

    $this->assertDatabaseMissing('athletes', ['lrn' => '123456789012']);
});

test('officers cannot register athletes for closed or foreign delegations', function (callable $setup) {
    [$delegation, $officer] = $setup();

    $this->actingAs($officer)
        ->post('/athletes', validAthletePayload($delegation))
        ->assertForbidden();
})->with([
    'registration closed' => fn () => (function () {
        $delegation = Delegation::factory()->create(['meet_id' => Meet::factory()->create()]);

        return [$delegation, athleteOfficerFor($delegation)];
    })(),
    'foreign delegation' => fn () => [
        Delegation::factory()->create(),
        User::factory()->delegationOfficer()->create(),
    ],
]);

test('a delegation officer cannot add to a submitted delegation roster', function () {
    $delegation = Delegation::factory()->submitted()->create();

    $this->actingAs(athleteOfficerFor($delegation))
        ->post('/athletes', validAthletePayload($delegation))
        ->assertForbidden();

    $this->assertDatabaseMissing('athletes', ['delegation_id' => $delegation->id]);
});

test('meet organizers cannot register athletes', function () {
    $delegation = Delegation::factory()->submitted()->create(['meet_id' => Meet::factory()->create()]);

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/athletes', validAthletePayload($delegation))
        ->assertForbidden();

    $this->assertDatabaseMissing('athletes', ['delegation_id' => $delegation->id]);
});

test('active ICT team members can register athletes', function () {
    $delegation = Delegation::factory()->create();
    $ict = User::factory()->create();
    $team = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($ict)
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'grade_level' => 0,
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('athletes', [
        'lrn' => '123456789012',
        'grade_level' => 0,
    ]);
    expect(AuditLog::query()->where('action', 'athlete.created')->exists())->toBeTrue();
});

test('active ICT team members can open and update the full athlete editor', function () {
    $delegation = Delegation::factory()->create();
    $event = Event::factory()->create();
    $delegation->meet->events()->attach($event);
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $delegation->meet_id,
        'sport_id' => $event->sport_id,
    ]);
    $originalCoach = User::factory()->coach()->create();
    $replacementCoach = User::factory()->coach()->create();
    foreach ([$originalCoach, $replacementCoach] as $coach) {
        CoachAssignmentRequest::query()->create([
            'user_id' => $coach->id,
            'delegation_id' => $delegation->id,
            'school_id' => schoolForDelegation($delegation)->id,
            'meet_sport_id' => $meetSport->id,
            'event_id' => $event->id,
            'status' => 'approved',
        ]);
    }
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'registered_by' => $originalCoach->id,
    ]);
    $ict = User::factory()->create();
    $team = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id, 'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($ict)->get("/athletes/{$athlete->id}/edit")
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('athletes/edit')
        ->where('athlete.id', $athlete->id)
        ->where('canReassignCoach', true)
        ->has('coachOptions', 2));

    $this->actingAs($ict)->put("/athletes/{$athlete->id}", [
        ...validAthletePayload($delegation), 'first_name' => 'Updated',
        'lrn' => $athlete->lrn, 'meet_sport_ids' => [$meetSport->id],
        'event_ids' => [$event->id], 'registered_by' => $replacementCoach->id,
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($athlete->refresh()->first_name)->toBe('UPDATED')
        ->and($athlete->registered_by)->toBe($replacementCoach->id);
});

test('administrators can replace an athletes delegation sports and events', function () {
    $meet = Meet::factory()->create();
    $sourceDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $targetDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $oldEvent = Event::factory()->create();
    $newEvent = Event::factory()->create();
    $meet->events()->attach([$oldEvent->id, $newEvent->id]);
    $oldMeetSport = MeetSport::factory()->create([
        'meet_id' => $meet->id,
        'sport_id' => $oldEvent->sport_id,
    ]);
    $newMeetSport = MeetSport::factory()->create([
        'meet_id' => $meet->id,
        'sport_id' => $newEvent->sport_id,
    ]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $sourceDelegation->id,
        'school_id' => schoolForDelegation($sourceDelegation)->id,
    ]);
    Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $sourceDelegation->id,
        'event_id' => $oldEvent->id,
    ]);
    SportRosterMember::query()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $sourceDelegation->id,
        'meet_sport_id' => $oldMeetSport->id,
        'level' => $athlete->ageDivision(),
        'gender' => $athlete->sex->value === 'male' ? 'boys' : 'girls',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/athletes/{$athlete->id}", [
            ...validAthletePayload($targetDelegation),
            'lrn' => $athlete->lrn,
            'delegation_id' => $targetDelegation->id,
            'school_id' => schoolForDelegation($targetDelegation)->id,
            'meet_sport_ids' => [$newMeetSport->id],
            'event_ids' => [$newEvent->id],
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($athlete->fresh()->delegation_id)->toBe($targetDelegation->id)
        ->and($athlete->entries()->pluck('event_id')->all())->toBe([$newEvent->id])
        ->and($athlete->sportRosterMemberships()->pluck('meet_sport_id')->all())->toBe([$newMeetSport->id]);
    $this->assertDatabaseHas('entries', [
        'athlete_id' => $athlete->id,
        'delegation_id' => $targetDelegation->id,
        'event_id' => $newEvent->id,
    ]);
    $this->assertDatabaseHas('sport_roster_members', [
        'athlete_id' => $athlete->id,
        'delegation_id' => $targetDelegation->id,
        'meet_sport_id' => $newMeetSport->id,
    ]);
});

test('athlete registration lists every active school with its school id', function () {
    $delegation = Delegation::factory()->create();
    $school = School::factory()->create([
        'name' => 'Tuboran National High School',
        'school_id_code' => '315803',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/athletes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("schoolOptionsByDelegation.{$delegation->id}", fn ($schools) => collect($schools)->contains(fn ($option) => $option['name'] === $school->name
                && $option['school_id_code'] === '315803')));
});

test('an athlete from a school without municipality or district can be registered and listed', function () {
    $delegation = Delegation::factory()->create();
    $school = School::factory()->create([
        'district_id' => null,
        'school_district_id' => null,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'school_id' => $school->id,
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->actingAs($admin)
        ->get('/athletes')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('athletes.data.0.school', $school->name)
            ->where('athletes.data.0.district', 'Not assigned'));
});

test('athlete validation rejects bad payloads', function (array $overrides, string $errorField) {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [...validAthletePayload($delegation), ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'future birthdate' => [['birthdate' => '2030-01-01'], 'birthdate'],
    'too old' => [['birthdate' => '1980-01-01'], 'birthdate'],
    'short lrn' => [['lrn' => '12345'], 'lrn'],
    'bad sex' => [['sex' => 'other'], 'sex'],
    'bad grade' => [['grade_level' => 13], 'grade_level'],
    'negative grade' => [['grade_level' => -1], 'grade_level'],
]);

test('lrn must be unique', function () {
    Athlete::factory()->create(['lrn' => '123456789012']);
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', validAthletePayload($delegation))
        ->assertSessionHasErrors('lrn');
});

test('viewing an athlete profile is audited', function () {
    $athlete = Athlete::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes/{$athlete->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('athletes/show')
            ->where('athlete.lrn', $athlete->lrn));

    expect(AuditLog::query()->where('action', 'athlete.viewed')->exists())->toBeTrue();
});

test('athlete 380 views use its sport roster membership instead of entries', function () {
    $coach = User::factory()->coach()->create(['name' => 'Coach Maria']);
    $athlete = Athlete::factory()->create(['id' => 380, 'registered_by' => $coach->id]);
    $rosterSport = Sport::factory()->create(['name' => 'Basketball']);
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $athlete->delegation->meet_id,
        'sport_id' => $rosterSport->id,
    ]);
    SportRosterMember::query()->create([
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $athlete->delegation_id,
        'athlete_id' => $athlete->id,
        'level' => 'elementary',
        'gender' => 'girls',
    ]);
    // An entry in another sport must not override the canonical roster sport.
    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $athlete->delegation_id,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/athletes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('athletes.data.0.sports', 'Basketball')
            ->where('athletes.data.0.events', $entry->event->name)
            ->where('athletes.data.0.coach', 'COACH MARIA')
            ->where('athletes.data.0.delegation', $athlete->delegation->registrantName()));

    $this->actingAs($admin)
        ->get("/athletes/{$athlete->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('athlete.sports', 'Basketball')
            ->where('athlete.coach', 'COACH MARIA')
            ->where('athlete.delegation', $athlete->delegation->registrantName()));
});

test('athlete profile falls back to entry sport when no roster sport is assigned', function () {
    $athlete = Athlete::factory()->create();
    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $athlete->delegation_id,
    ]);
    $entry->event->sport->update(['name' => 'Badminton']);
    $entry->event->update(['name' => 'Secondary Open']);

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes/{$athlete->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('athlete.sports', 'Badminton')
            ->where('athlete.events', 'Badminton — Secondary Open'));
});

test('athlete sport display lists distinct roster sports and sport filter uses roster membership', function () {
    $athlete = Athlete::factory()->create();
    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $volleyball = Sport::factory()->create(['name' => 'Volleyball']);

    foreach ([$basketball, $volleyball] as $sport) {
        $meetSport = MeetSport::factory()->create([
            'meet_id' => $athlete->delegation->meet_id,
            'sport_id' => $sport->id,
        ]);
        SportRosterMember::query()->create([
            'meet_sport_id' => $meetSport->id,
            'delegation_id' => $athlete->delegation_id,
            'athlete_id' => $athlete->id,
            'level' => 'elementary',
            'gender' => 'girls',
        ]);
    }

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes?sport_id={$basketball->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.sports', 'Basketball, Volleyball'));
});

test('officers cannot view athletes of other delegations', function () {
    $athlete = Athlete::factory()->create();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/athletes/{$athlete->id}")
        ->assertForbidden();
});

test('an athlete can be registered with a photo', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'photo' => UploadedFile::fake()->image('athlete.jpg'),
        ])
        ->assertRedirect();

    $athlete = Athlete::query()->sole();
    $upload = FileUpload::query()->sole();

    expect($athlete->photo_upload_id)->toBe($upload->id);
    Storage::disk('local')->assertExists($upload->path);
});

test('the athlete photo is served to authorized users only', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'photo' => UploadedFile::fake()->image('athlete.jpg'),
        ]);

    $athlete = Athlete::query()->sole();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes/{$athlete->id}/photo")
        ->assertOk();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/athletes/{$athlete->id}/photo")
        ->assertForbidden();
});

test('an athlete can be registered with a sports photo, independent of the profile photo', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'photo' => UploadedFile::fake()->image('profile.jpg'),
            'sports_photo' => UploadedFile::fake()->image('action.jpg'),
        ])
        ->assertRedirect();

    $athlete = Athlete::query()->sole();

    expect($athlete->photo_upload_id)->not->toBeNull()
        ->and($athlete->sports_photo_upload_id)->not->toBeNull()
        ->and($athlete->photo_upload_id)->not->toBe($athlete->sports_photo_upload_id);

    Storage::disk('local')->assertExists($athlete->photo->path);
    Storage::disk('local')->assertExists($athlete->sportsPhoto->path);
});

test('athlete profile lists uploaded documents with review status labels', function () {
    $athlete = Athlete::factory()->create();
    $athlete->eligibilityReview()->create(['meet_id' => $athlete->delegation->meet_id]);

    foreach ([
        [EligibilityDocumentType::AthleteRecord, RequirementStatus::Submitted, 'Athlete Record', 'Pending'],
        [EligibilityDocumentType::Form10, RequirementStatus::UnderReview, 'School Form 10', 'Review'],
        [EligibilityDocumentType::BirthCertificate, RequirementStatus::Verified, 'PSA Birth Certificate', 'Approved'],
        [EligibilityDocumentType::MedicalCertificate, RequirementStatus::Rejected, 'Medical Certificate', 'Rejected'],
    ] as [$type, $status]) {
        EligibilityDocument::factory()->create([
            'athlete_id' => $athlete->id,
            'document_type' => $type,
            'status' => $status,
        ]);
    }

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes/{$athlete->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athlete.documents', 4)
            ->where('athlete.documents.0.document', 'Athlete Record')
            ->where('athlete.documents.0.status_label', 'Pending')
            ->where('athlete.documents.1.document', 'School Form 10')
            ->where('athlete.documents.1.status_label', 'Review')
            ->where('athlete.documents.2.document', 'PSA Birth Certificate')
            ->where('athlete.documents.2.status_label', 'Approved')
            ->where('athlete.documents.3.document', 'Medical Certificate')
            ->where('athlete.documents.3.status_label', 'Rejected'));
});

test('replacing an athlete document removes its previous record and stored file', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/athletes', [
        ...validAthletePayload($delegation),
        'athlete_history' => UploadedFile::fake()->image('old-history.jpg'),
    ]);

    $athlete = Athlete::query()->sole();
    $oldDocument = $athlete->eligibilityDocuments()->sole();
    $oldUpload = $oldDocument->fileUpload;

    $this->actingAs($admin)
        ->put("/athletes/{$athlete->id}", [
            ...validAthletePayload($delegation),
            'athlete_history' => UploadedFile::fake()->image('new-history.png'),
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    expect($athlete->eligibilityDocuments()->count())->toBe(1)
        ->and($athlete->eligibilityDocuments()->sole()->file_upload_id)->not->toBe($oldUpload->id);
    $this->assertDatabaseMissing('eligibility_documents', ['id' => $oldDocument->id]);
    $this->assertDatabaseMissing('file_uploads', ['id' => $oldUpload->id]);
    Storage::disk('local')->assertMissing($oldUpload->path);
});

test('athlete documents accept files up to ten megabytes and store an optimized image', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();
    $document = UploadedFile::fake()->image('phone-scan.jpg', 3200, 4200)->size(9 * 1024);

    $this->actingAs(User::factory()->admin()->create())->post('/athletes', [
        ...validAthletePayload($delegation),
        'athlete_history' => $document,
    ])->assertSessionHasNoErrors();

    $upload = EligibilityDocument::query()->sole()->fileUpload;
    $stored = Storage::disk('local')->get($upload->path);
    $dimensions = getimagesizefromstring($stored);

    expect(strlen($stored))->toBeLessThan(1024 * 1024)
        ->and(max($dimensions[0], $dimensions[1]))->toBeLessThanOrEqual(2200)
        ->and(round($dimensions[0] / $dimensions[1], 2))->toBe(round(3200 / 4200, 2));
});

test('athlete documents over ten megabytes receive a friendly validation error', function () {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post('/athletes', [
        ...validAthletePayload($delegation),
        'medical_certificate' => UploadedFile::fake()->image('medical.jpg')->size(10241),
    ])->assertSessionHasErrors([
        'medical_certificate' => 'The selected document is too large. Maximum upload size is 10 MB per file.',
    ]);
});

test('spoofed athlete document images are rejected without replacing an existing document', function () {
    Storage::fake('local');
    $athlete = Athlete::factory()->create();
    $existing = EligibilityDocument::factory()->create(['athlete_id' => $athlete->id]);

    $this->actingAs(User::factory()->admin()->create())->put("/athletes/{$athlete->id}", [
        ...validAthletePayload($athlete->delegation),
        'athlete_history' => UploadedFile::fake()->createWithContent('spoofed.jpg', 'not an image'),
    ])->assertSessionHasErrors('athlete_history');

    $this->assertDatabaseHas('eligibility_documents', ['id' => $existing->id]);
});

test('two page document uploads preserve page order and process each page independently', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post('/athletes', [
        ...validAthletePayload($delegation),
        'form_10' => UploadedFile::fake()->image('page-1.jpg', 1800, 2400),
        'form_10_page_2' => UploadedFile::fake()->image('page-2.jpg', 2000, 2600),
    ])->assertSessionHasNoErrors();

    $documents = EligibilityDocument::query()->orderBy('id')->with('fileUpload')->get();
    expect($documents)->toHaveCount(2)
        ->and($documents[0]->document_type)->toBe(EligibilityDocumentType::Form10)
        ->and($documents[1]->document_type)->toBe(EligibilityDocumentType::Form10)
        ->and($documents[0]->file_upload_id)->not->toBe($documents[1]->file_upload_id)
        ->and($documents->every(fn ($document) => $document->fileUpload->size < 1024 * 1024))->toBeTrue();
});

test('the athlete sports photo is served to authorized users only', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'sports_photo' => UploadedFile::fake()->image('action.jpg'),
        ]);

    $athlete = Athlete::query()->sole();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes/{$athlete->id}/sports-photo")
        ->assertOk();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/athletes/{$athlete->id}/sports-photo")
        ->assertForbidden();
});

test('updating and permanently deleting an athlete cleans up sports photos', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/athletes', [
        ...validAthletePayload($delegation),
        'sports_photo' => UploadedFile::fake()->image('action.jpg'),
    ]);

    $athlete = Athlete::query()->sole();
    $originalUpload = $athlete->sportsPhoto;

    $this->actingAs($admin)
        ->put("/athletes/{$athlete->id}", [
            ...validAthletePayload($delegation),
            'sports_photo' => UploadedFile::fake()->image('replacement.jpg'),
        ])
        ->assertRedirect();

    $athlete->refresh();

    expect($athlete->sports_photo_upload_id)->not->toBe($originalUpload->id);
    $this->assertDatabaseMissing('file_uploads', ['id' => $originalUpload->id]);

    $replacementUpload = $athlete->sportsPhoto;

    $this->actingAs($admin)
        ->delete("/athletes/{$athlete->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('athletes', ['id' => $athlete->id]);
    $this->assertDatabaseHas('file_uploads', ['id' => $replacementUpload->id]);
});

test('administrator deletion archives the athlete and exposes it through the deleted filter', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/athletes', [
        ...validAthletePayload($delegation),
        'photo' => UploadedFile::fake()->image('athlete.jpg'),
    ]);

    $athlete = Athlete::query()->sole();

    $this->actingAs($admin)
        ->put("/athletes/{$athlete->id}", [
            ...validAthletePayload($delegation),
            'first_name' => 'Renamed',
        ])
        ->assertRedirect();

    expect($athlete->refresh()->first_name)->toBe('RENAMED')
        ->and(AuditLog::query()->where('action', 'athlete.updated')->exists())->toBeTrue();

    $upload = FileUpload::query()->sole();

    $this->actingAs($admin)
        ->delete("/athletes/{$athlete->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('athletes', ['id' => $athlete->id]);
    $this->assertDatabaseHas('file_uploads', ['id' => $upload->id]);

    expect(AuditLog::query()->where('action', 'athlete.deleted')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->get('/athletes?deleted=1')
        ->assertInertia(fn ($page) => $page
            ->where('filters.deleted', true)
            ->where('canViewDeleted', true)
            ->has('athletes.data', 1)
            ->where('athletes.data.0.id', $athlete->id)
            ->where('athletes.data.0.deleted', true));
});

test('administrator can permanently delete an archived athlete without dependencies', function () {
    $athlete = Athlete::factory()->create();
    $athlete->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/athletes/{$athlete->id}/permanent", ['confirm' => true])
        ->assertRedirect('/athletes?deleted=1')
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('athletes', ['id' => $athlete->id]);
});

test('permanent deletion removes sport roster membership without a foreign key error', function () {
    $athlete = Athlete::factory()->create();
    $membership = SportRosterMember::query()->create([
        'meet_sport_id' => MeetSport::factory()->create([
            'meet_id' => $athlete->delegation->meet_id,
        ])->id,
        'delegation_id' => $athlete->delegation_id,
        'athlete_id' => $athlete->id,
        'level' => $athlete->ageDivision()->value,
        'gender' => $athlete->sex->value === 'female' ? 'girls' : 'boys',
    ]);
    $athlete->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/athletes/{$athlete->id}/permanent", ['confirm' => true])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('sport_roster_members', ['id' => $membership->id]);
    $this->assertDatabaseMissing('athletes', ['id' => $athlete->id]);
});

test('normal athlete deletion preserves roster membership and registration history', function () {
    $athlete = Athlete::factory()->create();
    $entry = Entry::factory()->create(['athlete_id' => $athlete->id, 'delegation_id' => $athlete->delegation_id]);
    $membership = SportRosterMember::query()->create([
        'meet_sport_id' => MeetSport::factory()->create(['meet_id' => $athlete->delegation->meet_id])->id,
        'delegation_id' => $athlete->delegation_id, 'athlete_id' => $athlete->id,
        'level' => $athlete->ageDivision()->value, 'gender' => $athlete->sex->value === 'female' ? 'girls' : 'boys',
    ]);

    $this->actingAs(User::factory()->admin()->create())->delete("/athletes/{$athlete->id}");

    $this->assertSoftDeleted('athletes', ['id' => $athlete->id]);
    $this->assertDatabaseHas('entries', ['id' => $entry->id]);
    $this->assertDatabaseHas('sport_roster_members', ['id' => $membership->id]);
});

test('accreditation or official results block permanent deletion without partial cleanup', function (string $history) {
    $athlete = Athlete::factory()->create();
    $entry = Entry::factory()->create(['athlete_id' => $athlete->id, 'delegation_id' => $athlete->delegation_id]);
    $membership = SportRosterMember::query()->create([
        'meet_sport_id' => MeetSport::factory()->create(['meet_id' => $athlete->delegation->meet_id])->id,
        'delegation_id' => $athlete->delegation_id, 'athlete_id' => $athlete->id,
        'level' => $athlete->ageDivision()->value, 'gender' => $athlete->sex->value === 'female' ? 'girls' : 'boys',
    ]);

    if ($history === 'accreditation') {
        Accreditation::factory()->create(['athlete_id' => $athlete->id, 'personnel_id' => null, 'delegation_id' => $athlete->delegation_id]);
    } else {
        $result = EventResult::factory()->create(['event_id' => $entry->event_id, 'meet_id' => $athlete->delegation->meet_id]);
        ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id]);
    }
    $athlete->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/athletes/{$athlete->id}/permanent", ['confirm' => true])
        ->assertSessionHasErrors('confirm');

    $this->assertSoftDeleted('athletes', ['id' => $athlete->id]);
    $this->assertDatabaseHas('entries', ['id' => $entry->id]);
    $this->assertDatabaseHas('sport_roster_members', ['id' => $membership->id]);
})->with(['accreditation', 'result']);

test('non administrators cannot permanently delete an athlete', function () {
    $athlete = Athlete::factory()->create();
    $athlete->delete();

    $this->actingAs(User::factory()->create())
        ->delete("/athletes/{$athlete->id}/permanent", ['confirm' => true])
        ->assertForbidden();

    $this->assertSoftDeleted('athletes', ['id' => $athlete->id]);
});

test('athlete files are cleaned only after permanent deletion succeeds', function () {
    Storage::fake('local');
    $athlete = Athlete::factory()->create();
    $upload = FileUpload::factory()->create(['disk' => 'local', 'path' => 'athletes/profile.jpg']);
    Storage::disk('local')->put($upload->path, 'image');
    $athlete->forceFill(['photo_upload_id' => $upload->id])->save();
    $athlete->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/athletes/{$athlete->id}/permanent", ['confirm' => true])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('file_uploads', ['id' => $upload->id]);
    Storage::disk('local')->assertMissing($upload->path);
});

test('blocked permanent deletion retains athlete files', function () {
    Storage::fake('local');
    $athlete = Athlete::factory()->create();
    $upload = FileUpload::factory()->create(['disk' => 'local', 'path' => 'athletes/protected.jpg']);
    Storage::disk('local')->put($upload->path, 'image');
    $athlete->forceFill(['photo_upload_id' => $upload->id])->save();
    Accreditation::factory()->create(['athlete_id' => $athlete->id, 'personnel_id' => null, 'delegation_id' => $athlete->delegation_id]);
    $athlete->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/athletes/{$athlete->id}/permanent", ['confirm' => true])
        ->assertSessionHasErrors('confirm');

    $this->assertDatabaseHas('file_uploads', ['id' => $upload->id]);
    Storage::disk('local')->assertExists($upload->path);
});

test('delegations with athletes cannot be deleted', function () {
    $athlete = Athlete::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/delegations/{$athlete->delegation_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('delegations', ['id' => $athlete->delegation_id]);
});

test('ICT athlete totals match the complete current meet registration table', function () {
    $meet = Meet::factory()->active()->create();
    $delegations = Delegation::factory()->count(2)->approved()->create(['meet_id' => $meet->id]);
    Athlete::factory()->count(2)->create(['delegation_id' => $delegations[0]->id]);
    Athlete::factory()->count(3)->create(['delegation_id' => $delegations[1]->id]);

    $otherMeet = Meet::factory()->create(['is_active' => false]);
    $otherDelegation = Delegation::factory()->approved()->create(['meet_id' => $otherMeet->id]);
    Athlete::factory()->create(['delegation_id' => $otherDelegation->id]);

    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);

    $this->actingAs($ict)->get('/athletes')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('athletes.data', 5));

    $this->actingAs($ict)->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stats.0.key', 'athletes')
            ->where('stats.0.value', 5));
});
