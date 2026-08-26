<?php

use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
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
use Illuminate\Support\Facades\Storage;

test('approved coach scope limits registration and DSAC accreditation confirms the official entry', function () {
    $meet = Meet::factory()->registrationOpen()->create();
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
        'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'sex' => 'male',
        'birthdate' => now()->subYears(15)->toDateString(), 'lrn' => '123456789012', 'grade_level' => 9,
    ];
    $this->actingAs($coach)->post('/athletes', $athletePayload)->assertSessionHasNoErrors();
    $athlete = Athlete::query()->sole();
    expect(Entry::query()->sole()->status)->toBe(EntryStatus::Submitted);

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

    expect($athlete->accreditation()->exists())->toBeTrue()
        ->and(Entry::query()->sole()->fresh()->status)->toBe(EntryStatus::Submitted);
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

test('a coach may replace only attachments until approval and accreditation are both complete', function () {
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
    ])->assertSessionHasErrors('document');

    expect($onboarding->fresh()->profile_upload_id)->toBe($profileId);
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
