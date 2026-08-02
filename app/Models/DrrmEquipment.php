<?php

namespace App\Models;

use Database\Factories\DrrmEquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A flat inventory list — deliberately not Supply's issue/return/
 * transfer machinery, since nothing in this WP's scope asked for
 * tracking DRRM equipment custody the way Supply tracks issued sports
 * equipment. See docs/medical-drrm.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property string $name
 * @property int $quantity
 * @property int|null $venue_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'name', 'quantity', 'venue_id', 'notes'])]
class DrrmEquipment extends Model
{
    /** @use HasFactory<DrrmEquipmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
