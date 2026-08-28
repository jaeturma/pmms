<?php

use App\Enums\AthleteOversightType;
use App\Enums\EligibilityDocumentType;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MedicalClearanceStatus;
use App\Enums\RequirementStatus;
use App\Models\Athlete;
use App\Models\AthleteOversightAssignment;
use App\Models\EligibilityDocument;
use App\Models\EligibilityReview;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\MedicalClearance;
use App\Models\School;
use App\Models\SchoolDistrict;
use App\Models\User;

function authorityMember(ManagementTeamType $type, int $meetId): User
{
    $team = ManagementTeam::factory()->create(['meet_id' => $meetId, 'team_type' => $type]);

    return ManagementTeamMember::factory()->create(['management_team_id' => $team->id, 'status' => ManagementTeamMemberStatus::Active])->user;
}

test('a DSC sees only athletes in the assigned school district and cannot approve', function () {
    $schoolDistrict = SchoolDistrict::factory()->create();
    $school = School::factory()->create(['district_id' => $schoolDistrict->district_id, 'school_district_id' => $schoolDistrict->id]);
    $athlete = Athlete::factory()->create(['school_id' => $school->id]);
    $other = Athlete::factory()->create();
    $dsc = User::factory()->create();
    AthleteOversightAssignment::query()->create(['user_id' => $dsc->id, 'meet_id' => $athlete->delegation->meet_id,
        'authority_type' => AthleteOversightType::DistrictSportsCoordinator, 'district_id' => $schoolDistrict->district_id,
        'school_district_id' => $schoolDistrict->id, 'active' => true]);

    $this->actingAs($dsc)->get("/athletes/{$athlete->id}")->assertOk();
    $this->actingAs($dsc)->get("/athletes/{$other->id}")->assertForbidden();
    $review = EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $athlete->delegation->meet_id]);
    $this->actingAs($dsc)->patch("/eligibility/reviews/{$review->id}/approve")->assertForbidden();
    $this->actingAs($dsc)->get('/readiness?meet_id='.$athlete->delegation->meet_id)->assertOk();
});

test('a municipality team manager monitors its municipality but cannot approve DSAC or medical decisions', function () {
    $athlete = Athlete::factory()->create();
    $manager = User::factory()->create();
    AthleteOversightAssignment::query()->create(['user_id' => $manager->id, 'meet_id' => $athlete->delegation->meet_id,
        'authority_type' => AthleteOversightType::MunicipalityTeamManager, 'district_id' => $athlete->school->district_id, 'active' => true]);
    $review = EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $athlete->delegation->meet_id]);
    $clearance = MedicalClearance::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $athlete->delegation->meet_id]);
    $this->actingAs($manager)->get("/athletes/{$athlete->id}")->assertOk();
    $this->actingAs($manager)->patch("/eligibility/reviews/{$review->id}/approve")->assertForbidden();
    $this->actingAs($manager)->put("/medical-clearances/{$clearance->id}", ['status' => MedicalClearanceStatus::Cleared->value])->assertForbidden();
});

test('DSAC verifies qualification documents including an attached medical certificate', function () {
    $athlete = Athlete::factory()->create();
    $review = EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $athlete->delegation->meet_id]);
    $dsac = authorityMember(ManagementTeamType::DivisionScreeningAndAccreditation, $review->meet_id);
    $schoolId = EligibilityDocument::factory()->create(['athlete_id' => $athlete->id, 'document_type' => EligibilityDocumentType::SchoolId]);
    $medical = EligibilityDocument::factory()->create(['athlete_id' => $athlete->id, 'document_type' => EligibilityDocumentType::MedicalCertificate]);
    $this->actingAs($dsac)->patch("/eligibility/documents/{$schoolId->id}/status", ['status' => RequirementStatus::Verified->value])->assertRedirect();
    $this->actingAs($dsac)->patch("/eligibility/documents/{$medical->id}/status", ['status' => RequirementStatus::Verified->value])->assertRedirect();
    expect($medical->fresh()->status)->toBe(RequirementStatus::Verified);
});

test('Medical Team does not decide DSAC eligibility documents', function () {
    $athlete = Athlete::factory()->create();
    EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $athlete->delegation->meet_id]);
    $medicalTeam = authorityMember(ManagementTeamType::Medical, $athlete->delegation->meet_id);
    $medical = EligibilityDocument::factory()->create(['athlete_id' => $athlete->id, 'document_type' => EligibilityDocumentType::MedicalCertificate]);
    $psa = EligibilityDocument::factory()->create(['athlete_id' => $athlete->id, 'document_type' => EligibilityDocumentType::BirthCertificate]);
    $this->actingAs($medicalTeam)->patch("/eligibility/documents/{$medical->id}/status", ['status' => RequirementStatus::Verified->value])->assertForbidden();
    $this->actingAs($medicalTeam)->patch("/eligibility/documents/{$psa->id}/status", ['status' => RequirementStatus::Verified->value])->assertForbidden();
});
