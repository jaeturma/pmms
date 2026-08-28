<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityResult;
use App\Enums\EligibilityStatus;
use App\Enums\RequirementStatus;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Meet;
use App\Models\SportCategory;

class AthleteEligibilityChecker
{
    public function evaluate(Athlete $athlete, Meet $meet, ?Event $event = null, ?SportCategory $category = null): EligibilityEvaluation
    {
        $athlete->loadMissing(['school', 'delegation', 'eligibilityDocuments', 'eligibilityReview', 'medicalClearance']);
        $category ??= $event?->sportCategory;
        $rules = [];
        $add = function (string $rule, string $authority, string $description, string $expected, string $actual, string $status, ?string $remarks = null) use (&$rules): void {
            $rules[] = compact('rule', 'authority', 'description', 'expected', 'actual', 'status', 'remarks');
        };

        $cutoff = $meet->eligibility_cutoff_date ?? $meet->starts_at;
        $age = $athlete->birthdate->diffInYears($cutoff);
        $ageOk = $category === null || (($category->min_age === null || $age >= $category->min_age) && ($category->max_age === null || $age <= $category->max_age));
        $add('Age Requirement', 'System / DSAC', 'Athlete must meet the category age range.', $this->range($category?->min_age, $category?->max_age), "$age years", $ageOk ? 'passed' : 'failed');

        $gradeOk = $category === null || (($category->min_grade === null || $athlete->grade_level >= $category->min_grade) && ($category->max_grade === null || $athlete->grade_level <= $category->max_grade));
        $add('Grade Level', 'DSAC', 'Athlete must meet the category grade range.', $this->range($category?->min_grade, $category?->max_grade), 'Grade '.$athlete->grade_level, $gradeOk ? 'passed' : 'failed');

        $delegationOk = $athlete->delegation->meet_id === $meet->id && ($athlete->delegation->district_id === null || $athlete->school?->district_id === $athlete->delegation->district_id);
        $add('Municipality / Delegation', 'DSAC', 'School must belong to the registered meet delegation.', 'Matching delegation', $delegationOk ? 'Matched' : 'Mismatch', $delegationOk ? 'passed' : 'failed');

        foreach ([EligibilityDocumentType::SchoolId, EligibilityDocumentType::BirthCertificate, EligibilityDocumentType::MedicalCertificate, EligibilityDocumentType::ParentalConsent] as $type) {
            $document = $athlete->eligibilityDocuments->sortByDesc('id')->firstWhere('document_type', $type);
            $status = $document?->status;
            $ruleStatus = match ($status) {
                RequirementStatus::Verified => 'passed', RequirementStatus::Rejected, RequirementStatus::Expired => 'failed', default => 'pending',
            };
            if ($type === EligibilityDocumentType::MedicalCertificate && $document !== null) {
                $ruleStatus = 'passed';
            }
            if ($type === EligibilityDocumentType::SchoolId && $status === RequirementStatus::Verified && $document->school_id !== null && $document->school_id !== $athlete->school_id) {
                $ruleStatus = 'failed';
            }
            $add(
                $type->label(),
                'DSAC',
                $type === EligibilityDocumentType::MedicalCertificate ? 'Medical Certificate must be attached for DSAC qualification.' : 'Required document must be submitted and verified.',
                $type === EligibilityDocumentType::MedicalCertificate ? 'Attached' : 'Verified',
                $status?->value ?? 'Missing',
                $ruleStatus,
                $document?->remarks,
            );
        }

        $medical = null;

        $entryCount = $athlete->entries()->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))->count();
        $limit = $meet->max_events_per_athlete;
        $add('Event Entry Limit', 'System', 'Athlete must remain within the configured event limit.', $limit === null ? 'No configured limit' : "Maximum $limit", "$entryCount events", $limit === null || $entryCount <= $limit ? 'passed' : 'failed');

        $sexOk = $category === null || $category->sex?->value === 'mixed' || ($category->sex?->value === 'boys' && $athlete->sex->value === 'male') || ($category->sex?->value === 'girls' && $athlete->sex->value === 'female');
        $levelOk = $category === null || $category->level === null || $category->level === $athlete->ageDivision();
        $add('Sport Category', 'System / DSAC', 'Sex and level must match the selected category.', $category?->display_name ?? 'Any', $athlete->sex->value.' / '.$athlete->ageDivision()->value, $sexOk && $levelOk ? 'passed' : 'failed');

        $review = $athlete->eligibilityReview;
        $add('DSAC Validation', 'DSAC', 'Athlete profile and registration must be approved by DSAC.', 'Approved', $review?->status->value ?? 'Missing', match ($review?->status) {
            EligibilityStatus::Approved => 'passed', EligibilityStatus::Rejected => 'failed', default => 'pending',
        });

        $result = match (true) {
            $review?->status === EligibilityStatus::Returned => EligibilityResult::ReturnedByDsac,
            collect($rules)->contains('status', 'failed') => EligibilityResult::Ineligible,
            collect($rules)->contains(fn ($rule) => $rule['status'] === 'pending' && in_array($rule['rule'], ['School ID', 'PSA Birth Certificate', 'Medical Certificate', 'Parental Consent'], true)) => EligibilityResult::PendingRequirements,
            $review === null || $review->status === EligibilityStatus::Pending => EligibilityResult::PendingDsac,
            default => EligibilityResult::Eligible,
        };

        return new EligibilityEvaluation($rules, $result);
    }

    private function range(?int $minimum, ?int $maximum): string
    {
        return $minimum === null && $maximum === null ? 'No configured restriction' : ($minimum ?? 'Any').'–'.($maximum ?? 'Any');
    }
}
