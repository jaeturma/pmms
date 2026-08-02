<?php

namespace App\Enums;

/**
 * Shared between `EquipmentItem.condition` (current assessed condition)
 * and `EquipmentReturn.condition_on_return` (condition observed when
 * equipment comes back), per WP-REALIGN-10 (docs/equipment-management.md).
 */
enum EquipmentCondition: string
{
    case Good = 'good';
    case Fair = 'fair';
    case Damaged = 'damaged';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::Fair => 'Fair',
            self::Damaged => 'Damaged',
            self::Lost => 'Lost',
        };
    }
}
