<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as VerifiesEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, VerifiesEmail;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Sports this Technical Official is assigned to operate live scoring
     * for — meaningless for every other role, mirrors Personnel::sports().
     *
     * @return BelongsToMany<Sport, $this>
     */
    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class)->withTimestamps();
    }

    /**
     * This user's meet-scoped tournament-personnel assignments (Tournament
     * Manager/Secretary/ICT/Technical Official) — distinct from `sports()`
     * above, which is the existing, meet-unscoped Technical Official
     * pivot still in live use (see `MeetSportAssignment`'s own docblock).
     *
     * @return HasMany<MeetSportAssignment, $this>
     */
    public function meetSportAssignments(): HasMany
    {
        return $this->hasMany(MeetSportAssignment::class);
    }

    /**
     * A Coach's own roster identity (`Personnel::user()`'s inverse) — not
     * unique across meets by design (a returning coach gets a new
     * `Personnel` row per meet's delegation but may keep the same login,
     * see the migration's own docblock), so this resolves to whichever
     * row Eloquent's default ordering returns first when a login has
     * more than one. Fine for this WP's scope (one active assignment at
     * a time is the common case); a "current meet" concept would be
     * needed to disambiguate the multi-row case precisely.
     *
     * @return HasOne<Personnel, $this>
     */
    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class);
    }
}
