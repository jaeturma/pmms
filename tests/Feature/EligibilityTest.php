<?php

use App\Enums\EligibilityStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\Entry;
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
        'file' => UploadedFile::fake()->create('birth-cert.pdf', 200, 'application/pdf'),
    ]);
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
            'file' => UploadedFile::fake()->create('birth-cert.pdf', 200, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    expect(EligibilityDocument::query()->count())->toBe(1)
        ->and(EligibilityReview::query()->sole()->status)->toBe(EligibilityStatus::Pending)
        ->and(AuditLog::query()->where('action', 'eligibility.document_uploaded')->exists())->toBeTrue();
});

test('officers cannot upload for foreign athletes or when registration is closed', function () {
    $foreign = Athlete::factory()->create();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->post('/eligibility/documents', [
            'athlete_id' => $foreign->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        ])
        ->assertForbidden();

    $closedDelegation = Delegation::factory()->create(['meet_id' => Meet::factory()->create()]);
    $athlete = Athlete::factory()->create(['delegation_id' => $closedDelegation->id]);
    $officer = eligibilityOfficerFor($closedDelegation);

    $this->actingAs($officer)
        ->post('/eligibility/documents', [
            'athlete_id' => $athlete->id,
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
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
            'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
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

test('managers can approve a pending review and officers cannot decide', function () {
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

    $this->actingAs($organizer)
        ->patch("/eligibility/reviews/{$review->id}/approve", ['remarks' => 'Complete papers.'])
        ->assertRedirect();

    $review->refresh();

    expect($review->status)->toBe(EligibilityStatus::Approved)
        ->and($review->reviewer_id)->toBe($organizer->id)
        ->and($review->decided_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'eligibility.approved')->exists())->toBeTrue();
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
            'file' => UploadedFile::fake()->create('card.pdf', 100, 'application/pdf'),
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

    expect(AuditLog::query()->where('action', 'eligibility.document_deleted')->exists())->toBeTrue();
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
