<?php

namespace App\Enums;

/**
 * The 12 permanent sport-portal routes (Phase 12, `/{sportSlug}`) and
 * the real `Sport.name` each resolves to — confirmed against
 * `SportsCatalogSeeder`/`DdopaaReferenceSeeder` before writing this,
 * not assumed. Constrains the `{sportSlug}` route parameter via
 * `whereIn(self::values())` so it can never intercept any other
 * top-level route.
 */
enum SportPortalSlug: string
{
    case Basketball = 'basketball';
    case Volleyball = 'volleyball';
    case Baseball = 'baseball';
    case Softball = 'softball';
    case Football = 'football';
    case SepakTakraw = 'sepak-takraw';
    case Badminton = 'badminton';
    case TableTennis = 'table-tennis';
    case Chess = 'chess';
    case Boxing = 'boxing';
    case Athletics = 'athletics';
    case Swimming = 'swimming';

    /**
     * The real Sport catalog name this slug resolves to.
     */
    public function sportName(): string
    {
        return match ($this) {
            self::Basketball => 'Basketball',
            self::Volleyball => 'Volleyball',
            self::Baseball => 'Baseball',
            self::Softball => 'Softball',
            self::Football => 'Football',
            self::SepakTakraw => 'Sepak Takraw',
            self::Badminton => 'Badminton',
            self::TableTennis => 'Table Tennis',
            self::Chess => 'Chess',
            self::Boxing => 'Boxing',
            self::Athletics => 'Athletics',
            self::Swimming => 'Swimming',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
