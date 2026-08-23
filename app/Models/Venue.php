<?php

namespace App\Models;

use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $notes
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'source_code', 'source_system', 'name', 'short_name', 'address', 'municipality_id',
    'latitude', 'longitude', 'public_notes', 'internal_notes', 'readiness_status',
    'source_venue_text', 'source_notes', 'notes',
])]
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<EventSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class);
    }

    public function eventAssignments(): HasMany
    {
        return $this->hasMany(EventVenue::class);
    }

    public function competitionAreas(): HasMany
    {
        return $this->hasMany(CompetitionArea::class)->orderBy('display_order');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(District::class, 'municipality_id');
    }

    public function meetSportAssignments(): HasMany
    {
        return $this->hasMany(MeetSportVenue::class);
    }

    public function gameCoordinatorAssignments(): HasMany
    {
        return $this->hasMany(GameCoordinatorAssignment::class);
    }

    /**
     * @return HasMany<VenueEmergencyPlan, $this>
     */
    public function emergencyPlans(): HasMany
    {
        return $this->hasMany(VenueEmergencyPlan::class);
    }

    /**
     * @return HasMany<EvacuationRoute, $this>
     */
    public function evacuationRoutes(): HasMany
    {
        return $this->hasMany(EvacuationRoute::class);
    }

    /**
     * Whether any record references this venue and blocks deletion —
     * includes `VenueEmergencyPlan`/`EvacuationRoute` (both cascade-
     * delete on this venue, unlike Equipment/Billeting/Vehicle's
     * `nullOnDelete` references) so deleting a venue never silently
     * destroys DRRM planning data.
     */
    public function isInUse(): bool
    {
        return $this->schedules()->exists()
            || $this->eventAssignments()->exists()
            || $this->meetSportAssignments()->exists()
            || $this->gameCoordinatorAssignments()->exists()
            || $this->emergencyPlans()->exists()
            || $this->evacuationRoutes()->exists();
    }
}
