<?php

namespace App\Enums;

/**
 * Mirrors MeetSportAssignmentStatus's shape deliberately — same
 * "assigned but not yet confirmed" acceptance step the mandate's own
 * BC-13/BC-05 responsibility lists describe ("acceptance").
 */
enum ManagementTeamMemberStatus: string
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
