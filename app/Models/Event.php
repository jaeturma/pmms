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
    'name',
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
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @return BelongsToMany<Meet, $this>
     */
    public function meets(): BelongsToMany
    {
        return $this->belongsToMany(Meet::class, 'meet_events')->withTimestamps();
    }
}
