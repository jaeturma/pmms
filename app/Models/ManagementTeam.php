<?php

namespace App\Models;

use App\Enums\ManagementTeamStatus;
use App\Enums\ManagementTeamType;
use Database\Factories\ManagementTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The administrative shell for one meet's committee/oversight function
 * (who is on it, what is its mandate) — deliberately not the specialized
 * operational data a given team type produces (Medical records,
 * Equipment, Meals, etc. are separate, later work packages that
 * reference a team by id). See `ManagementTeamType`'s own docblock and
 * docs/architecture/pmms-approved-organizational-model.md §6.
 *
 * @property int $id
 * @property int $meet_id
 * @property ManagementTeamType $team_type
 * @property string $name
 * @property string|null $description
 * @property ManagementTeamStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $members_count
 */
#[Fillable(['meet_id', 'team_type', 'source_code', 'name', 'description', 'display_order', 'status'])]
class ManagementTeam extends Model
{
    /** @use HasFactory<ManagementTeamFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_type' => ManagementTeamType::class,
            'status' => ManagementTeamStatus::class,
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
     * @return HasMany<ManagementTeamMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ManagementTeamMember::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }
}
