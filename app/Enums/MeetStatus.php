<?php

namespace App\Enums;

enum MeetStatus: string
{
    case Draft = 'draft';
    case RegistrationOpen = 'registration_open';
    case RegistrationClosed = 'registration_closed';
    case Active = 'active';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::RegistrationOpen => 'Registration Open',
            self::RegistrationClosed => 'Registration Closed',
            self::Active => 'Active',
            self::Completed => 'Completed',
        };
    }

    /**
     * The label for the action that moves a meet INTO this status.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Draft => 'Return to draft',
            self::RegistrationOpen => 'Open registration',
            self::RegistrationClosed => 'Close registration',
            self::Active => 'Start meet',
            self::Completed => 'Complete meet',
        };
    }

    /**
     * Statuses this status may move to.
     *
     * Linear lifecycle with one pragmatic exception: closed registration may
     * reopen (deadline extensions are routine at an SDO).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::RegistrationOpen],
            self::RegistrationOpen => [self::RegistrationClosed],
            self::RegistrationClosed => [self::RegistrationOpen, self::Active],
            self::Active => [self::Completed],
            self::Completed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
