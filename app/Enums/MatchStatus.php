<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Walkover = 'walkover';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Walkover => 'Walkover',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The label for the action that moves a match INTO this status.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Scheduled => 'Reschedule',
            self::Completed => 'Complete',
            self::Walkover => 'Declare walkover',
            self::Cancelled => 'Cancel',
        };
    }

    /**
     * Scheduled is the only mutable state; the other three are terminal.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Scheduled => [self::Completed, self::Walkover, self::Cancelled],
            self::Completed, self::Walkover, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
