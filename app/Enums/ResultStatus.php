<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Encoded = 'encoded';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Validated = 'validated';
    case Official = 'official';
    case Reopened = 'reopened';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Encoded => 'Encoded',
            self::Submitted => 'For Event Secretariat Validation',
            self::Returned => 'Returned',
            self::Validated => 'Validated',
            self::Official => 'Official',
            self::Reopened => 'Reopened',
            self::Cancelled => 'Cancelled',
        };
    }
}
