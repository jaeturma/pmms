<?php

namespace App\Models;

use App\Enums\AthleteOversightType;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\Permission;
use App\Enums\PersonnelRole;
use App\Enums\UserRole;
use App\Services\CoachAccessService;
use App\Services\CompetitionAccessService;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail as VerifiesEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
 * @property array<int, string> $additional_roles
 * @property string $approval_status
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable, VerifiesEmail;

    protected static function booted(): void
    {
        static::deleted(function (User $user): void {
            // Soft deletion does not execute the database foreign key's
            // nullOnDelete action. Preserve the existing audit privacy
            // contract by removing the deleted account as the log actor.
            AuditLog::query()->where('user_id', $user->id)->update(['user_id' => null]);
        });
    }

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
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'disabled_at' => 'datetime',
            'role' => UserRole::class,
            'additional_roles' => 'array',
            'approved_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::make(get: function (?string $value, array $attributes): ?string {
            if ($value === null) {
                return null;
            }

            $additionalRoles = json_decode($attributes['additional_roles'] ?? '[]', true) ?: [];
            $isCoach = ($attributes['role'] ?? null) === UserRole::Coach->value
                || in_array(UserRole::Coach->value, $additionalRoles, true);

            return $isCoach ? mb_strtoupper($value) : $value;
        });
    }

    public function hasRole(UserRole ...$roles): bool
    {
        $heldRoles = collect([$this->role])
            ->merge(collect($this->additional_roles ?? [])->map(
                fn (string $role): ?UserRole => UserRole::tryFrom($role),
            ))
            ->filter();

        return $heldRoles->contains(fn (UserRole $heldRole): bool => in_array($heldRole, $roles, true));
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function canManageProductionAccounts(): bool
    {
        return $this->isAdmin() || $this->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('team_type', ManagementTeamType::ICT->value)
                ->orWhereIn('source_code', [
                    'CENTRAL_ICT', 'ICT',
                ]))
            ->exists();
    }

    public function canManageSchoolMasterData(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $hasMeetLeadershipRole = $this->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereRaw("LOWER(TRIM(role_title)) IN ('meet manager', 'meet director')")
            ->exists();

        return $hasMeetLeadershipRole || $this->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('team_type', ManagementTeamType::ICT->value)
                ->orWhereIn('source_code', [
                    'CENTRAL_ICT', 'ICT',
                ]))
            ->exists();
    }

    /**
     * Coach registrations may be reviewed by a system administrator or any
     * active member of the ICT management team.
     */
    public function canReviewCoachRegistrations(): bool
    {
        return $this->isAdmin() || $this->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('team_type', ManagementTeamType::ICT->value)
                ->orWhereIn('source_code', [
                    'CENTRAL_ICT', 'ICT',
                ]))
            ->exists();
    }

    public function canManageAnnouncements(): bool
    {
        return $this->isAdmin() || $this->canManageProductionAccounts()
            || $this->managementTeamMemberships()
                ->where('status', ManagementTeamMemberStatus::Active)
                ->whereHas('managementTeam', fn ($team) => $team->where('source_code', 'INFORMATION'))
                ->exists();
    }

    public function isInformationTeamMember(?Meet $meet = null): bool
    {
        return $this->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('source_code', 'INFORMATION')
                ->when($meet !== null, fn ($scope) => $scope->where('meet_id', $meet->id)))
            ->exists();
    }

    public function canManageEditorialContent(?Meet $meet = null): bool
    {
        return $this->isAdmin() || $this->isInformationTeamMember($meet);
    }

    public function canUploadGalleryCandidates(): bool
    {
        return $this->isAdmin() || $this->canManageEditorialContent() || $this->meetSportAssignments()
            ->where('status', 'active')
            ->whereIn('role', [
                MeetSportAssignmentRole::TournamentICT->value,
                MeetSportAssignmentRole::TournamentSecretary->value,
            ])->exists();
    }

    public function canAccessContentManagement(): bool
    {
        return $this->canManageEditorialContent() || $this->canUploadGalleryCandidates();
    }

    public function canViewAnnouncements(): bool
    {
        return $this->hasRole(UserRole::Organizer) || $this->canManageAnnouncements();
    }

    public function canManagePersonnel(): bool
    {
        return $this->isAdmin() || $this->canManageProductionAccounts();
    }

    public function canFileProtest(?Delegation $delegation = null): bool
    {
        return Personnel::query()
            ->where('user_id', $this->id)
            ->where('role', PersonnelRole::DelegationManager)
            ->when($delegation !== null, fn ($query) => $query->where('delegation_id', $delegation->id))
            ->exists();
    }

    public function canViewManagementReports(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Organizer)
            || $this->canManageProductionAccounts()
            || $this->managementTeamMemberships()
                ->where('status', ManagementTeamMemberStatus::Active)
                ->whereHas('managementTeam', fn ($team) => $team
                    ->whereIn('team_type', [
                        ManagementTeamType::TopManagement->value,
                        ManagementTeamType::MeetManagement->value,
                    ]))
                ->exists();
    }

    /** @return Collection<int, int> */
    public function tournamentAthleteSportIds(): Collection
    {
        return $this->meetSportAssignments()
            ->where('meet_sport_assignments.status', 'active')
            ->whereIn('meet_sport_assignments.role', [
                MeetSportAssignmentRole::TournamentManager->value,
                MeetSportAssignmentRole::AssistantTournamentManager->value,
                MeetSportAssignmentRole::TrackTournamentManager->value,
                MeetSportAssignmentRole::FieldTournamentManager->value,
                MeetSportAssignmentRole::BoysTournamentManager->value,
                MeetSportAssignmentRole::GirlsTournamentManager->value,
                MeetSportAssignmentRole::CategoryTournamentManager->value,
                MeetSportAssignmentRole::TechnicalOfficial->value,
                MeetSportAssignmentRole::TournamentICT->value,
                MeetSportAssignmentRole::TournamentSecretary->value,
            ])
            ->join('meet_sports', 'meet_sports.id', '=', 'meet_sport_assignments.meet_sport_id')
            ->distinct()
            ->pluck('meet_sports.sport_id');
    }

    /** @return Collection<int, int> */
    public function tournamentEventIds(?int $meetId = null): Collection
    {
        return app(CompetitionAccessService::class)->eventIds($this, $meetId);
    }

    /** @return Collection<int, int> */
    public function tournamentMeetIds(): Collection
    {
        return $this->meetSportAssignments()
            ->where('meet_sport_assignments.status', 'active')
            ->whereIn('meet_sport_assignments.role', [
                MeetSportAssignmentRole::TournamentManager->value,
                MeetSportAssignmentRole::AssistantTournamentManager->value,
                MeetSportAssignmentRole::TrackTournamentManager->value,
                MeetSportAssignmentRole::FieldTournamentManager->value,
                MeetSportAssignmentRole::BoysTournamentManager->value,
                MeetSportAssignmentRole::GirlsTournamentManager->value,
                MeetSportAssignmentRole::CategoryTournamentManager->value,
                MeetSportAssignmentRole::TechnicalOfficial->value,
                MeetSportAssignmentRole::TournamentICT->value,
                MeetSportAssignmentRole::TournamentSecretary->value,
            ])
            ->join('meet_sports', 'meet_sports.id', '=', 'meet_sport_assignments.meet_sport_id')
            ->distinct()
            ->pluck('meet_sports.meet_id');
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

    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    /**
     * The one sport (if any) this Tournament Manager holds the catalog-wide
     * `TournamentManager` login role for — the inverse of
     * `Sport::tournamentManager()`. Meaningless for every other role.
     *
     * @return HasOne<Sport, $this>
     */
    public function managedSport(): HasOne
    {
        return $this->hasOne(Sport::class, 'tournament_manager_id');
    }

    /**
     * @return HasMany<ManagementTeamMember, $this>
     */
    public function managementTeamMemberships(): HasMany
    {
        return $this->hasMany(ManagementTeamMember::class);
    }

    public function athleteOversightAssignments(): HasMany
    {
        return $this->hasMany(AthleteOversightAssignment::class);
    }

    public function coachAssignmentRequests(): HasMany
    {
        return $this->hasMany(CoachAssignmentRequest::class);
    }

    public function coachOnboardingRequest(): HasOne
    {
        return $this->hasOne(CoachOnboardingRequest::class);
    }

    /** @return Collection<int, int> */
    public function approvedCoachEventIds(): Collection
    {
        return app(CoachAccessService::class)->eventIds($this);
    }

    /** @return Collection<int, int> */
    public function approvedCoachEventIdsForDelegation(Delegation $delegation): Collection
    {
        return app(CoachAccessService::class)->eventIds($this, $delegation);
    }

    /** @return Collection<int, int> */
    public function approvedCoachDelegationIds(): Collection
    {
        return app(CoachAccessService::class)->delegationIds($this);
    }

    public function hasApprovedCoachScope(Delegation $delegation, ?Event $event = null): bool
    {
        if ($this->role !== UserRole::Coach) {
            return false;
        }

        $access = app(CoachAccessService::class);

        return $event === null
            ? $access->eventIds($this, $delegation)->isNotEmpty()
            : $access->canAccessEvent($this, $event, $delegation);
    }

    public function hasPermission(Permission $permission, ?Meet $meet = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (in_array($permission, [
            Permission::ContentView,
            Permission::NewsManage,
            Permission::AnnouncementsManage,
            Permission::FaqManage,
            Permission::GalleryReview,
            Permission::GalleryPublish,
        ], true)) {
            return $this->canManageEditorialContent($meet);
        }

        if ($permission === Permission::GalleryUploadCandidate) {
            return $this->canUploadGalleryCandidates();
        }

        $teamType = match ($permission) {
            Permission::AthleteProfileValidate, Permission::AthleteDocumentsVerify,
            Permission::AthleteEligibilityReview, Permission::AthleteEligibilityApprove => ManagementTeamType::DivisionScreeningAndAccreditation,
            Permission::MedicalClearanceEvaluate, Permission::MedicalClearanceApprove => ManagementTeamType::Medical,
            default => null,
        };
        if ($teamType !== null && $this->managementTeamMemberships()->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($query) => $query->where('team_type', $teamType)
                ->when($meet !== null, fn ($scope) => $scope->where('meet_id', $meet->id)))->exists()) {
            return true;
        }

        $oversightType = match ($permission) {
            Permission::DistrictReadinessView, Permission::DistrictAthletesView => AthleteOversightType::DistrictSportsCoordinator,
            Permission::MunicipalityReadinessView, Permission::MunicipalityAthletesView => AthleteOversightType::MunicipalityTeamManager,
            default => null,
        };

        return $oversightType !== null && $this->athleteOversightAssignments()->where('active', true)
            ->where('authority_type', $oversightType)
            ->when($meet !== null, fn ($query) => $query->where('meet_id', $meet->id))->exists();
    }

    /**
     * Final athlete accreditation is a separation-of-duties decision made
     * only by DSAC leadership, after ordinary DSAC members have verified
     * the individual requirements.
     */
    public function isDsacAccreditationLeader(Meet $meet): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $titles = [
            'chair', 'chairperson', 'co-chair', 'co-chairperson',
            'assistant chair', 'assistant chairperson',
            'team leader', 'team lead', 'leader',
        ];

        return $this->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->where(function ($membership) use ($titles) {
                $membership->where('is_head', true)
                    ->orWhereIn(DB::raw('LOWER(TRIM(role_title))'), $titles);
            })
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('meet_id', $meet->id)
                ->where(function ($dsac) {
                    $dsac->where('source_code', 'DSAC')
                        ->orWhere('team_type', ManagementTeamType::DivisionScreeningAndAccreditation);
                }))
            ->exists();
    }
}
