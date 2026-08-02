<?php

namespace App\Models;

use Database\Factories\EquipmentTransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A stock line moved between venues within a meet. `from_venue_id` is
 * nullable (the item may be moving out of unassigned general storage).
 * A full-quantity transfer just moves the item's own `venue_id`; a
 * partial-quantity transfer splits the stock line into a second
 * `EquipmentItem` row at `to_venue_id` — enforced at the controller
 * level, not here. See docs/equipment-management.md.
 *
 * @property int $id
 * @property int $equipment_item_id
 * @property int|null $from_venue_id
 * @property int $to_venue_id
 * @property int $quantity
 * @property int $transferred_by_user_id
 * @property string|null $reason
 * @property Carbon $transferred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['equipment_item_id', 'from_venue_id', 'to_venue_id', 'quantity', 'transferred_by_user_id', 'reason', 'transferred_at'])]
class EquipmentTransfer extends Model
{
    /** @use HasFactory<EquipmentTransferFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EquipmentItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(EquipmentItem::class, 'equipment_item_id');
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function fromVenue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'from_venue_id');
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function toVenue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'to_venue_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by_user_id');
    }
}
