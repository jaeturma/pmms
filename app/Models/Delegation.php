<?php

namespace App\Models;

use App\Enums\DelegationStatus;
use Database\Factories\DelegationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $meet_id
 * @property int|null $school_id
 * @property int|null $district_id
 * @property string $head_name
 * @property string|null $head_phone
 * @property string|null $head_email
 * @property DelegationStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'school_id', 'district_id', 'head_name', 'head_phone', 'head_email'])]
class Delegation extends Model
{
    /** @use HasFactory<DelegationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DelegationStatus::class,
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
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'delegation_user')->withTimestamps();
    }

    /**
     * @return HasMany<Athlete, $this>
     */
    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    /**
     * @return HasMany<Personnel, $this>
     */
    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function hasOfficer(User $user): bool
    {
        return $this->officers()->whereKey($user->getKey())->exists();
    }

    /**
     * @return HasOne<BilletingAssignment, $this>
     */
    public function billetingAssignment(): HasOne
    {
        return $this->hasOne(BilletingAssignment::class);
    }

    /**
     * @return HasMany<TransportRequest, $this>
     */
    public function transportRequests(): HasMany
    {
        return $this->hasMany(TransportRequest::class);
    }

    /**
     * @return HasMany<TransportTrip, $this>
     */
    public function transportTrips(): HasMany
    {
        return $this->hasMany(TransportTrip::class);
    }

    /**
     * Whether `$user` is this delegation's own Coach (or Assistant Coach,
     * or Chaperone — any roster role) via their own `Personnel.user_id`
     * link, not the `delegation_user` pivot `hasOfficer()` checks — a
     * Coach is scoped through their roster identity, not an
     * administrative "officer" assignment. See `Personnel::user()`'s
     * docblock.
     */
    public function hasCoach(User $user): bool
    {
        return $this->personnel()->where('user_id', $user->id)->exists();
    }

    /**
     * The delegation's own identity — its school's name (City division) or
     * its district's name (Province division, presented as "Municipality").
     * Use this for anything describing the delegation itself (rosters,
     * ID-card headers, protest labels); it is never the right source for an
     * individual athlete's/personnel's own school — see their own
     * `school_id` for that.
     */
    public function registrantName(): string
    {
        if ($this->school !== null) {
            return $this->school->name;
        }

        if ($this->district !== null) {
            return $this->district->name;
        }

        return '';
    }

    /**
     * 'school' or 'district' — whichever this delegation registered under.
     */
    public function registrantType(): string
    {
        return $this->school_id !== null ? 'school' : 'district';
    }

    /**
     * Draft registration follows the meet window. A submitted delegation
     * remains a working roster until it is approved, so assigned officers
     * and coaches can still correct officials and personnel after submission.
     */
    public function isEditableByOfficers(): bool
    {
        return $this->status === DelegationStatus::Submitted
            || ($this->status === DelegationStatus::Draft && $this->meet->isRegistrationOpen());
    }
}
