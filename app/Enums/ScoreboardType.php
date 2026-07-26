<?php

namespace App\Enums;

/**
 * Which scoreboard UI a live scoring session uses, inferred from the
 * match's sport (App\Models\Sport::name). Falls back to Generic for any
 * sport without a dedicated scoreboard — see docs/live-scoring.md.
 */
enum ScoreboardType: string
{
    case Generic = 'generic';
    case Basketball = 'basketball';
    case Boxing = 'boxing';
    case SoftballBaseball = 'softball_baseball';

    public static function forSport(?string $sportName): self
    {
        return match (mb_strtolower((string) $sportName)) {
            'basketball' => self::Basketball,
            'boxing' => self::Boxing,
            'softball', 'baseball' => self::SoftballBaseball,
            default => self::Generic,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Generic => 'Generic',
            self::Basketball => 'Basketball',
            self::Boxing => 'Boxing',
            self::SoftballBaseball => 'Softball / Baseball',
        };
    }
}
