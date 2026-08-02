<?php

namespace App\Enums;

/**
 * See docs/medical-drrm.md.
 */
enum EmergencyIncidentStatus: string
{
    case Reported = 'reported';
    case Responding = 'responding';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported',
            self::Responding => 'Responding',
            self::Resolved => 'Resolved',
        };
    }
}
