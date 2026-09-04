<?php

namespace App\Http\Middleware;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Models\Division;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\Setting;
use App\Services\CompetitionAccessService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $division = Division::current();
        $user = $request->user();
        $settings = Setting::current();
        $assignedSports = collect();
        if ($user !== null) {
            $assignedSports = app(CompetitionAccessService::class)->labels($user);
            if ($assignedSports->isEmpty() && $user->role === UserRole::TechnicalOfficial) {
                $assignedSports = $user->sports()->orderBy('name')->pluck('name');
            } elseif ($assignedSports->isEmpty() && $user->role === UserRole::TournamentManager && $user->managedSport !== null) {
                $assignedSports = collect([$user->managedSport->name]);
            }
        }

        return [
            ...parent::share($request),
            'name' => $settings->app_title ?: config('app.name'),
            'systemTimezone' => $settings->timezone ?: config('app.timezone'),
            'branding' => [
                'title' => $settings->app_title ?: config('app.name'),
                'logoUrl' => $settings->app_logo_upload_id === null ? null : route('branding.logo'),
                'loginSplashTitle' => $settings->login_splash_title ?: 'One secure place to manage every moment of the meet.',
                'loginBackgroundUrl' => $settings->login_background_upload_id === null ? null : route('branding.login-background'),
            ],
            'auth' => [
                'user' => $user === null ? null : [
                    ...$user->toArray(),
                    'role_label' => $user->role->label(),
                    'tournament_assignment_roles' => app(CompetitionAccessService::class)
                        ->assignments($user, Meet::current()->id)
                        ->map(fn ($assignment): string => $assignment->role->value)
                        ->unique()->values(),
                    'team_types' => $user->managementTeamMemberships()
                        ->where('management_team_members.status', 'active')
                        ->join('management_teams', 'management_teams.id', '=', 'management_team_members.management_team_id')
                        ->distinct()
                        ->pluck('management_teams.team_type')
                        ->values(),
                    'can_review_coaches' => $user->canReviewCoachRegistrations() || $user->meetSportAssignments()
                        ->where('status', 'active')
                        ->whereIn('role', [
                            MeetSportAssignmentRole::TournamentManager->value,
                            MeetSportAssignmentRole::AssistantTournamentManager->value,
                            MeetSportAssignmentRole::TechnicalOfficial->value,
                            MeetSportAssignmentRole::TournamentICT->value,
                            MeetSportAssignmentRole::TournamentSecretary->value,
                        ])->exists(),
                    'can_request_coach_enrollment' => $user->role->value === 'coach'
                        || $user->coachOnboardingRequest()->whereIn('status', ['pending', 'rejected'])->exists(),
                    'can_manage_school_master_data' => $user->canManageSchoolMasterData(),
                    'can_manage_accounts' => $user->canManageProductionAccounts(),
                    'can_manage_announcements' => $user->canManageAnnouncements(),
                    'can_access_content_management' => $user->canAccessContentManagement(),
                    'can_manage_editorial_content' => $user->canManageEditorialContent(),
                    'can_upload_gallery_candidates' => $user->canUploadGalleryCandidates(),
                    'can_manage_personnel' => $user->canManagePersonnel(),
                    'can_file_protest' => $user->canFileProtest(),
                    'can_view_management_reports' => $user->canViewManagementReports(),
                    'can_view_system_logs' => $user->can('view-system-logs'),
                    'can_access_meal_stub' => app(\App\Services\MealEntitlementService::class)
                        ->isEligible($user, Meet::current()),
                    'can_view_tournament_athletes' => $user->tournamentAthleteSportIds()->isNotEmpty(),
                    'assigned_sports' => $assignedSports->values(),
                    'is_tournament_scoped' => $assignedSports->isNotEmpty()
                        && ! $user->hasRole(UserRole::Admin, UserRole::Organizer),
                ],
            ],
            'impersonation' => $request->session()->has('impersonator_user_id') ? [
                'active' => true,
                'administrator_id' => $request->session()->get('impersonator_user_id'),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'division' => [
                'type' => $division->type->value,
                'name' => $division->name,
                'areaLabel' => $division->areaLabel(),
                'logoUrl' => $division->logo_upload_id === null ? null : route('division.logo'),
                'heroLogoUrl' => $division->hero_icon_upload_id === null ? null : route('division.hero-icon'),
            ],
            // Shell-level chrome (WP-08-03): the sidebar's persistent meet-context
            // card needs this on every authenticated page, not just the dashboard
            // — guarded to authenticated requests only so guest/public portal
            // page loads (no sidebar) never pay for the extra query.
            'currentMeet' => $user === null ? null : $this->currentMeet(),
            // Shell-level chrome (WP-08-07): the public portal header's nav
            // needs a meet to link Schedule/Results/Medal Tally into, and a
            // live-match count for its "Live now" indicator, on every public
            // page — guarded to guest requests only so authenticated page
            // loads never pay for the extra query.
            'publicNav' => $user === null ? $this->publicNav() : null,
            // Only the login/register pages actually render the widget —
            // guarded to guest requests for the same reason as `publicNav`
            // above. The site key is public by design (Google's own docs:
            // it's meant to ship to the browser); the secret key never
            // leaves the server.
            'recaptcha' => $user === null ? $this->recaptcha() : null,
        ];
    }

    /**
     * @return array{name: string, status_label: string, starts_at: string, ends_at: string, venue: string|null}
     */
    private function currentMeet(): array
    {
        $meet = Meet::current();

        return [
            'name' => $meet->name,
            'status_label' => $meet->status->label(),
            'starts_at' => $meet->starts_at->toDateString(),
            'ends_at' => $meet->ends_at->toDateString(),
            'venue' => $meet->venue,
        ];
    }

    /**
     * The one active meet (the same meet the landing page features), or —
     * when no meet is currently active — the most recently started
     * published meet, so guest navigation still has somewhere to point.
     * Scoped through `Meet::published()` since this feeds guest-facing
     * navigation, unlike the authenticated sidebar card. `venue`/
     * `schoolYear` (WP-10-02) feed the public footer's meet-info column.
     *
     * @return array{meetId: int, meetName: string, venue: string|null, schoolYear: string, liveCount: int}|null
     */
    private function publicNav(): ?array
    {
        $meet = Meet::query()->published()->active()->first()
            ?? Meet::query()->published()->orderByDesc('starts_at')->first();

        if ($meet === null) {
            return null;
        }

        return [
            'meetId' => $meet->id,
            'meetName' => $meet->name,
            'venue' => $meet->venue,
            'schoolYear' => $meet->school_year,
            'liveCount' => ScoringSession::query()
                ->where('status', '!=', ScoringSessionStatus::Ended->value)
                ->whereHas('match', fn ($query) => $query->where('meet_id', $meet->id))
                ->count(),
        ];
    }

    /**
     * @return array{enabled: bool, siteKey: string|null}
     */
    private function recaptcha(): array
    {
        $settings = Setting::current();

        return [
            'enabled' => $settings->recaptchaReady(),
            'siteKey' => $settings->recaptchaReady() ? $settings->recaptcha_site_key : null,
        ];
    }
}
