<?php

namespace App\Models;

use Database\Factories\MedicalAccessLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The break-glass emergency-access audit trail decision #2 requires — a
 * genuinely new pattern for this app: every other domain's `AuditLogger`
 * records mutations, this table records a *read* of sensitive data,
 * because there's otherwise nothing to log for "someone looked at this."
 * See docs/medical-drrm.md.
 *
 * @property int $id
 * @property int $medical_clearance_id
 * @property int $accessed_by_user_id
 * @property string $reason
 * @property Carbon $accessed_at
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property string|null $review_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['medical_clearance_id', 'accessed_by_user_id', 'reason', 'accessed_at', 'reviewed_by_user_id', 'reviewed_at', 'review_notes'])]
class MedicalAccessLog extends Model
{
    /** @use HasFactory<MedicalAccessLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MedicalClearance, $this>
     */
    public function medicalClearance(): BelongsTo
    {
        return $this->belongsTo(MedicalClearance::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function accessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accessed_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
