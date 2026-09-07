<?php

namespace App\Enums;

/**
 * Lifecycle of a DAVRAA Report Group. A group is never hard-deleted —
 * `Archived` retires it from the working list while keeping the
 * historical roster and its field snapshots intact.
 */
enum DavraaReportStatus: string
{
    case Draft = 'draft';
    case Final = 'final';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Final => 'Finalized',
            self::Archived => 'Archived',
        };
    }
}
