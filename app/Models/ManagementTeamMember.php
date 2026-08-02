<?php

namespace App\Models;

use App\Enums\ManagementTeamMemberStatus;
use Database\Factories\ManagementTeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $management_team_id
 * @property int $user_id
 * @property string|null $role_title
 * @property bool $is_head
 * @property string|null $responsibilities
 * @property ManagementTeamMemberStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['management_team_id', 'user_id', 'role_title', 'is_head', 'responsibilities', 'status'])]
class ManagementTeamMember extends Model
{
    /** @use HasFactory<ManagementTeamMemberFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_head' => 'boolean',
            'status' => ManagementTeamMemberStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ManagementTeam, $this>
     */
    public function managementTeam(): BelongsTo
    {
        return $this->belongsTo(ManagementTeam::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
