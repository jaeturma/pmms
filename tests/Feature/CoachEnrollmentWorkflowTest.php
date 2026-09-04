<?php

use App\Enums\DelegationStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\RequirementStatus;
use App\Enums\UserRole;
use App\Models\Accreditation;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\FileUpload;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Personnel;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('approved coach scope limits registration and DSAC accreditation confirms the official entry', function () {
    $meet = Meet::factory()->registrationOpen()->create(['medical_clearance_required' => false]);
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id, 'gender' => 'boys', 'age_division' => 'secondary']);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id, 'status' => DelegationStatus::Draft]);
    $school = schoolForDelegation($delegation);
    $coach = User::factory()->coach()->create();
    $manager = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id, 'user_id' => $manager->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($coach)->post('/coach/assignment-requests', [
        'meet_sport_id' => $meetSport->id, 'event_id' => $event->id,
        'delegation_id' => $delegation->id,
    ])->assertSessionHasNoErrors();
    $request = CoachAssignmentRequest::query()->sole();
    $this->actingAs($manager)->patch("/coach/assignment-requests/{$request->id}", ['status' => 'approved'])->assertSessionHasNoErrors();

    $athletePayload = [
        'delegation_id' => $delegation->id, 'school_id' => $school->id,
        'first_name' => 'Juan', 'middle_name' => 'N/A', 'last_name' => 'Dela Cruz', 'name_extension' => 'None', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => '123456789012', 'grade_level' => 9,
    ];
    $this->actingAs($coach)->post('/athletes', $athletePayload)->assertSessionHasNoErrors();
    $athlete = Athlete::query()->sole();
    expect(Entry::query()->doesntExist())->toBeTrue()
        ->and($athlete->sportRosterMemberships()->where('meet_sport_id', $meetSport->id)->exists())->toBeTrue();

    $delegation->forceFill(['status' => DelegationStatus::Approved])->save();
    $athlete->eligibilityReview()->update([
        'status' => 'approved',
        'decided_at' => now(),
    ]);
    EligibilityDocument::factory()->create(['athlete_id' => $athlete->id, 'status' => RequirementStatus::Verified]);
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::DivisionScreeningAndAccreditation]);
    $dsac = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'role_title' => 'Team Leader',
        'status' => ManagementTeamMemberStatus::Active,
    ])->user;
    $this->actingAs($dsac)->post('/accreditations', ['athlete_id' => $athlete->id])->assertSessionHasNoErrors();
    $this->actingAs($coach)->post('/entries', ['athlete_id' => $athlete->id, 'event_id' => $event->id])
        ->assertSessionHasNoErrors();

    expect($athlete->accreditation()->exists())->toBeTrue()
        ->and(Entry::query()->sole()->status->value)->toBe('submitted');
});

test('an active ICT team member can review and approve a coach registration', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = User::factory()->coach()->create();
    $coachRequest = CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'pending',
    ]);

    $ict = User::factory()->create();
    $ictTeam = ManagementTeam::factory()->create([
        'meet_id' => $meet->id,
        'team_type' => ManagementTeamType::ICT,
    ]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $ictTeam->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($ict)
        ->get('/coach/assignment-requests')
        ->assertOk();

    $this->actingAs($ict)
        ->patch("/coach/assignment-requests/{$coachRequest->id}", ['status' => 'approved'])
        ->assertSessionHasNoErrors();

    expect($coachRequest->fresh()->status)->toBe('approved')
        ->and($coach->fresh()->role)->toBe(UserRole::Coach);
});

test('tournament personnel can accredit an approved coach with their selected team and sports', function (MeetSportAssignmentRole $role) {
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $school = schoolForDelegation($delegation);
    $coach = User::factory()->coach()->create(['name' => 'Maria Santos']);
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id, 'district_id' => $school->district_id,
        'event_id' => $event->id, 'status' => 'pending',
        'profile_upload_id' => FileUpload::factory()->create()->id,
        'certification_upload_id' => FileUpload::factory()->create()->id,
    ]);
    $onboarding->events()->attach($event);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->patch("/coach/onboarding-requests/{$onboarding->id}", ['status' => 'approved'])
        ->assertSessionHasNoErrors();

    $person = Personnel::query()->where('user_id', $coach->id)->sole();
    expect($person->delegation_id)->toBe($delegation->id)
        ->and($person->sports()->whereKey($sport->id)->exists())->toBeTrue();

    // Simulate a registration approved before automatic coach roster
    // synchronization existed. Accreditation must backfill it, not 404.
    $person->delete();
    CoachAssignmentRequest::query()->where('user_id', $coach->id)->update(['status' => 'pending']);

    $accreditor = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id, 'user_id' => $accreditor->id,
        'role' => $role, 'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($accreditor)->post("/coach/onboarding-requests/{$onboarding->id}/accredit")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $person = Personnel::query()->where('user_id', $coach->id)->sole();
    expect($person->accreditation()->first()?->number)->toStartWith('ACR-');
})->with([
    MeetSportAssignmentRole::TournamentManager,
    MeetSportAssignmentRole::AssistantTournamentManager,
    MeetSportAssignmentRole::TournamentSecretary,
    MeetSportAssignmentRole::TournamentICT,
]);

