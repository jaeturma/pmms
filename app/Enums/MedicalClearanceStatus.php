<?php

namespace App\Enums;

/**
 * The approved organizational model's own "safe aggregate statuses" —
 * the only Medical detail allowed to surface outside the Medical Team.
 * See docs/medical-drrm.md.
 */
enum MedicalClearanceStatus: string
{
    case Cleared = 'cleared';
    case Pending = 'pending';
    case ForEvaluation = 'for_evaluation';
    case Restricted = 'restricted';
    case Referred = 'referred';
    case NotCleared = 'not_cleared';

    public function label(): string
    {
        return match ($this) {
            self::Cleared => 'Cleared',
            self::Pending => 'Pending',
            self::ForEvaluation => 'For Evaluation',
            self::Restricted => 'Restricted',
            self::Referred => 'Referred',
            self::NotCleared => 'Not Cleared',
        };
    }
}
