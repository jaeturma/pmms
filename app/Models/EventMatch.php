<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Database\Factories\EventMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
#[Fillable(['meet_id', 'event_id', 'event_schedule_id', 'competition_area', 'round_label', 'sequence', 'live_scoring_enabled', 'awards_medals'])]
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
            'live_scoring_enabled' => 'boolean',
            'awards_medals' => 'boolean',
        ];
    }

    public function scopeReal($query) { return $query->whereNull('demo_scenario_id'); }
    public function scopeDemo($query) { return $query->whereNotNull('demo_scenario_id'); }
    public function demoScenario(): BelongsTo { return $this->belongsTo(DemoScenario::class); }

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

    /**
     * Delegation-level participants for team events. A team remains one
     * match entry regardless of how many athletes are assigned to it.
     *
     * @return BelongsToMany<TeamEntry, $this>
     */
    public function teamEntries(): BelongsToMany
    {
        return $this->belongsToMany(TeamEntry::class, 'match_team_entries', 'match_id')->withTimestamps();
    }

    public function participantSlots(): HasMany
    {
        return $this->hasMany(MatchParticipantSlot::class, 'match_id');
    }

    /**
     * @return HasMany<ScoringSession, $this>
     */
    public function scoringSessions(): HasMany
    {
        return $this->hasMany(ScoringSession::class, 'match_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(EventResult::class, 'match_id');
    }
}
