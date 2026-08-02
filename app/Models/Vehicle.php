<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A meet's Transport Team vehicle roster, defined fresh per meet — not a
 * division-wide fleet catalog, same catalog-scope convention
 * WP-REALIGN-10 established for equipment. See
 * docs/food-billeting-transport.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property string $plate_number
 * @property string|null $type
 * @property int|null $capacity
 * @property string|null $driver_name
 * @property string|null $driver_phone
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'plate_number', 'type', 'capacity', 'driver_name', 'driver_phone', 'notes'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    /**
     * @return HasMany<TransportTrip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(TransportTrip::class);
    }
}
