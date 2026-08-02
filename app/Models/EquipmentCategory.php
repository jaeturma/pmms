<?php

namespace App\Models;

use Database\Factories\EquipmentCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A meet's Supply Team equipment catalog, defined fresh per meet (no
 * division-wide durable-goods catalog in this WP). See
 * docs/equipment-management.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_consumable
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'name', 'description', 'is_consumable'])]
class EquipmentCategory extends Model
{
    /** @use HasFactory<EquipmentCategoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_consumable' => 'boolean',
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
     * @return HasMany<EquipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EquipmentItem::class);
    }
}
