<?php

namespace App\Models;

use App\Enums\BilletingAssignmentStatus;
use Database\Factories\BilletingAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row per (meet, delegation) — a single status
 * (Assigned/CheckedIn/CheckedOut), deliberately not a check-in/out event
 * log. `room_detail`/`contact_name` are restricted to the Billeting Team
 * and this assignment's own delegation officer — see
 * `BilletingPolicy`. See docs/food-billeting-transport.md.
 *
 * @property int $id
 * @property int $billeting_venue_id
 * @property int $delegation_id
 * @property int $meet_id
 * @property string|null $room_detail
 * @property string|null $contact_name
 * @property BilletingAssignmentStatus $status
 * @property Carbon $assigned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['billeting_venue_id', 'delegation_id', 'meet_id', 'room_detail', 'contact_name', 'status', 'assigned_at'])]
class BilletingAssignment extends Model
{
    /** @use HasFactory<BilletingAssignmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BilletingAssignmentStatus::class,
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BilletingVenue, $this>
     */
    public function billetingVenue(): BelongsTo
    {
        return $this->belongsTo(BilletingVenue::class);
    }

    /**
     * @return BelongsTo<Delegation, $this>
     */
    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }
}