test('a documented municipality coach can be accredited without a school or pre-existing assignment', function () {
    $meet = Meet::factory()->registrationOpen()->create(['is_active' => true]);
    $district = District::factory()->create();
    $delegation = Delegation::factory()->approved()->create([
        'meet_id' => $meet->id, 'district_id' => $district->id, 'school_id' => null,
    ]);
    $event = Event::factory()->create();
    $coach = User::factory()->coach()->create();
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id, 'district_id' => $district->id,
        'event_id' => $event->id, 'status' => 'approved',
        'profile_upload_id' => FileUpload::factory()->create()->id,
        'certification_upload_id' => FileUpload::factory()->create()->id,
    ]);
    $onboarding->events()->attach($event);

    $this->actingAs(User::factory()->admin()->create())
        ->post("/coach/onboarding-requests/{$onboarding->id}/accredit")
        ->assertSessionHasNoErrors();

    $person = Personnel::query()->where('user_id', $coach->id)->sole();
    expect($person->delegation_id)->toBe($delegation->id)
        ->and($person->school_id)->toBeNull()
        ->and($person->accreditation()->exists())->toBeTrue();
});

test('coach registration management excludes archived users from operational queues', function () {
    $coach = User::factory()->coach()->create(['name' => 'Archived Coach']);
    CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'status' => 'pending',
    ]);
    $coach->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/coach/assignment-requests')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('registrations.data', 0));

    expect(CoachOnboardingRequest::query()->sole()->userIncludingDeleted?->name)->toBe('ARCHIVED COACH');
});

test('ict dashboard and coach queues survive stale actionable rows for a deleted coach', function () {
    $meet = Meet::factory()->registrationOpen()->create(['is_active' => true]);
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = User::factory()->coach()->create(['name' => 'Removed Legacy Coach']);
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id, 'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id, 'event_id' => $event->id,
        'status' => 'approved',
    ]);
    CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id, 'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id, 'event_id' => $event->id,
        'status' => 'approved', 'ended_at' => null,
    ]);
    $coach->delete();

    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id, 'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($ict)->get('/dashboard')->assertOk();
    $this->actingAs($ict)->get('/coach/assignment-requests')->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('requests.data', 0)
            ->has('registrations.data', 0));

    $this->actingAs($ict)->put("/coach/onboarding-requests/{$onboarding->id}/assignments", [
        'event_ids' => [$event->id],
    ])->assertSessionHasErrors('coach');

    expect(CoachAssignmentRequest::query()->where('user_id', $coach->id)->count())->toBe(1);
});

test('a coach may replace attachments after approval and accreditation', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->approved()->create();
    $coach = User::factory()->coach()->create();
    $event = Event::factory()->create();
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'district_id' => $delegation->school->district_id,
        'event_id' => $event->id,
        'status' => 'approved',
    ]);
    $onboarding->events()->attach($event);

    $this->actingAs($coach)->post("/coach/onboarding-requests/{$onboarding->id}/documents/profile", [
        'document' => UploadedFile::fake()->image('coach-profile.jpg'),
    ])->assertSessionDoesntHaveErrors();
    $profileId = $onboarding->fresh()->profile_upload_id;
    expect($profileId)->not->toBeNull();
    $this->actingAs($coach)
        ->get("/coach/onboarding-requests/{$onboarding->id}/documents/profile")
        ->assertOk();

    $personnel = Personnel::factory()->coach()->create([
        'delegation_id' => $delegation->id,
        'user_id' => $coach->id,
    ]);
    Accreditation::factory()->create([
        'delegation_id' => $delegation->id,
        'personnel_id' => $personnel->id,
    ]);

    $this->actingAs($coach)->post("/coach/onboarding-requests/{$onboarding->id}/documents/profile", [
        'document' => UploadedFile::fake()->image('replacement.jpg'),
    ])->assertSessionDoesntHaveErrors();

    expect($onboarding->fresh()->profile_upload_id)->not->toBe($profileId);
});

