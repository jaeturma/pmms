<?php

namespace App\Services\Eligibility;

use App\Enums\MedicalClearanceStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\RequirementStatus;
use App\Models\Meet;
use App\Models\MeetSportAssignment;
use App\Models\Personnel;
use App\Models\Sport;
use App\Models\TechnicalOfficialAccreditation;
use App\Models\User;

class TechnicalOfficialEligibilityChecker
{
    public function evaluate(User $official, Meet $meet, Sport $sport): EligibilityEvaluation
    {
        $rules = [];
        $add = function (string $rule, string $expected, string $actual, string $status, ?string $remarks = null) use (&$rules): void {
            $description = "Technical official requirement: $rule.";
            $rules[] = compact('rule', 'description', 'expected', 'actual', 'status', 'remarks');
        };

        $assignment = MeetSportAssignment::query()->where('user_id', $official->id)
            ->where('role', MeetSportAssignmentRole::TechnicalOfficial)
            ->whereHas('meetSport', fn ($query) => $query->where('meet_id', $meet->id)->where('sport_id', $sport->id))->first();
        $add('Registration', 'Registered for current meet', $assignment === null ? 'Not registered' : 'Registered', $assignment === null ? 'failed' : 'passed');
        $add('Sport Assignment', $sport->name, $assignment?->meetSport?->sport?->name ?? 'None', $assignment === null ? 'failed' : 'passed');

        $credential = TechnicalOfficialAccreditation::query()->where('user_id', $official->id)->where('sport_id', $sport->id)->latest()->first();
        $add('Accreditation Submitted', 'Submitted', $credential === null ? 'Missing' : 'Submitted', $credential === null ? 'pending' : 'passed');
        $add('Accreditation Verified', 'Verified', $credential?->status->value ?? 'Missing', match ($credential?->status) {
            RequirementStatus::Verified => 'passed', RequirementStatus::Rejected => 'failed', default => 'pending',
        }, $credential?->remarks);
        $expired = $credential?->expires_at?->lt($meet->starts_at) ?? false;
        $add('Accreditation Validity', 'Valid on competition date', $expired ? 'Expired' : ($credential?->expires_at?->toDateString() ?? 'No expiration'), $expired ? 'failed' : ($credential === null ? 'pending' : 'passed'));

        $personnel = Personnel::query()->where('user_id', $official->id)->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))->first();
        if ($meet->medical_clearance_required) {
            $medical = $personnel?->medicalClearance?->status;
            $add('Medical Clearance', 'Cleared', $medical?->value ?? 'Missing', match ($medical) {
                MedicalClearanceStatus::Cleared => 'passed', MedicalClearanceStatus::Restricted, MedicalClearanceStatus::Referred => 'failed', default => 'pending',
            });
        } else {
            $add('Medical Clearance', 'Not required', 'Not applicable', 'not_applicable');
        }

        $assignmentStatus = $assignment?->status;
        $add('Assignment Approval', 'Active', $assignmentStatus?->value ?? 'Missing', match ($assignmentStatus) {
            MeetSportAssignmentStatus::Active => 'passed', MeetSportAssignmentStatus::Declined, MeetSportAssignmentStatus::Ended => 'failed', default => 'pending',
        });

        return new EligibilityEvaluation($rules);
    }
}
