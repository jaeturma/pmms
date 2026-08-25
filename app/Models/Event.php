<?php

namespace App\Models;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sport_id
 * @property int|null $sport_category_id
 * @property string $name
 * @property GenderCategory $gender
 * @property AgeDivision $age_division
 * @property bool $is_team_event
 * @property int $max_entries_per_delegation
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'sport_id',
    'sport_category_id',
    'code',
    'event_no',
    'name',
    'slug',
    'event_type',
    'discipline',
    'weight_class',
    'distance',
    'distance_meters',
    'stroke',
    'team_size',
    'relay_legs',
    'relay_leg_distance_meters',
    'is_medal_event',
    'display_order',
    'gender',
    'age_division',
    'is_team_event',
    'max_entries_per_delegation',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => GenderCategory::class,
            'age_division' => AgeDivision::class,
            'is_team_event' => 'boolean',
            'max_entries_per_delegation' => 'integer',
            'active' => 'boolean',
            'team_size' => 'integer',
            'event_no' => 'integer',
            'distance_meters' => 'integer',
            'relay_legs' => 'integer',
            'relay_leg_distance_meters' => 'integer',
            'is_medal_event' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Sport, $this>
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Optional additional classification (e.g. "Elementary Boys Track")
     * — `gender`/`age_division` above remain the authoritative,
     * always-set columns; this is additive context, not a dependency of
     * anything reading this event today (see `SportCategory`'s own
     * docblock).
     *
     * @return BelongsTo<SportCategory, $this>
     */
    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function teamEntries(): HasMany
    {
        return $this->hasMany(TeamEntry::class);
    }

    /**
     * @return BelongsToMany<Meet, $this>
     */
    public function meets(): BelongsToMany
    {
        return $this->belongsToMany(Meet::class, 'meet_events')->withTimestamps();
    }

    /** Physical venues and their playable courts/tables for this event. */
    public function venueAssignments(): HasMany
    {
        return $this->hasMany(EventVenue::class);
    }
}
