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
use Illuminate\Support\Collection;

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
#[Fillable(['sport_id', 'meet_sport_id', 'name', 'slug', 'level', 'sex', 'discipline', 'classification', 'event_type', 'competition_format', 'team_size', 'min_players', 'max_players', 'participation_notes', 'display_name', 'display_order', 'active', 'min_age', 'max_age', 'min_grade', 'max_grade'])]
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
            'team_size' => 'integer',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'display_order' => 'integer',
            'min_age' => 'integer',
            'max_age' => 'integer',
            'min_grade' => 'integer',
            'max_grade' => 'integer',
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

    /**
     * Schedule slots specifically scoped to this category (e.g. an
     * "Elementary Boys Track" session) — a slot's `event`/`venue` remain
     * its own authoritative source; `EventSchedule.sport_category_id` is
     * additive context, same relationship shape as `events()` above.
     *
     * @return HasMany<EventSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class);
    }

    /**
     * The distinct venues this category's own schedule slots have been
     * booked into. Derived through `schedules()` rather than a direct FK
     * — a category has no `venue_id` of its own, since its slots may span
     * several venues across a meet.
     *
     * @return Collection<int, Venue>
     */
    public function venues(): Collection
    {
        return Venue::query()
            ->whereIn('id', $this->schedules()->pluck('venue_id'))
            ->get();
    }
}
