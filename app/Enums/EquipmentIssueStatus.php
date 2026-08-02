<?php

namespace App\Enums;

/**
 * Only meaningful for issues against a non-consumable
 * (`EquipmentCategory.is_consumable = false`) category — a consumable
 * issue stays `Issued` forever, since no `EquipmentReturn` is ever
 * possible against it. See docs/equipment-management.md.
 */
enum EquipmentIssueStatus: string
{
    case Issued = 'issued';
    case PartiallyReturned = 'partially_returned';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Issued',
            self::PartiallyReturned => 'Partially Returned',
            self::Returned => 'Returned',
        };
    }
}