test('only assigned sport ICT and administrators may replace another coach attachments', function () {
    Storage::fake('local');
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $otherSport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $otherMeetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $otherSport->id]);
    $coach = User::factory()->coach()->create();
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'district_id' => District::factory()->create()->id,
        'meet_sport_id' => $meetSport->id,
        'status' => 'approved',
    ]);
    $assignedIct = User::factory()->create();
    $otherIct = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'user_id' => $assignedIct->id,
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);
    MeetSportAssignment::factory()->create([
        'user_id' => $otherIct->id,
        'meet_sport_id' => $otherMeetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($assignedIct)->post("/coach/onboarding-requests/{$onboarding->id}/documents/certification", [
        'document' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
    ])->assertSessionDoesntHaveErrors();
    $ictUploadId = $onboarding->fresh()->certification_upload_id;

    $this->actingAs($otherIct)->post("/coach/onboarding-requests/{$onboarding->id}/documents/certification", [
        'document' => UploadedFile::fake()->create('wrong-sport.pdf', 100, 'application/pdf'),
    ])->assertForbidden();
    expect($onboarding->fresh()->certification_upload_id)->toBe($ictUploadId);

    $this->actingAs(User::factory()->admin()->create())->post("/coach/onboarding-requests/{$onboarding->id}/documents/certification", [
        'document' => UploadedFile::fake()->create('admin-replacement.pdf', 100, 'application/pdf'),
    ])->assertSessionDoesntHaveErrors();
    expect($onboarding->fresh()->certification_upload_id)->not->toBe($ictUploadId);
});

test('sport ICT can fully manage a coach registration and view all of its athletes with photos', function () {
    Storage::fake('local');
    config()->set('pmms.accounts.default_reset_password', 'ScopedResetPassword123!');
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id, 'gender' => 'boys', 'age_division' => 'secondary']);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coach = User::factory()->create(['role' => UserRole::Viewer, 'approval_status' => 'pending']);
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
        'status' => 'pending',
    ]);
    $onboarding->events()->attach($event);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id,
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($ict)->patch("/coach/onboarding-requests/{$onboarding->id}", [
        'status' => 'approved',
        'event_ids' => [$event->id],
    ])->assertSessionDoesntHaveErrors();
    $this->actingAs($ict)->patch("/coach/onboarding-requests/{$onboarding->id}/information", [
        'name' => 'Updated Coach',
        'email' => 'updated-coach@example.test',
        'district_id' => schoolForDelegation($delegation)->district_id,
        'school_id' => schoolForDelegation($delegation)->id,
    ])->assertSessionDoesntHaveErrors();
    $this->actingAs($ict)->post("/coach/onboarding-requests/{$onboarding->id}/documents/profile", [
        'document' => UploadedFile::fake()->image('coach-profile.jpg'),
    ])->assertSessionDoesntHaveErrors();
    $this->actingAs($ict)->post("/coach/onboarding-requests/{$onboarding->id}/reset-password")
        ->assertSessionDoesntHaveErrors();

    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'registered_by' => $coach->id,
    ]);
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $meet->id,
    ]);
    $this->actingAs($ict)->put("/athletes/{$athlete->id}", [
        'photo' => UploadedFile::fake()->image('athlete-profile.jpg'),
    ])->assertSessionDoesntHaveErrors();

    $this->actingAs($ict)->get('/coach/assignment-requests')
        ->assertInertia(fn ($page) => $page
            ->where('registrations.data.0.can_update_information', true)
            ->where('registrations.data.0.can_update_attachments', true)
            ->where('registrations.data.0.can_reset_password', true)
            ->has('registrations.data.0.registered_athletes', 1)
            ->where('registrations.data.0.registered_athletes.0.id', $athlete->id)
            ->where('registrations.data.0.registered_athletes.0.photo_url', fn ($url) => is_string($url) && $url !== ''));
    $this->actingAs($ict)->get("/athletes/{$athlete->id}")->assertOk();
    $this->actingAs($ict)->get("/athletes/{$athlete->id}/photo")
        ->assertOk()
        ->assertHeader('content-disposition', 'inline');

    expect($coach->fresh()->role)->toBe(UserRole::Coach)
        ->and($coach->fresh()->name)->toBe('UPDATED COACH')
        ->and(Hash::check('ScopedResetPassword123!', $coach->fresh()->password))->toBeTrue();
});

test('an inactive ICT team membership cannot approve a coach registration', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $coachRequest = CoachAssignmentRequest::query()->create([
        'user_id' => User::factory()->coach()->create()->id,
        'meet_sport_id' => $meetSport->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'pending',
    ]);
    $ict = User::factory()->create();
    $ictTeam = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $ictTeam->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Ended,
    ]);

    $this->actingAs($ict)
        ->patch("/coach/assignment-requests/{$coachRequest->id}", ['status' => 'approved'])
        ->assertForbidden();
});
