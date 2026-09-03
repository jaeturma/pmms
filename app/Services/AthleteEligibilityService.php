<?php

namespace App\Services;

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Models\Athlete;
use App\Models\EligibilityReview;
use App\Models\User;

class AthleteEligibilityService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Mark an athlete eligible as soon as all five required document types
     * have at least one attachment.
     */
    public function markEligibleWhenComplete(Athlete $athlete, ?User $actor = null): bool
    {
        $requiredTypes = collect(EligibilityDocumentType::qualificationRequirements())
            ->map(fn (EligibilityDocumentType $type): string => $type->value);

        $attachedTypes = $athlete->eligibilityDocuments()
            ->whereIn('document_type', $requiredTypes)
            ->distinct()
            ->pluck('document_type')
            ->map(fn (EligibilityDocumentType|string $type): string => $type instanceof EligibilityDocumentType
                ? $type->value
                : $type);

        if ($requiredTypes->diff($attachedTypes)->isNotEmpty()) {
            return false;
        }

        $athlete->loadMissing('delegation');
        $review = EligibilityReview::query()->firstOrCreate([
            'athlete_id' => $athlete->id,
            'meet_id' => $athlete->delegation->meet_id,
        ]);

        if ($review->status === EligibilityStatus::Approved) {
            return false;
        }

        $previousStatus = $review->status->value;
        $review->forceFill([
            'status' => EligibilityStatus::Approved,
            'reviewer_id' => $actor?->getAuthIdentifier(),
            'remarks' => null,
            'decided_at' => now(),
        ])->save();

        $this->audit->record('eligibility.approved', $review, [
            'athlete' => $athlete->fullName(),
            'source' => 'all_required_documents_attached',
            'required_document_count' => $requiredTypes->count(),
            'previous_status' => $previousStatus,
        ], $actor);

        return true;
    }

    /** Record an explicit per-athlete ICT eligibility override. */
    public function markEligibleManually(Athlete $athlete, User $actor): bool
    {
        $athlete->loadMissing('delegation');
        $review = EligibilityReview::query()->firstOrCreate([
            'athlete_id' => $athlete->id,
            'meet_id' => $athlete->delegation->meet_id,
        ]);

        if ($review->status === EligibilityStatus::Approved) {
            return false;
        }

        $previousStatus = $review->status?->value ?? 'none';
        $review->forceFill([
            'status' => EligibilityStatus::Approved,
            'reviewer_id' => $actor->getAuthIdentifier(),
            'remarks' => 'Manually approved by Tournament ICT/Secretary; eligibility may have incomplete document uploads.',
            'decided_at' => now(),
        ])->save();

        $this->audit->record('eligibility.approved', $review, [
            'athlete' => $athlete->fullName(),
            'source' => 'tournament_operations_manual_override',
            'previous_status' => $previousStatus,
        ], $actor);

        return true;
    }
}
