<?php

namespace App\Models;

use App\Enums\TransportTripStatus;
use Database\Factories\TransportTripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * BC-24's sole authoritative aggregate for Transport. `transport_request_id`
 * is nullable both ways — a trip need not fulfill any request (e.g. an
 * officials' shuttle), and a trip's history survives if its request row
 * is ever removed. See docs/food-billeting-transport.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property int $vehicle_id
 * @property int|null $delegation_id
 * @property int|null $transport_request_id
 * @property string $pickup_location
 * @property string $dropoff_location
 * @property TransportTripStatus $status
 * @property Carbon $scheduled_at
 * @property Carbon|null $departed_at
 * @property Carbon|null $arrived_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'vehicle_id', 'delegation_id', 'transport_request_id', 'pickup_location', 'dropoff_location', 'status', 'scheduled_at', 'departed_at', 'arrived_at', 'notes'])]
class TransportTrip extends Model
{
    /** @use HasFactory<TransportTripFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TransportTripStatus::class,
            'scheduled_at' => 'datetime',
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Delegation, $this>
     */
    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /**
     * @return BelongsTo<TransportRequest, $this>
     */
    public function transportRequest(): BelongsTo
    {
        return $this->belongsTo(TransportRequest::class);
    }
}
