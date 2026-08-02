<?php

namespace App\Models;

use App\Enums\PersonnelRole;
use Database\Factories\PersonnelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delegation_id
 * @property int $school_id
 * @property string $first_name
 * @property string $last_name
 * @property PersonnelRole $role
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $photo_upload_id
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['delegation_id', 'school_id', 'first_name', 'last_name', 'role', 'phone', 'email'])]
class Personnel extends Model
{
    /** @use HasFactory<PersonnelFactory> */
    use HasFactory;

    /**
     * Eloquent would pluralize to "personnels"; the table is "personnel".
     */
    protected $table = 'personnel';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => PersonnelRole::class,
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
     * The person's own home school — see Athlete::school() for why this is
     * decoupled from the delegation's registering unit.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsToMany<Sport, $this>
     */
    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class, 'personnel_sport')->withTimestamps();
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'photo_upload_id');
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * @return HasOne<Accreditation, $this>
     */
    public function accreditation(): HasOne
    {
        return $this->hasOne(Accreditation::class);
    }

    /**
     * @return HasOne<MedicalClearance, $this>
     */
    public function medicalClearance(): HasOne
    {
        return $this->hasOne(MedicalClearance::class);
    }

    /**
     * This roster row's own login account, when it has one — a Coach
     * account (`App\Enums\UserRole::Coach`) is scoped through this link
     * (`Delegation::hasCoach()`), not through a `delegation_user`-style
     * pivot the way a Delegation Officer is. `user_id` is intentionally
     * kept out of Fillable (like `photo_upload_id`) — linking a roster
     * row to a login is a controlled action, never mass-assignable from
     * `PersonnelController::update()`'s general request input. Most
     * `Personnel` rows have no linked account at all; that's normal, not
     * every coach/assistant coach/chaperone gets a login.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
