<?php

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\RequirementStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    Storage::fake('local');
});

function eligibilityOfficerFor(Delegation $delegation): User
{
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    return $officer;
}

function uploadDocumentFor(Athlete $athlete, User $actor): void
{
    test()->actingAs($actor)->post('/eligibility/documents', [
        'athlete_id' => $athlete->id,
        'document_type' => 'birth_certificate',
        'file' => UploadedFile::fake()->image('birth-cert.jpg'),
    ]);
}

function createVerifiedQualificationDocuments(Athlete $athlete, ?EligibilityDocumentType $except = null): void
{
    foreach (EligibilityDocumentType::qualificationRequirements() as $type) {
        if ($type === $except) {
            continue;
        }
        EligibilityDocument::factory()->create([
            'athlete_id' => $athlete->id,
            'document_type' => $type,
            'status' => RequirementStatus::Verified,
        ]);
    }
}

test('guests are redirected and viewers are forbidden', function () {
    $this->get('/eligibility')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/eligibility')
        ->assertForbidden();
});

test('an officer upload creates a document and a pending review', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $officer = eligibilityOfficerFor($delegation);

    $this->actingAs($officer)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->image('birth-cert.jpg'),
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    expect(EligibilityDocument::query()->count())->toBe(1)
        ->and(EligibilityReview::query()->sole()->status)->toBe(EligibilityStatus::Pending)
        ->and(AuditLog::query()->where('action', 'eligibility.document_uploaded')->exists())->toBeTrue();
});

test('an incomplete athlete remains in the DSAC queue with all five checklist requirements', function () {
    $athlete = Athlete::factory()->create();
    EligibilityReview::factory()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $athlete->delegation->meet_id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/eligibility')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reviews.data.0.requirement_checklist', 5)
            ->where('reviews.data.0.requirements_validated', false)
            ->where('reviews.data.0.requirements_summary', '0 of 5 required documents validated'));
});

test('officers cannot upload for foreign athletes or when registration is closed', function () {
    $foreign = Athlete::factory()->create();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->post('/eligibility/documents', [
            'athlete_id' => $foreign->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->image('cert.jpg'),
        ])
        ->assertForbidden();

    $closedDelegation = Delegation::factory()->create(['meet_id' => Meet::factory()->create()]);
    $athlete = Athlete::factory()->create(['delegation_id' => $closedDelegation->id]);
    $officer = eligibilityOfficerFor($closedDelegation);

    $this->actingAs($officer)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->image('cert.jpg'),
        ])
        ->assertForbidden();
});

test('disallowed file types and bad document types are rejected', function () {
    $athlete = Athlete::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
        ])
        ->assertSessionHasErrors('file');

    $this->actingAs($admin)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'diploma',
            'file' => UploadedFile::fake()->image('cert.jpg'),
        ])
        ->assertSessionHasErrors('document_type');
});

test('document downloads are authorized and audited', function () {
    $athlete = Athlete::factory()->create();
    $admin = User::factory()->admin()->create();
    uploadDocumentFor($athlete, $admin);

    $document = EligibilityDocument::query()->sole();

    $this->actingAs($admin)
        ->get("/eligibility/documents/{$document->id}")
        ->assertOk();

    expect(AuditLog::query()->where('action', 'eligibility.document_viewed')->exists())->toBeTrue();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/eligibility/documents/{$document->id}")
        ->assertForbidden();
});

test('DSAC can approve a pending review and officers cannot decide', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $review = EligibilityReview::factory()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);
    $officer = eligibilityOfficerFor($delegation);

    $this->actingAs($officer)
        ->patch("/eligibility/reviews/{$review->id}/approve")
        ->assertForbidden();

    $organizer = User::factory()->organizer()->create();
    $this->actingAs($organizer)->patch("/eligibility/reviews/{$review->id}/approve")->assertForbidden();

    $team = ManagementTeam::factory()->create(['meet_id' => $delegation->meet_id, 'team_type' => ManagementTeamType::DivisionScreeningAndAccreditation]);
    createVerifiedQualificationDocuments($athlete);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'role_title' => 'Chairperson',
        'status' => ManagementTeamMemberStatus::Active,
    ]);
    $dsac = $member->user;

    $this->actingAs($dsac)
        ->patch("/eligibility/reviews/{$review->id}/approve", ['remarks' => 'Complete papers.'])
        ->assertRedirect();

    $review->refresh();

    expect($review->status)->toBe(EligibilityStatus::Approved)
        ->and($review->reviewer_id)->toBe($dsac->id)
        ->and($review->decided_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'eligibility.approved')->exists())->toBeTrue();
});

