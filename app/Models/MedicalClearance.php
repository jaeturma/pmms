<?php

namespace App\Models;

use App\Enums\MedicalClearanceStatus;
use Database\Factories\MedicalClearanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One row per person — no case-management/encounter history, per
 * WP-REALIGN-12's own deliberately minimal scope decision. Exactly one
 * of `athlete_id`/`personnel_id` is set (enforced at the controller
 * level), the same mutual-exclusivity shape `Protest` already uses for
 * `event_result_id`/`match_id`. `status` is the only field safe to
 * surface outside the Medical Team — `conditions`/`emergency_contact_*`/
 * `notes` are Medical-Team-or-Admin-only, see `MedicalPolicy`. See
 * docs/medical-drrm.md.
 *
 * @property int $id
 * @property int $meet_id
 * @property int|null $athlete_id
 * @property int|null $personnel_id
 * @property MedicalClearanceStatus $status
 * @property string|null $conditions
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property bool $consent_confirmed
 * @property Carbon|null $consent_confirmed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'athlete_id', 'personnel_id', 'status', 'conditions', 'emergency_contact_name', 'emergency_contact_phone', 'consent_confirmed', 'consent_confirmed_at', 'notes'])]
class MedicalClearance extends Model
{
    /** @use HasFactory<MedicalClearanceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MedicalClearanceStatus::class,
            'consent_confirmed' => 'boolean',
            'consent_confirmed_at' => 'datetime',
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
     * @return BelongsTo<Athlete, $this>
     */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    /**
     * @return BelongsTo<Personnel, $this>
     */
    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    /**
     * @return HasMany<MedicalAccessLog, $this>
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(MedicalAccessLog::class);
    }

    /**
     * The name of whichever of athlete/personnel this clearance is for.
     */
    public function personName(): string
    {
        if ($this->athlete !== null) {
            return "{$this->athlete->first_name} {$this->athlete->last_name}";
        }

        if ($this->personnel !== null) {
            return "{$this->personnel->first_name} {$this->personnel->last_name}";
        }

        return '';
    }
}
