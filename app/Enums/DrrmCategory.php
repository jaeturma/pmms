<?php

namespace App\Enums;

/**
 * The three emergency categories WP-REALIGN-12's DRRM scope covers, per
 * the owner's own resolved decision. See docs/medical-drrm.md.
 */
enum DrrmCategory: string
{
    case Weather = 'weather';
    case Medical = 'medical';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Weather => 'Weather',
            self::Medical => 'Medical',
            self::Security => 'Security',
        };
    }
}
