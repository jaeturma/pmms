<?php

namespace App\Enums;

/**
 * Why an `EquipmentItem.quantity` changed outside the normal
 * issue/return/transfer flow. See docs/equipment-management.md.
 */
enum InventoryAdjustmentType: string
{
    case Damage = 'damage';
    case Loss = 'loss';
    case Recount = 'recount';
    case Found = 'found';

    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Damage',
            self::Loss => 'Loss',
            self::Recount => 'Recount',
            self::Found => 'Found',
        };
    }
}
