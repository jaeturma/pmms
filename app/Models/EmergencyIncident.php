<?php

namespace App\Models;

use App\Enums\DrrmCategory;
use App\Enums\EmergencyIncidentStatus;
use Database\Factories\EmergencyIncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Deliberately a new table, not a repurposed `Incident` — the approved
 * model's own §7 rules this out explicitly: `Incident` is the existing,
 * working, simpler protest-adjacent meet-day log; DRRM incidents need
 * classification/responder/escalation fields that would bloat its
 * current purpose for the majority of its existing, non-emergency use.
 * See docs/medical-drrm.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property int|null $venue_id
 * @property DrrmCategory $category
 * @property string $description
 * @property EmergencyIncidentStatus $status
 * @property int $reported_by_user_id
 * @property Carbon $reported_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'venue_id', 'category', 'description', 'status', 'reported_by_user_id', 'reported_at', 'resolved_at'])]
class EmergencyIncident extends Model
{
    /** @use HasFactory<EmergencyIncidentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DrrmCategory::class,
            'status' => EmergencyIncidentStatus::class,
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * @return HasMany<EmergencyCommunicationLog, $this>
     */
    public function communicationLogs(): HasMany
    {
        return $this->hasMany(EmergencyCommunicationLog::class);
    }
}
