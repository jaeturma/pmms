<?php

namespace App\Enums;

/**
 * The permanent sport-portal routes (`/{sportSlug}`) and the real
 * `Sport.name` each resolves to — confirmed against
 * `SportsCatalogSeeder` before writing this, not assumed. Constrains the
 * `{sportSlug}` route parameter via `whereIn(self::values())` so it can
 * never intercept any other top-level route.
 *
 * Extended (WP: public sports directory & mini portals) from the
 * original Phase 12 set of 12 to the full 28-sport catalog, per explicit
 * owner decision — every sport `SportsCatalogSeeder` seeds now has a
 * working public route, not just the 12 with an existing rich live-
 * scoreboard integration. The two Paragames entries get their own slugs
 * (`paragames-athletics`/`paragames-swimming`) rather than colliding with
 * `athletics`/`swimming` — they are genuinely distinct `Sport` rows (see
 * `docs/reports/public-sports-and-mini-portals-review.md` §2).
 */
enum SportPortalSlug: string
{
    case Athletics = 'athletics';
    case Archery = 'archery';
    case Arnis = 'arnis';
    case Badminton = 'badminton';
    case Baseball = 'baseball';
    case Basketball = 'basketball';
    case Billiard = 'billiard';
    case Bocce = 'bocce';
    case Boxing = 'boxing';
    case Chess = 'chess';
    case Dancesports = 'dancesports';
    case Football = 'football';
    case Futsal = 'futsal';
    case GoalBall = 'goal-ball';
    case Gymnastics = 'gymnastics';
    case PencakSilat = 'pencak-silat';
    case Swimming = 'swimming';
    case Weightlifting = 'weightlifting';
    case SepakTakraw = 'sepak-takraw';
    case Softball = 'softball';
    case Taekwondo = 'taekwondo';
    case TableTennis = 'table-tennis';
    case Tennis = 'tennis';
    case Volleyball = 'volleyball';
    case Wrestling = 'wrestling';
    case Wushu = 'wushu';
    case ParagamesAthletics = 'paragames-athletics';
    case ParagamesSwimming = 'paragames-swimming';

    /**
     * The real Sport catalog name this slug resolves to.
     */
    public function sportName(): string
    {
        return match ($this) {
            self::Athletics => 'Athletics',
            self::Archery => 'Archery',
            self::Arnis => 'Arnis',
            self::Badminton => 'Badminton',
            self::Baseball => 'Baseball',
            self::Basketball => 'Basketball',
            self::Billiard => 'Billiard',
            self::Bocce => 'Bocce',
            self::Boxing => 'Boxing',
            self::Chess => 'Chess',
            self::Dancesports => 'Dancesports',
            self::Football => 'Football',
            self::Futsal => 'Futsal',
            self::GoalBall => 'Goal Ball',
            self::Gymnastics => 'Gymnastics',
            self::PencakSilat => 'Pencak Silat',
            self::Swimming => 'Swimming',
            self::Weightlifting => 'Weightlifting',
            self::SepakTakraw => 'Sepak Takraw',
            self::Softball => 'Softball',
            self::Taekwondo => 'Taekwondo',
            self::TableTennis => 'Table Tennis',
            self::Tennis => 'Tennis',
            self::Volleyball => 'Volleyball',
            self::Wrestling => 'Wrestling',
            self::Wushu => 'Wushu',
            self::ParagamesAthletics => 'Paragames - Athletics',
            self::ParagamesSwimming => 'Paragames - Swimming',
        };
    }

    /**
     * The matching slug for a real `Sport.name`, if one of the routed
     * sports — `null` for anything outside the catalog this enum covers
     * (there shouldn't be any, since it now spans the full
     * `SportsCatalogSeeder` list, but this stays honest rather than
     * assuming every `Sport` row is one of these).
     */
    public static function fromSportName(string $sportName): ?self
    {
        foreach (self::cases() as $slug) {
            if ($slug->sportName() === $sportName) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
