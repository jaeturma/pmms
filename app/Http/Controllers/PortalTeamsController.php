<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\PersonnelRole;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Sport;
use App\Models\SportRosterMember;
use App\Services\MedalTallyService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public municipal Teams/Delegations directory — per-municipality
 * profile (medal breakdown, medal winners, athletes/coaches). Kept in its
 * own controller rather than folded into the already-large
 * `PortalController`, since this is a genuinely new, self-contained
 * feature area, not an existing responsibility being split up.
 *
 * Resolves to whichever meet is currently "active" (`Meet::published()->
 * active()`) rather than taking a `{meet}` route parameter — the same
 * pattern `PortalController::sportPortal()` already established for its
 * own permanent, meet-agnostic routes (Phase 12) — so a municipality's URL
 * (`/teams/nabunturan`) stays stable across meets instead of embedding a
 * meet ID, at the same cost: only reachable while its meet is currently
 * active, not merely published.
 */
class PortalTeamsController extends Controller
{
    public function __construct(private readonly MedalTallyService $tally) {}

    public function index(): Response
    {
        $meet = Meet::query()->published()->active()->first();

        if ($meet === null) {
            return Inertia::render('portal/teams', [
                'meet' => null,
                'teams' => [],
            ]);
        }

        $municipalities = $this->competingMunicipalities($meet);
        $participation = $this->municipalityParticipation($meet);
        $standings = collect($this->tally->standings($meet->id)['districts'])->keyBy('district_id');

        return Inertia::render('portal/teams', [
            'meet' => $this->meetSummary($meet),
            'teams' => $municipalities
                ->map(function (District $municipality) use ($participation, $standings): array {
                    $stats = $participation->get($municipality->id, ['athlete_count' => 0, 'sport_count' => 0]);
                    $medals = $standings->get($municipality->id);

                    return [
                        'id' => $municipality->id,
                        'slug' => Str::slug($municipality->name),
                        'name' => $municipality->name,
                        'nickname' => $municipality->nickname,
                        'congressional_district' => $municipality->congressionalDistrict?->name,
                        'logo_url' => $municipality->logoUrl(),
                        'team_logo_url' => $municipality->teamLogoUrl(),
                        'athlete_count' => $stats['athlete_count'],
                        'sport_count' => $stats['sport_count'],
                        'medals' => [
                            'gold' => $medals['gold'] ?? 0,
                            'silver' => $medals['silver'] ?? 0,
                            'bronze' => $medals['bronze'] ?? 0,
                            'total' => $medals['total'] ?? 0,
                        ],
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    public function show(Request $request, string $municipality): Response
    {
        $meet = Meet::query()->published()->active()->firstOrFail();
        $district = $this->resolveMunicipality($meet, $municipality);

        $category = $request->query('category');
        $category = in_array($category, ['elementary', 'secondary', 'paragames'], true) ? $category : null;

        $stats = $this->municipalityParticipation($meet)
            ->get($district->id, ['athlete_count' => 0, 'sport_count' => 0]);

        return Inertia::render('portal/team-detail', [
            'meet' => $this->meetSummary($meet),
            'team' => [
                'id' => $district->id,
                'slug' => Str::slug($district->name),
                'name' => $district->name,
                'nickname' => $district->nickname,
                'congressional_district' => $district->congressionalDistrict?->name,
                'logo_url' => $district->logoUrl(),
                'team_logo_url' => $district->teamLogoUrl(),
                'athlete_count' => $stats['athlete_count'],
                'sport_count' => $stats['sport_count'],
            ],
            'medalBreakdown' => $this->tally->municipalityMedalBreakdown($meet->id, $district->id),
            'medalWinners' => $this->tally->municipalityMedalWinners($meet->id, $district->id, $category),
            'filters' => ['category' => $category],
        ]);
    }

    public function playersCoaches(Request $request, string $municipality): Response
    {
        $meet = Meet::query()->published()->active()->firstOrFail();
        $district = $this->resolveMunicipality($meet, $municipality);
        $sportId = $request->integer('sport_id') ?: null;

        $sportOptions = Sport::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($sportId !== null && ! $sportOptions->contains('id', $sportId)) {
            abort(404);
        }

        return Inertia::render('portal/team-players-coaches', [
            'meet' => $this->meetSummary($meet),
            'team' => [
                'id' => $district->id,
                'slug' => Str::slug($district->name),
                'name' => $district->name,
                'logo_url' => $district->logoUrl(),
            ],
            'sportOptions' => $sportOptions,
            'selectedSportId' => $sportId,
            'sports' => $sportId === null ? [] : $this->sportPersonnel($meet, $district, $sportId),
        ]);
    }

    /**
     * The municipalities competing in this meet — one row per registered
     * delegation's municipality, deduplicated (a delegation is rooted at
     * either a district/municipality or, in a City division, a school —
     * both resolve to a municipality here). Same resolution
     * `PortalController::competingMunicipalities()` already uses for the
     * home page's own municipality list, kept here as real `District`
     * models (rather than that method's plain arrays) since this
     * controller needs `logoUrl()`/`congressionalDistrict` on each row,
     * not just id/name/nickname.
     *
     * @return Collection<int, District>
     */
    private function competingMunicipalities(Meet $meet): Collection
    {
        return Delegation::query()
            ->where('meet_id', $meet->id)
            ->with(['district.congressionalDistrict', 'school.district.congressionalDistrict'])
            ->get()
            ->map(fn (Delegation $delegation): ?District => $delegation->district ?? $delegation->school?->district)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Resolves a `{municipality}` route segment to a real, competing
     * `District` for this meet — matched by slugifying each candidate's
     * real name at read time (`District` has no stored `slug` column, and
     * the competing-municipality set is small, tens of rows at most, so
     * this costs nothing extra). 404s on no match, same as any other
     * not-found model route in this app.
     */
    private function resolveMunicipality(Meet $meet, string $slug): District
    {
        $municipality = $this->competingMunicipalities($meet)
            ->first(fn (District $district): bool => Str::slug($district->name) === $slug);

        abort_if($municipality === null, 404);

        return $municipality;
    }

    /**
     * Athlete and sport participation counts per municipality, for this
     * meet — computed from `Entry` (not `Athlete`/`Delegation` directly)
     * since Entry is the one row that already carries both the athlete's
     * real school (→ municipality) and the event's sport in a single
     * relation hop, avoiding a second query per municipality. One query,
     * grouped in PHP — this meet's total entry count is small enough
     * (hundreds, not tens of thousands) that this is cheaper than N
     * separate per-municipality aggregate queries.
     *
     * @return Collection<int|string, array{athlete_count: int<0, max>, sport_count: int<0, max>}>
     */
    private function municipalityParticipation(Meet $meet): Collection
    {
        return SportRosterMember::query()
            ->whereHas('meetSport', fn ($query) => $query->where('meet_id', $meet->id))
            ->with([
                'athlete:id,school_id',
                'athlete.school:id,district_id',
                'delegation:id,district_id',
                'meetSport:id,sport_id',
            ])
            ->get()
            ->filter(fn (SportRosterMember $member): bool => $this->rosterMunicipalityId($member) !== null)
            ->groupBy(fn (SportRosterMember $member): int => $this->rosterMunicipalityId($member))
            ->map(fn (Collection $members): array => [
                'athlete_count' => $members->pluck('athlete.id')->unique()->count(),
                'sport_count' => $members->pluck('meetSport.sport_id')->unique()->count(),
            ]);
    }

    private function rosterMunicipalityId(SportRosterMember $member): ?int
    {
        return $member->athlete?->school?->district_id
            ?? $member->delegation?->district_id;
    }

    /**
     * Prefer the athlete's actual school municipality. Historical imports
     * may have a school without a municipality, so fall back to the
     * delegation municipality and exclude the entry only when neither is
     * available.
     */
    private function entryMunicipalityId(Entry $entry): ?int
    {
        return $entry->athlete?->school?->district_id
            ?? $entry->delegation?->district_id
            ?? $entry->delegation?->school?->district_id;
    }

    /**
     * Athletes and coaches for one municipality, grouped by sport.
     * Athletes are this meet's Confirmed entries whose real school belongs
     * to the municipality (never `Delegation::athletes()` alone, which
     * would miss a City-division municipality's athletes pooled under
     * several school-rooted delegations). Coaches (`App\Models\Personnel`
     * — there is no separate `Coach` model) have no per-meet registration
     * the way athletes do; they're scoped by their own school's
     * municipality plus their real `sports()` assignment instead, filtered
     * to the two coaching `PersonnelRole` cases (Chaperone excluded — not
     * a coaching role).
     *
     * @return array<int, array{sport: string, is_paragames: bool, athletes: array<int, array{name: string, event: string, level: string, category: string, school: string}>, coaches: array<int, array{name: string, role: string, school: string}>}>
     */
    private function sportPersonnel(Meet $meet, District $district, int $sportId): array
    {
        $certifiedCoachSports = CoachOnboardingRequest::query()
            ->where('status', 'approved')
            ->whereHas('meetSport', fn ($query) => $query->where('meet_id', $meet->id))
            ->whereHas('meetSport', fn ($query) => $query->where('sport_id', $sportId))
            ->with(['meetSport.sport:id,name', 'school:id,district_id', 'delegation:id,district_id,school_id', 'delegation.school:id,district_id'])
            ->get()
            ->filter(fn (CoachOnboardingRequest $request): bool => ($request->district_id
                ?? $request->school?->district_id
                ?? $request->delegation?->district_id
                ?? $request->delegation?->school?->district_id) === $district->id)
            ->mapWithKeys(fn (CoachOnboardingRequest $request): array => [
                $request->user_id.'|'.$request->meetSport->sport->name => $request->certification_upload_id === null
                    ? 'Accredited (Without Certificate attached)'
                    : 'Accredited',
            ]);

        $entries = Entry::query()
            ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
            ->where('status', '!=', EntryStatus::Withdrawn->value)
            ->whereHas('event', fn ($query) => $query->where('sport_id', $sportId))
            ->whereHas('athlete.sportRosterMemberships.meetSport', fn ($query) => $query
                ->where('meet_id', $meet->id)
                ->where('sport_id', $sportId))
            ->with([
                'athlete:id,first_name,last_name,school_id',
                'athlete.school:id,name',
                'athlete.eligibilityReview:id,athlete_id,meet_id,status',
                'delegation:id,district_id,school_id',
                'delegation.school:id,district_id,name',
                'event:id,sport_id,name,gender,age_division',
                'event.sport:id,name',
            ])
            ->get()
            ->filter(fn (Entry $entry): bool => $this->entryMunicipalityId($entry) === $district->id);

        $athletesBySport = $entries->groupBy('event.sport.name');

        $personnel = Personnel::query()
            ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
            ->whereIn('role', [PersonnelRole::Coach->value, PersonnelRole::AssistantCoach->value])
            ->whereHas('sports', fn ($query) => $query->whereKey($sportId))
            ->with([
                'school:id,district_id,name',
                'delegation:id,district_id,school_id',
                'delegation.school:id,district_id,name',
                'sports:id,name',
            ])
            ->get()
            ->filter(fn (Personnel $coach): bool => $this->personnelMunicipalityId($coach) === $district->id);

        $personnelCoaches = $personnel
            ->flatMap(fn (Personnel $coach) => $coach->sports->map(fn (Sport $sport): array => [
                'sport' => $sport->name,
                'name' => $coach->fullName(),
                'role' => $coach->role->label(),
                'school' => $coach->school?->name ?? $coach->delegation->registrantName(),
                'status' => $certifiedCoachSports->get($coach->user_id.'|'.$sport->name, 'Registered'),
            ]));

        $approvedAccountCoaches = CoachAssignmentRequest::query()
            ->where('status', 'approved')
            ->whereHas('meetSport', fn ($query) => $query->where('meet_id', $meet->id))
            ->whereHas('meetSport', fn ($query) => $query->where('sport_id', $sportId))
            ->with([
                'user:id,name',
                'meetSport.sport:id,name',
                'school:id,district_id,name',
                'delegation:id,district_id,school_id',
                'delegation.school:id,district_id,name',
            ])
            ->get()
            ->filter(fn (CoachAssignmentRequest $request): bool => $this->coachMunicipalityId($request) === $district->id)
            ->map(fn (CoachAssignmentRequest $request): array => [
                'sport' => $request->meetSport->sport->name,
                'name' => $request->user?->name ?? __('Unknown coach'),
                'role' => 'Coach',
                'school' => $request->school?->name ?? $request->delegation->registrantName(),
                'status' => $certifiedCoachSports->get($request->user_id.'|'.$request->meetSport->sport->name, 'Registered'),
            ]);

        $coachesBySport = $personnelCoaches
            ->merge($approvedAccountCoaches)
            ->unique(fn (array $coach): string => Str::lower($coach['sport'].'|'.$coach['name'].'|'.$coach['school']))
            ->groupBy('sport');

        $sportNames = $athletesBySport->keys()
            ->merge($coachesBySport->keys())
            ->unique()
            ->sort()
            ->values();

        return $sportNames
            ->map(fn (string $sportName): array => [
                'sport' => $sportName,
                // Paragames is a real, seeded Sport-name prefix
                // ('Paragames - Athletics', 'Paragames - Swimming' —
                // `SportsCatalogSeeder`), not an `AgeDivision` case — same
                // convention `MedalTallyService::basePlacements()`'s
                // `$paragames` filter already uses, so a Paragames sport
                // section can be identified/filtered client-side without
                // duplicating a string-matching heuristic on the frontend.
                'is_paragames' => str_starts_with($sportName, 'Paragames'),
                'athletes' => ($athletesBySport->get($sportName) ?? collect())
                    ->map(fn (Entry $entry): array => [
                        'name' => $entry->athlete->fullName(),
                        'event' => $entry->event->name,
                        'level' => $entry->event->age_division->value,
                        'category' => sprintf('%s %s', $entry->event->age_division->label(), $entry->event->gender->label()),
                        'school' => $entry->athlete->school->name,
                        'eligibility_status' => $entry->athlete->eligibilityReview?->status === EligibilityStatus::Approved
                            ? 'Eligible'
                            : ($entry->athlete->eligibilityReview?->status->label() ?? 'Not Submitted'),
                        'is_eligible' => $entry->athlete->eligibilityReview?->status === EligibilityStatus::Approved,
                    ])
                    ->sortBy('name')
                    ->values()
                    ->all(),
                'coaches' => ($coachesBySport->get($sportName) ?? collect())
                    ->map(fn (array $coach): array => [
                        'name' => $coach['name'],
                        'role' => $coach['role'],
                        'school' => $coach['school'],
                        'status' => $coach['status'],
                        'is_accredited' => str_starts_with($coach['status'], 'Accredited'),
                    ])
                    ->sortBy('name')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function personnelMunicipalityId(Personnel $coach): ?int
    {
        return $coach->school?->district_id
            ?? $coach->delegation?->district_id
            ?? $coach->delegation?->school?->district_id;
    }

    private function coachMunicipalityId(CoachAssignmentRequest $request): ?int
    {
        return $request->school?->district_id
            ?? $request->delegation?->district_id
            ?? $request->delegation?->school?->district_id;
    }

    /**
     * The public-safe meet header — identical shape to
     * `PortalController::meetSummary()`, duplicated rather than shared
     * (a few lines, no medal logic) to keep this controller decoupled
     * from `PortalController`'s private surface.
     *
     * @return array<string, mixed>
     */
    private function meetSummary(Meet $meet): array
    {
        return [
            'id' => $meet->id,
            'name' => $meet->name,
            'school_year' => $meet->school_year,
            'starts_at' => $meet->starts_at->format('M j, Y'),
            'starts_at_iso' => $meet->starts_at->toIso8601String(),
            'ends_at' => $meet->ends_at->format('M j, Y'),
            'venue' => $meet->venue,
            'status_label' => $meet->status->label(),
        ];
    }
}
