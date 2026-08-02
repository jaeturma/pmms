<?php

namespace App\Models;

use App\Enums\EquipmentIssueStatus;
use Database\Factories\EquipmentIssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Equipment issued out of a stock line to a venue for use during the
 * meet. Consumable-category issues stay `Issued` forever (no
 * `EquipmentReturn` is ever possible against them); returnable-category
 * issues progress Issued → PartiallyReturned → Returned as
 * `EquipmentReturn` rows come in. See docs/equipment-management.md.
 *
 * @property int $id
 * @property int $equipment_item_id
 * @property int $venue_id
 * @property int $quantity
 * @property string|null $custodian_name
 * @property int $issued_by_user_id
 * @property string|null $purpose
 * @property EquipmentIssueStatus $status
 * @property Carbon $issued_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['equipment_item_id', 'venue_id', 'quantity', 'custodian_name', 'issued_by_user_id', 'purpose', 'status', 'issued_at'])]
class EquipmentIssue extends Model
{
    /** @use HasFactory<EquipmentIssueFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EquipmentIssueStatus::class,
            'issued_at' => 'datetime',
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
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /**
     * @return HasMany<EquipmentReturn, $this>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(EquipmentReturn::class);
    }

    /**
     * This issue's own quantity minus everything already returned
     * against it — the per-issue term `EquipmentItem::availableQuantity()`
     * sums across every issue on a stock line.
     */
    public function outstandingQuantity(): int
    {
        return $this->quantity - $this->returns->sum('quantity');
    }
}
