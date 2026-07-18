<?php

namespace App\Enums;

enum PersonnelRole: string
{
    case Coach = 'coach';
    case AssistantCoach = 'assistant_coach';
    case Chaperone = 'chaperone';

    public function label(): string
    {
        return match ($this) {
            self::Coach => 'Coach',
            self::AssistantCoach => 'Assistant Coach',
            self::Chaperone => 'Chaperone',
        };
    }

    /**
     * Only coaching roles carry sport assignments.
     */
    public function coaches(): bool
    {
        return $this === self::Coach || $this === self::AssistantCoach;
    }
}
