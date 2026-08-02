<?php

namespace App\Enums;

/**
 * A single status per (meet, delegation) assignment — deliberately not a
 * check-in/out event log. See docs/food-billeting-transport.md.
 */
enum BilletingAssignmentStatus: string
{
    case Assigned = 'assigned';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::CheckedIn => 'Checked In',
            self::CheckedOut => 'Checked Out',
        };
    }
}
