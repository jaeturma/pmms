<?php

namespace App\Models;

use App\Enums\EquipmentCondition;
use Database\Factories\EquipmentReturnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A (possibly partial) return of equipment against an
 * `EquipmentIssue` — only ever created for issues whose category is not
 * consumable, enforced at the controller level. Append-only, like every
 * other transactional record in this domain. See
 * docs/equipment-management.md.
 *
 * @property int $id
 * @property int $equipment_issue_id
 * @property int $quantity
 * @property EquipmentCondition|null $condition_on_return
 * @property int $received_by_user_id
 * @property string|null $notes
 * @property Carbon $returned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['equipment_issue_id', 'quantity', 'condition_on_return', 'received_by_user_id', 'notes', 'returned_at'])]
class EquipmentReturn extends Model
{
    /** @use HasFactory<EquipmentReturnFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition_on_return' => EquipmentCondition::class,
            'returned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EquipmentIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(EquipmentIssue::class, 'equipment_issue_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }
}
