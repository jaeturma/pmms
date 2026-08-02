<?php

namespace App\Models;

use App\Enums\TransportRequestStatus;
use Database\Factories\TransportRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A delegation's ride request — the one manage action a DelegationOfficer
 * gets on this domain (filing a request for their own delegation), per
 * `TransportPolicy`. Flips to Fulfilled when a `TransportTrip` is
 * created against it. See docs/food-billeting-transport.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property int $delegation_id
 * @property string $pickup_location
 * @property string $dropoff_location
 * @property Carbon $requested_at
 * @property int|null $passenger_count
 * @property string|null $notes
 * @property TransportRequestStatus $status
 * @property int $requested_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'delegation_id', 'pickup_location', 'dropoff_location', 'requested_at', 'passenger_count', 'notes', 'status', 'requested_by_user_id'])]
class TransportRequest extends Model
{
    /** @use HasFactory<TransportRequestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'status' => TransportRequestStatus::class,
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
     * @return BelongsTo<Delegation, $this>
     */
    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * @return HasOne<TransportTrip, $this>
     */
    public function fulfillingTrip(): HasOne
    {
        return $this->hasOne(TransportTrip::class);
    }
}
