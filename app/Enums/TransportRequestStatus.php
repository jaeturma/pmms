<?php

namespace App\Enums;

/**
 * See docs/food-billeting-transport.md. Flips to Fulfilled automatically
 * when a `TransportTrip` is created against this request
 * (`TransportTripController::store()`).
 */
enum TransportRequestStatus: string
{
    case Pending = 'pending';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Fulfilled => 'Fulfilled',
            self::Cancelled => 'Cancelled',
        };
    }
}
