<?php

namespace App\Models;

use App\Enums\InventoryAdjustmentType;
use Database\Factories\InventoryAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A correction to `EquipmentItem.quantity` itself outside the normal
 * issue/return/transfer flow (damage, loss, recount, found) — unlike
 * issues/returns, which only affect the item's *derived* available
 * quantity, applying an adjustment updates `quantity` directly. `reason`
 * is required, mirroring `ResultController::correct()`'s
 * reason-required-on-correction precedent. See
 * docs/equipment-management.md.
 *
 * @property int $id
 * @property int $equipment_item_id
 * @property InventoryAdjustmentType $type
 * @property int $quantity_delta
 * @property string $reason
 * @property int $adjusted_by_user_id
 * @property Carbon $adjusted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['equipment_item_id', 'type', 'quantity_delta', 'reason', 'adjusted_by_user_id', 'adjusted_at'])]
class InventoryAdjustment extends Model
{
    /** @use HasFactory<InventoryAdjustmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InventoryAdjustmentType::class,
            'adjusted_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by_user_id');
    }
}
