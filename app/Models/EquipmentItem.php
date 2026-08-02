<?php

namespace App\Models;

use App\Enums\EquipmentCondition;
use Database\Factories\EquipmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A quantity-based stock line ("Basketballs — qty 20"), not one row per
 * physical unit — no serial/asset-tag tracking in this WP. `venue_id` is
 * the item's *current* location, moved by `EquipmentTransfer` (the
 * transfer row is the audit trail, this field is the current-state
 * pointer). See docs/equipment-management.md.
 *
 * @property int $id
 * @property int $equipment_category_id
 * @property int|null $venue_id
 * @property int $quantity
 * @property EquipmentCondition|null $condition
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['equipment_category_id', 'venue_id', 'quantity', 'condition', 'notes'])]
class EquipmentItem extends Model
{
    /** @use HasFactory<EquipmentItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition' => EquipmentCondition::class,
        ];
    }

    /**
     * @return BelongsTo<EquipmentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'equipment_category_id');
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return HasMany<EquipmentIssue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(EquipmentIssue::class);
    }

    /**
     * @return HasMany<EquipmentTransfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(EquipmentTransfer::class);
    }

    /**
     * @return HasMany<InventoryAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    /**
     * Derived at read time, never stored — `quantity` minus every
     * issue's own outstanding quantity (see
     * `EquipmentIssue::outstandingQuantity()`). Covers consumable and
     * returnable categories with the same formula: a consumable issue
     * never accumulates returns, so it stays permanently deducted.
     */
    public function availableQuantity(): int
    {
        return $this->quantity - $this->issues
            ->sum(fn (EquipmentIssue $issue): int => $issue->outstandingQuantity());
    }
}
