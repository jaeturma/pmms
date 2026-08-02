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
    case Restricted = 'restricted';
    case Referred = 'referred';

    public function label(): string
    {
        return match ($this) {
            self::Cleared => 'Cleared',
            self::Pending => 'Pending',
            self::Restricted => 'Restricted',
            self::Referred => 'Referred',
        };
    }
}
