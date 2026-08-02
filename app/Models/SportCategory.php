<?php

namespace App\Models;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use Database\Factories\SportCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An optional classification layer between `Sport` and `Event` — e.g.
 * "Elementary Boys Track" under Athletics. Additive: `Event`'s own
 * `gender`/`age_division` columns remain the authoritative, always-set
 * classification; `Event.sport_category_id` is optional extra context,
 * not a replacement (see
 * docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md
 * §8). A category may be catalog-wide (`meet_sport_id` null, reusable
 * across every meet the sport is included in) or scoped to one specific
 * meet's inclusion of the sport.
 *
 * @property int $id
 * @property int $sport_id
 * @property int|null $meet_sport_id
 * @property AgeDivision|null $level
 * @property GenderCategory|null $sex
 * @property string|null $discipline
 * @property string|null $event_type
 * @property string $display_name
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sport_id', 'meet_sport_id', 'level', 'sex', 'discipline', 'event_type', 'display_name'])]
class SportCategory extends Model
{
    /** @use HasFactory<SportCategoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => AgeDivision::class,
            'sex' => GenderCategory::class,
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
     * @return BelongsTo<MeetSport, $this>
     */
    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Assignments specifically scoped to this category (e.g. a Category
     * Tournament Manager) — assignments scoped to the whole meet_sport
     * instead are reached via `meetSport->assignments()`.
     *
     * @return HasMany<MeetSportAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(MeetSportAssignment::class);
    }
}