test('DSAC members validate requirements one by one but only leadership performs final review', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $review = EligibilityReview::factory()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);
    $document = EligibilityDocument::factory()->create([
        'athlete_id' => $athlete->id,
        'document_type' => 'birth_certificate',
        'status' => RequirementStatus::Submitted,
    ]);
    $team = ManagementTeam::factory()->create([
        'meet_id' => $delegation->meet_id,
        'team_type' => ManagementTeamType::DivisionScreeningAndAccreditation,
        'source_code' => 'DSAC',
    ]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'role_title' => 'Member',
        'status' => ManagementTeamMemberStatus::Active,
    ])->user;
    $leader = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'role_title' => 'Assistant Chairperson',
        'status' => ManagementTeamMemberStatus::Active,
    ])->user;

    $this->actingAs($member)
        ->patch("/eligibility/documents/{$document->id}/status", ['status' => 'verified'])
        ->assertSessionHasNoErrors();

    expect($document->fresh()->status)->toBe(RequirementStatus::Verified)
        ->and($document->fresh()->verified_by)->toBe($member->id);
    createVerifiedQualificationDocuments($athlete, EligibilityDocumentType::BirthCertificate);

    $this->actingAs($member)
        ->patch("/eligibility/reviews/{$review->id}/approve")
        ->assertForbidden();

    $this->actingAs($leader)
        ->patch("/eligibility/reviews/{$review->id}/approve", ['remarks' => 'All requirements validated.'])
        ->assertSessionHasNoErrors();

    expect($review->fresh()->status)->toBe(EligibilityStatus::Approved)
        ->and($review->fresh()->reviewer_id)->toBe($leader->id);
});

test('DSAC leadership cannot approve while any requirement is not validated', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $review = EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $delegation->meet_id]);
    EligibilityDocument::factory()->create([
        'athlete_id' => $athlete->id,
        'document_type' => 'birth_certificate',
        'status' => RequirementStatus::Submitted,
    ]);
    $team = ManagementTeam::factory()->create([
        'meet_id' => $delegation->meet_id,
        'team_type' => ManagementTeamType::DivisionScreeningAndAccreditation,
    ]);
    $leader = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'role_title' => 'Chairperson',
        'status' => ManagementTeamMemberStatus::Active,
    ])->user;

    $this->actingAs($leader)
        ->patch("/eligibility/reviews/{$review->id}/approve")
        ->assertSessionHasErrors('requirements');

    expect($review->fresh()->status)->toBe(EligibilityStatus::Pending);
});

test('returning a review requires remarks', function () {
    $review = EligibilityReview::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/eligibility/reviews/{$review->id}/return", ['remarks' => ''])
        ->assertSessionHasErrors('remarks');

    $this->actingAs($admin)
        ->patch("/eligibility/reviews/{$review->id}/return", ['remarks' => 'Birth certificate unreadable.'])
        ->assertRedirect();

    expect($review->refresh()->status)->toBe(EligibilityStatus::Returned)
        ->and(AuditLog::query()->where('action', 'eligibility.returned')->exists())->toBeTrue();
});

test('decided reviews cannot be decided again', function () {
    $review = EligibilityReview::factory()->approved()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/eligibility/reviews/{$review->id}/return", ['remarks' => 'Too late.'])
        ->assertRedirect();

    expect($review->refresh()->status)->toBe(EligibilityStatus::Approved);
});

test('rejecting a review requires remarks and officers cannot decide', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $review = EligibilityReview::factory()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);
    $officer = eligibilityOfficerFor($delegation);

    $this->actingAs($officer)
        ->patch("/eligibility/reviews/{$review->id}/reject", ['remarks' => 'Not qualified.'])
        ->assertForbidden();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/eligibility/reviews/{$review->id}/reject", ['remarks' => ''])
        ->assertSessionHasErrors('remarks');

    $this->actingAs($admin)
        ->patch("/eligibility/reviews/{$review->id}/reject", ['remarks' => 'Age does not match documents.'])
        ->assertRedirect();

    $review->refresh();

    expect($review->status)->toBe(EligibilityStatus::Rejected)
        ->and($review->reviewer_id)->toBe($admin->id)
        ->and($review->decided_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'eligibility.rejected')->exists())->toBeTrue();
});

test('a rejected review is terminal — unlike a returned one, a fresh upload does not reopen it', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $review = EligibilityReview::factory()->rejected()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);
    $officer = eligibilityOfficerFor($delegation);

    $this->actingAs($officer)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'report_card',
            'file' => UploadedFile::fake()->image('card.jpg'),
        ])
        ->assertSessionHasErrors('athlete_id');

    expect($review->refresh()->status)->toBe(EligibilityStatus::Rejected);
});

