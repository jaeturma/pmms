<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Database\Factories\EventMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * One match or heat of a meet event ("Match" itself is a PHP reserved
 * word). Participants come from the event's confirmed entries.
 *
 * @property int $id
 * @property int $meet_id
 * @property int $event_id
 * @property int|null $event_schedule_id
 * @property string $round_label
 * @property int $sequence
 * @property MatchStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'event_id', 'event_schedule_id', 'round_label', 'sequence'])]
class EventMatch extends Model
{
    /** @use HasFactory<EventMatchFactory> */
    use HasFactory;

    protected $table = 'matches';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => MatchStatus::class,
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
     * @return BelongsTo<EventSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EventSchedule::class, 'event_schedule_id');
    }

    /**
     * @return BelongsToMany<Entry, $this>
     */
    public function entries(): BelongsToMany
    {
        return $this->belongsToMany(Entry::class, 'match_entries', 'match_id')->withTimestamps();
    }
}
