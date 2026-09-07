<?php

namespace App\Enums;

/**
 * The "Designation" column of the DAVRAA List of Recommended Qualifiers.
 * `defaultOrder()` gives the template's default presentation sequence
 * (ATHLETES, then COACH, ASST. COACH, CHAPERONE) — ICT may still reorder
 * rows manually before generating.
 */
enum DavraaReportDesignation: string
{
    case Athlete = 'athlete';
    case Coach = 'coach';
    case AssistantCoach = 'assistant_coach';
    case Chaperone = 'chaperone';

    public function label(): string
    {
        return match ($this) {
            self::Athlete => 'ATHLETE',
            self::Coach => 'COACH',
            self::AssistantCoach => 'ASST. COACH',
            self::Chaperone => 'CHAPERONE',
        };
    }

    public function defaultOrder(): int
    {
        return match ($this) {
            self::Athlete => 0,
            self::Coach => 1,
            self::AssistantCoach => 2,
            self::Chaperone => 3,
        };
    }

    /** A person record (Athlete) vs. an account/personnel row (User). */
    public function isAthlete(): bool
    {
        return $this === self::Athlete;
    }
}