test('a rejected review cannot be decided again', function () {
    $review = EligibilityReview::factory()->rejected()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/eligibility/reviews/{$review->id}/approve", ['remarks' => 'Reconsidered.'])
        ->assertRedirect();

    expect($review->refresh()->status)->toBe(EligibilityStatus::Rejected);
});

test('uploading to a returned review resubmits it as pending', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $review = EligibilityReview::factory()->returned()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);
    $officer = eligibilityOfficerFor($delegation);

    uploadDocumentFor($athlete, $officer);

    $review->refresh();

    expect($review->status)->toBe(EligibilityStatus::Pending)
        ->and($review->remarks)->toBeNull()
        ->and($review->decided_at)->toBeNull()
        ->and(AuditLog::query()->where('action', 'eligibility.resubmitted')->exists())->toBeTrue();
});

test('uploads are blocked once the review is approved', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'report_card',
            'file' => UploadedFile::fake()->image('card.jpg'),
        ])
        ->assertSessionHasErrors('athlete_id');
});

test('documents can be removed while pending but not after approval', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $admin = User::factory()->admin()->create();
    uploadDocumentFor($athlete, $admin);

    $document = EligibilityDocument::query()->sole();
    $review = EligibilityReview::query()->sole();

    $review->forceFill(['status' => EligibilityStatus::Approved])->save();

    $this->actingAs($admin)
        ->delete("/eligibility/documents/{$document->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('eligibility_documents', ['id' => $document->id]);

    $review->forceFill(['status' => EligibilityStatus::Pending])->save();

    $this->actingAs($admin)
        ->delete("/eligibility/documents/{$document->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('eligibility_documents', ['id' => $document->id]);
});

test('documents cannot be removed after rejection either', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $admin = User::factory()->admin()->create();
    uploadDocumentFor($athlete, $admin);

    $document = EligibilityDocument::query()->sole();
    $review = EligibilityReview::query()->sole();

    $review->forceFill(['status' => EligibilityStatus::Rejected])->save();

    $this->actingAs($admin)
        ->delete("/eligibility/documents/{$document->id}")
        ->assertRedirect();

    $this->assertDatabaseHas('eligibility_documents', ['id' => $document->id]);
});

test('officers see only their own delegation\'s reviews', function () {
    $mine = Delegation::factory()->create();
    $myAthlete = Athlete::factory()->create(['delegation_id' => $mine->id]);
    EligibilityReview::factory()->create([
        'athlete_id' => $myAthlete->id,
        'meet_id' => $mine->meet_id,
    ]);
    EligibilityReview::factory()->create();
    $officer = eligibilityOfficerFor($mine);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/eligibility')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('eligibility/index')
            ->has('reviews.data', 2));

    $this->actingAs($officer)
        ->get('/eligibility')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reviews.data', 1));
});

test('the queue can be filtered by status', function () {
    EligibilityReview::factory()->create();
    EligibilityReview::factory()->approved()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/eligibility?status=pending')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reviews.data', 1)
            ->where('reviews.data.0.status', 'pending'));
});

test('the queue can be searched by athlete name', function () {
    $athlete = Athlete::factory()->create(['first_name' => 'Juan', 'last_name' => 'Dela Cruz']);
    EligibilityReview::factory()->create(['athlete_id' => $athlete->id]);

    $other = Athlete::factory()->create(['first_name' => 'Maria', 'last_name' => 'Santos']);
    EligibilityReview::factory()->create(['athlete_id' => $other->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/eligibility?search=Dela+Cruz')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reviews.data', 1)
            ->where('reviews.data.0.athlete', 'Juan Dela Cruz')
            ->where('filters.search', 'Dela Cruz'));
});

test('summary counts reflect the whole queue regardless of the status filter', function () {
    EligibilityReview::factory()->count(2)->create();
    EligibilityReview::factory()->approved()->create();
    EligibilityReview::factory()->returned()->create();
    EligibilityReview::factory()->rejected()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/eligibility?status=approved')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('reviews.data', 1)
            ->where('counts.pending', 2)
            ->where('counts.approved', 1)
            ->where('counts.returned', 1)
            ->where('counts.rejected', 1));
});

test('entries flag athletes whose eligibility is not approved', function () {
    $delegation = Delegation::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $entry = Entry::factory()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/entries')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entries.data.0.eligibility_approved', false));

    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);

    $this->actingAs($admin)
        ->get('/entries')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entries.data.0.eligibility_approved', true));

    expect($entry->refresh()->status->value)->toBe('submitted');
});
