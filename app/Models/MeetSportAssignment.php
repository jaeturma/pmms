<?php

namespace App\Models;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use Database\Factories\MeetSportAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A person's role against one meet's inclusion of one sport — the
 * generic personnel-assignment layer the running system lacked (Tournament
 * Manager/Assistant/Track/Field/Boys/Girls/Category, Tournament
 * Secretary, Tournament ICT, and Technical Official all live here, one
 * row per person per role per `MeetSport`). See
 * docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md
 * §9 and docs/architecture/pmms-approved-organizational-model.md §3.
 *
 * Deliberately does **not** yet replace `sport_user` (the existing,
 * meet-unscoped Technical Official pivot `Sport::technicalOfficials()`/
 * `User::sports()`) — `ScoringSessionController`/`ResultController` still
 * read `sport_user` today. Cutting them over to this table requires a
 * backfill-strategy decision with no deterministic answer from existing
 * data alone (`sport_user` has no `meet_id` to derive from) — see
 * docs/architecture/pmms-data-migration-plan.md §5, §9 steps 5-6. This
 * migration/model introduces the table only; no existing data was moved
 * into it and no existing authorization code was changed.
 *
 * @property int $id
 * @property int $meet_sport_id
 * @property int|null $sport_category_id
 * @property int $user_id
 * @property MeetSportAssignmentRole $role
 * @property bool $is_lead
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property MeetSportAssignmentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_sport_id', 'sport_category_id', 'user_id', 'role', 'is_lead', 'start_date', 'end_date', 'status'])]
class MeetSportAssignment extends Model
{
    /** @use HasFactory<MeetSportAssignmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MeetSportAssignmentRole::class,
            'is_lead' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => MeetSportAssignmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<MeetSport, $this>
     */
    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    /**
     * @return BelongsTo<SportCategory, $this>
     */
    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
