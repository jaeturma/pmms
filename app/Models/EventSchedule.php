<?php

namespace App\Models;

use Database\Factories\EventScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A schedule slot: one session of an event at a venue. Events may have
 * several slots (multi-day or multi-session events).
 *
 * @property int $id
 * @property int $meet_id
 * @property int $event_id
 * @property int|null $sport_category_id
 * @property int $venue_id
 * @property Carbon $scheduled_date
 * @property string $starts_at
 * @property string $ends_at
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'event_id', 'sport_category_id', 'venue_id', 'competition_area_id', 'scheduled_date', 'starts_at', 'ends_at', 'note'])]
class EventSchedule extends Model
{
    /** @use HasFactory<EventScheduleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
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
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function competitionArea(): BelongsTo
    {
        return $this->belongsTo(CompetitionArea::class);
    }

    /**
     * Optional additional classification (e.g. this slot is specifically
     * the "Elementary Boys Track" session) — `event`/`venue` above remain
     * the authoritative, always-set columns; this is additive context, not
     * a dependency of anything reading this schedule today. Mirrors
     * `Event::sportCategory()`'s own docblock.
     *
     * @return BelongsTo<SportCategory, $this>
     */
    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }
}
