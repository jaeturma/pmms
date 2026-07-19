<?php

namespace App\Models;

use Database\Factories\AccreditationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An accreditation decision for one meet participant. Exactly one of
 * athlete_id / personnel_id is set. Presence of the row means accredited;
 * revoking deletes it (both decisions are audited).
 *
 * @property int $id
 * @property int $delegation_id
 * @property int|null $athlete_id
 * @property int|null $personnel_id
 * @property string|null $number
 * @property int|null $accredited_by
 * @property Carbon $accredited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['delegation_id', 'athlete_id', 'personnel_id', 'accredited_at'])]
class Accreditation extends Model
{
    /** @use HasFactory<AccreditationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accredited_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Delegation, $this>
     */
    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
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
     * @return BelongsTo<User, $this>
     */
    public function accreditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accredited_by');
    }

    public function subjectName(): string
    {
        return $this->athlete?->fullName()
            ?? $this->personnel?->fullName()
            ?? '';
    }

    /**
     * Derive the unique card number from the meet and the row id.
     */
    public function assignNumber(): void
    {
        $this->forceFill([
            'number' => sprintf('ACR-%03d-%05d', $this->delegation->meet_id, $this->id),
        ])->save();
    }
}
