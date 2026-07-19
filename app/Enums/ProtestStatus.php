<?php

namespace App\Enums;

enum ProtestStatus: string
{
    case Filed = 'filed';
    case UnderReview = 'under_review';
    case Upheld = 'upheld';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Filed => 'Filed',
            self::UnderReview => 'Under Review',
            self::Upheld => 'Upheld',
            self::Dismissed => 'Dismissed',
        };
    }

    /**
     * filed → under_review → upheld | dismissed; decisions are terminal.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Filed => [self::UnderReview],
            self::UnderReview => [self::Upheld, self::Dismissed],
            self::Upheld, self::Dismissed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
