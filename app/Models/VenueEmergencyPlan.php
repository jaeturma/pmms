<?php

namespace App\Models;

use Database\Factories\VenueEmergencyPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Reuses the existing, division-wide competition `Venue` catalog
 * unmodified — unlike `BilletingVenue`, this genuinely describes the
 * competition venue itself, not a disjoint concept. See
 * docs/medical-drrm.md.
 *
 * @property int $id
 * @property int $venue_id
 * @property int $meet_id
 * @property string $plan_detail
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['venue_id', 'meet_id', 'plan_detail'])]
class VenueEmergencyPlan extends Model
{
    /** @use HasFactory<VenueEmergencyPlanFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }
}
