<?php

namespace App\Models;

use Database\Factories\BilletingVenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An off-site lodging venue for a meet's delegations — deliberately its
 * own table, not a reuse of `Venue` (the existing division-wide
 * competition-scheduling catalog, whose only relation/purpose is
 * `EventSchedule`). `venue_id` here is purely informational, set only if
 * a billeting site happens to coincide with a competition venue. See
 * docs/food-billeting-transport.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property string $name
 * @property string|null $address
 * @property int|null $capacity
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property int|null $venue_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'name', 'address', 'capacity', 'contact_name', 'contact_phone', 'venue_id', 'notes'])]
class BilletingVenue extends Model
{
    /** @use HasFactory<BilletingVenueFactory> */
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

    /**
     * @return HasMany<BilletingAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(BilletingAssignment::class);
    }
}
