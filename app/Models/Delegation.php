<?php

namespace App\Models;

use App\Enums\DelegationStatus;
use Database\Factories\DelegationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $meet_id
 * @property int $school_id
 * @property string $head_name
 * @property string|null $head_phone
 * @property string|null $head_email
 * @property DelegationStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'school_id', 'head_name', 'head_phone', 'head_email'])]
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
     * @return BelongsToMany<User, $this>
     */
    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'delegation_user')->withTimestamps();
    }

    public function hasOfficer(User $user): bool
    {
        return $this->officers()->whereKey($user->getKey())->exists();
    }
}
