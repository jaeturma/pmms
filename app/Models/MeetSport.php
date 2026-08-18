<?php

namespace App\Models;

use Database\Factories\MeetSportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A sport's inclusion in one specific meet — the structural piece the
 * running system lacked (`Sport` itself has zero meet-scoping; see
 * `docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md`
 * §7). Deliberately does not replace `Sport`'s own `events()` relation or
 * the `meet_events` pivot on `Event` — those are unchanged; this table is
 * additive, meant to become the scope every meet-specific personnel
 * assignment (Tournament Manager/Secretary/ICT/Technical Official) hangs
 * off in a later work package, in place of today's meet-unscoped
 * `sport_user` pivot.
 *
 * @property int $id
 * @property int $meet_id
 * @property int $sport_id
 * @property bool $active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'sport_id', 'status', 'description', 'venue_notes', 'active', 'notes', 'display_order'])]
class MeetSport extends Model
{
    /** @use HasFactory<MeetSportFactory> */
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
            'display_order' => 'integer',
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
     * @return BelongsTo<Sport, $this>
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Categories scoped specifically to this meet's inclusion of the
     * sport — catalog-wide categories (`SportCategory.meet_sport_id`
     * null) are reached via `sport->categories()` instead.
     *
     * @return HasMany<SportCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(SportCategory::class);
    }

    /**
     * Tournament Manager/Secretary/ICT/Technical Official assignments
     * scoped to this meet's inclusion of the sport.
     *
     * @return HasMany<MeetSportAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(MeetSportAssignment::class);
    }
}
