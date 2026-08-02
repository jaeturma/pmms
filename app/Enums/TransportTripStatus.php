<?php

namespace App\Enums;

/**
 * BC-24's own dispatch/boarding/arrival/delay vocabulary. See
 * docs/food-billeting-transport.md.
 */
enum TransportTripStatus: string
{
    case Dispatched = 'dispatched';
    case Boarding = 'boarding';
    case EnRoute = 'en_route';
    case Arrived = 'arrived';
    case Delayed = 'delayed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Dispatched => 'Dispatched',
            self::Boarding => 'Boarding',
            self::EnRoute => 'En Route',
            self::Arrived => 'Arrived',
            self::Delayed => 'Delayed',
            self::Cancelled => 'Cancelled',
        };
    }
}
