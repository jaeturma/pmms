<?php

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\RequirementStatus;
use App\Models\Athlete;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\FileUpload;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\TechnicalOfficialAccreditation;
use App\Models\User;
use App\Services\Eligibility\AthleteEligibilityChecker;
use App\Services\Eligibility\TechnicalOfficialEligibilityChecker;

function verifiedAthleteDocuments(Athlete $athlete, array $except = []): void
{
    foreach ([EligibilityDocumentType::SchoolId, EligibilityDocumentType::BirthCertificate, EligibilityDocumentType::MedicalCertificate, EligibilityDocumentType::ParentalConsent] as $type) {
        if (in_array($type, $except, true)) {
            continue;
        }
        EligibilityDocument::factory()->create(['athlete_id' => $athlete->id, 'document_type' => $type, 'status' => RequirementStatus::Verified]);
    }
}

test('complete verified athlete requirements are eligible and a missing PSA is pending', function () {
    $athlete = Athlete::factory()->create();
    $meet = $athlete->delegation->meet;
    $meet->forceFill(['medical_clearance_required' => false])->save();
    EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $meet->id, 'status' => EligibilityStatus::Approved]);
    verifiedAthleteDocuments($athlete);
    expect(app(AthleteEligibilityChecker::class)->evaluate($athlete->fresh(), $meet->fresh())->result()->value)->toBe('eligible');

    $athlete->eligibilityDocuments()->where('document_type', EligibilityDocumentType::BirthCertificate)->delete();
    expect(app(AthleteEligibilityChecker::class)->evaluate($athlete->fresh(), $meet->fresh())->result()->value)->toBe('pending_requirements');
});

test('a rejected document or exceeded event limit makes an athlete ineligible', function () {
    $athlete = Athlete::factory()->create();
    $meet = $athlete->delegation->meet;
    $meet->forceFill(['medical_clearance_required' => false, 'max_events_per_athlete' => 0])->save();
    EligibilityReview::factory()->approved()->create(['athlete_id' => $athlete->id, 'meet_id' => $meet->id]);
    verifiedAthleteDocuments($athlete);
    $athlete->eligibilityDocuments()->where('document_type', EligibilityDocumentType::BirthCertificate)->update(['status' => RequirementStatus::Rejected]);
    expect(app(AthleteEligibilityChecker::class)->evaluate($athlete->fresh(), $meet->fresh())->result()->value)->toBe('ineligible');
});

test('verified valid technical official accreditation and active assignment are eligible', function () {
    $official = User::factory()->technicalOfficial()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['sport_id' => $sport->id]);
    $meetSport->meet->forceFill(['medical_clearance_required' => false])->save();
    MeetSportAssignment::factory()->create(['meet_sport_id' => $meetSport->id, 'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial, 'status' => MeetSportAssignmentStatus::Active]);
    TechnicalOfficialAccreditation::query()->create(['user_id' => $official->id, 'sport_id' => $sport->id,
        'file_upload_id' => FileUpload::factory()->create()->id, 'accreditation_type' => 'National Certificate',
        'status' => RequirementStatus::Verified, 'verified_at' => now()]);
    expect(app(TechnicalOfficialEligibilityChecker::class)->evaluate($official, $meetSport->meet->fresh(), $sport)->result()->value)->toBe('eligible');
});

test('missing official accreditation is pending while expired accreditation is ineligible', function () {
    $official = User::factory()->technicalOfficial()->create();
    $sport = Sport::factory()->create();
    $meetSport = MeetSport::factory()->create(['sport_id' => $sport->id]);
    $meetSport->meet->forceFill(['medical_clearance_required' => false])->save();
    MeetSportAssignment::factory()->create(['meet_sport_id' => $meetSport->id, 'user_id' => $official->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial, 'status' => MeetSportAssignmentStatus::Active]);
    $checker = app(TechnicalOfficialEligibilityChecker::class);
    expect($checker->evaluate($official, $meetSport->meet->fresh(), $sport)->result()->value)->toBe('pending_requirements');
    TechnicalOfficialAccreditation::query()->create(['user_id' => $official->id, 'sport_id' => $sport->id,
        'file_upload_id' => FileUpload::factory()->create()->id, 'accreditation_type' => 'Certificate',
        'status' => RequirementStatus::Verified, 'expires_at' => $meetSport->meet->starts_at->copy()->subDay()]);
    expect($checker->evaluate($official, $meetSport->meet->fresh(), $sport)->result()->value)->toBe('ineligible');
});
