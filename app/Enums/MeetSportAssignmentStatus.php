<?php

namespace App\Enums;

/**
 * An assignment's own lifecycle, independent of the meet's or the
 * meet-sport's status. `Pending` covers the mandate's "acceptance" step
 * (§10, §13's `bounded-context-catalog.md` BC-13 responsibility list) —
 * a person assigned but not yet confirmed; `Declined` lets that step
 * fail explicitly rather than being deleted with no record.
 */
enum MeetSportAssignmentStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Declined = 'declined';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Declined => 'Declined',
            self::Ended => 'Ended',
        };
    }
}
