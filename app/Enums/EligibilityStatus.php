<?php

namespace App\Enums;

enum EligibilityStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Returned = 'returned';

    /**
     * Terminal, unlike Returned — a rejected review is not automatically
     * reopened by a fresh document upload (see
     * `EligibilityController::storeDocument()`). Added WP-REALIGN-06.
     */
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Approved => 'Approved',
            self::Returned => 'Returned',
            self::Rejected => 'Rejected',
        };
    }
}
