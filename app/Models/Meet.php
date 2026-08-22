<?php

namespace App\Models;

use App\Enums\MeetStatus;
use Database\Factories\MeetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $school_year
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $venue
 * @property MeetStatus $status
 * @property bool $is_published
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $events_count
 */
#[Fillable(['name', 'school_year', 'starts_at', 'ends_at', 'venue', 'eligibility_cutoff_date', 'medical_clearance_required', 'max_events_per_athlete'])]
class Meet extends Model
{
    /** @use HasFactory<MeetFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => MeetStatus::class,
            'is_published' => 'boolean',
            'is_active' => 'boolean',
            'eligibility_cutoff_date' => 'date',
            'medical_clearance_required' => 'boolean',
            'max_events_per_athlete' => 'integer',
        ];
    }

    /**
     * Meets visible on the public portal. Every public route must go
     * through this scope — nothing about an unpublished meet is public.
     *
     * @param  Builder<Meet>  $query
     * @return Builder<Meet>
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * The one meet featured on the public landing page. At most one row
     * should ever match — enforced by MeetController::setActive(), not a
     * database constraint.
     *
     * @param  Builder<Meet>  $query
     * @return Builder<Meet>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'meet_events')->withTimestamps();
    }

    /**
     * This meet's sport inclusions — see `MeetSport`'s own docblock for
     * why this exists alongside (not instead of) `events()` above.
     *
     * @return HasMany<MeetSport, $this>
     */
    public function meetSports(): HasMany
    {
        return $this->hasMany(MeetSport::class);
    }

    /**
     * @return HasMany<Delegation, $this>
     */
    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class);
    }

    /** Canonical sports enabled for this meet. */
    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class, 'meet_sports')
            ->withPivot(['status', 'description', 'venue_notes', 'active', 'notes', 'display_order'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ManagementTeam, $this>
     */
    public function managementTeams(): HasMany
    {
        return $this->hasMany(ManagementTeam::class);
    }

    /**
     * @return HasMany<EquipmentCategory, $this>
     */
    public function equipmentCategories(): HasMany
    {
        return $this->hasMany(EquipmentCategory::class);
    }

    /**
     * @return HasMany<MealSchedule, $this>
     */
    public function mealSchedules(): HasMany
    {
        return $this->hasMany(MealSchedule::class);
    }

    /**
     * @return HasMany<BilletingVenue, $this>
     */
    public function billetingVenues(): HasMany
    {
        return $this->hasMany(BilletingVenue::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasMany<MedicalClearance, $this>
     */
    public function medicalClearances(): HasMany
    {
        return $this->hasMany(MedicalClearance::class);
    }

    /**
     * @return HasMany<DrrmPlan, $this>
     */
    public function drrmPlans(): HasMany
    {
        return $this->hasMany(DrrmPlan::class);
    }

    /**
     * @return HasMany<EmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * @return HasMany<DrrmEquipment, $this>
     */
    public function drrmEquipment(): HasMany
    {
        return $this->hasMany(DrrmEquipment::class);
    }

    /**
     * @return HasMany<ReadinessChecklist, $this>
     */
    public function readinessChecklists(): HasMany
    {
        return $this->hasMany(ReadinessChecklist::class);
    }

    /**
     * @return HasMany<EmergencyIncident, $this>
     */
    public function emergencyIncidents(): HasMany
    {
        return $this->hasMany(EmergencyIncident::class);
    }

    /**
     * Registration-window hook for the delegation and entry modules.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->status === MeetStatus::RegistrationOpen;
    }

    /**
     * The single meet this deployment runs — same singleton idiom as
     * `Division::current()`. This system is dedicated to one meet at a
     * time (rename/redate this row for next year's meet, or when reused
     * for another province, rather than creating a second row); ordered
     * by `id` so the oldest row wins even if stray rows exist.
     *
     * `status` is deliberately left out of the create payload — it's not
     * Fillable (only `MeetController::updateStatus()` may set it), so
     * mass assignment would silently drop it, leaving the in-memory
     * model's `status` null even though the `meets.status` column's own
     * `default('draft')` applies at the database level. `refresh()`
     * after a fresh create pulls that default back in.
     */
    public static function current(): self
    {
        $meet = static::query()->orderBy('id')->firstOrCreate([], [
            'name' => 'Meet',
            'school_year' => now()->format('Y').'-'.now()->addYear()->format('Y'),
            'starts_at' => now(),
            'ends_at' => now()->addDays(4),
        ]);

        return $meet->wasRecentlyCreated ? $meet->refresh() : $meet;
    }
}
