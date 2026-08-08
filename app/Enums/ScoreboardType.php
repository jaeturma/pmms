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
    case VolleyballSepakTakraw = 'volleyball_sepak_takraw';
    case FootballFutsal = 'football_futsal';
    case RacketGames = 'racket_games';
    case CombatRounds = 'combat_rounds';
    case Wrestling = 'wrestling';
    case Tennis = 'tennis';
    case GoalBall = 'goal_ball';
    case Billiard = 'billiard';
    case Bocce = 'bocce';

    public static function forSport(?string $sportName): self
    {
        return match (mb_strtolower((string) $sportName)) {
            'basketball' => self::Basketball,
            'boxing' => self::Boxing,
            'softball', 'baseball' => self::SoftballBaseball,
            'volleyball', 'sepak takraw' => self::VolleyballSepakTakraw,
            'football', 'futsal' => self::FootballFutsal,
            'table tennis', 'badminton' => self::RacketGames,
            'taekwondo', 'wushu', 'pencak silat', 'arnis' => self::CombatRounds,
            'wrestling' => self::Wrestling,
            'tennis' => self::Tennis,
            'goal ball' => self::GoalBall,
            'billiard' => self::Billiard,
            'bocce' => self::Bocce,
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
            self::VolleyballSepakTakraw => 'Volleyball / Sepak Takraw',
            self::FootballFutsal => 'Football / Futsal',
            self::RacketGames => 'Table Tennis / Badminton',
            self::CombatRounds => 'Taekwondo / Wushu / Pencak Silat / Arnis',
            self::Wrestling => 'Wrestling',
            self::Tennis => 'Tennis',
            self::GoalBall => 'Goal Ball',
            self::Billiard => 'Billiard',
            self::Bocce => 'Bocce',
        };
    }
}
