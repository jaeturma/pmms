<?php

use App\Enums\EligibilityDocumentType;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\RequirementStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityDocument;
use App\Models\Entry;
use App\Models\FileUpload;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
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
            ->where('athletes.data.0.name', 'Ana Reyes'));

    $this->actingAs($admin)
        ->get('/athletes?search=999888')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.name', 'Ben Cruz'));
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
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => schoolForDelegation($delegation)->id]);
    $ict = User::factory()->create();
    $team = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id, 'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($ict)->get("/athletes/{$athlete->id}/edit")
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('athletes/edit')->where('athlete.id', $athlete->id));

    $this->actingAs($ict)->put("/athletes/{$athlete->id}", [
        ...validAthletePayload($delegation), 'first_name' => 'Updated',
        'lrn' => $athlete->lrn, 'meet_sport_ids' => [], 'event_ids' => [],
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($athlete->refresh()->first_name)->toBe('Updated');
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
            ->where('athletes.data.0.coach', 'Coach Maria')
            ->where('athletes.data.0.delegation', $athlete->delegation->registrantName()));

    $this->actingAs($admin)
        ->get("/athletes/{$athlete->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('athlete.sports', 'Basketball')
            ->where('athlete.coach', 'Coach Maria')
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

    expect($athlete->refresh()->first_name)->toBe('Renamed')
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

test('delegations with athletes cannot be deleted', function () {
    $athlete = Athlete::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/delegations/{$athlete->delegation_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('delegations', ['id' => $athlete->delegation_id]);
});
