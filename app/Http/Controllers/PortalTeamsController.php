<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\PersonnelRole;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Sport;
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

    public function playersCoaches(string $municipality): Response
    {
        $meet = Meet::query()->published()->active()->firstOrFail();
        $district = $this->resolveMunicipality($meet, $municipality);

        return Inertia::render('portal/team-players-coaches', [
            'meet' => $this->meetSummary($meet),
            'team' => [
                'id' => $district->id,
                'slug' => Str::slug($district->name),
                'name' => $district->name,
                'logo_url' => $district->logoUrl(),
            ],
            'sports' => $this->sportPersonnel($meet, $district),
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
        return Entry::query()
            ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
            ->with(['athlete:id,school_id', 'athlete.school:id,district_id', 'event:id,sport_id'])
            ->get()
            ->groupBy(fn (Entry $entry): int => $entry->athlete->school->district_id)
            ->map(fn (Collection $entries): array => [
                'athlete_count' => $entries->pluck('athlete.id')->unique()->count(),
                'sport_count' => $entries->pluck('event.sport_id')->unique()->count(),
            ]);
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
    private function sportPersonnel(Meet $meet, District $district): array
    {
        $entries = Entry::query()
            ->whereHas('delegation', fn ($query) => $query->where('meet_id', $meet->id))
            ->whereHas('athlete.school', fn ($query) => $query->where('district_id', $district->id))
            ->where('status', '!=', EntryStatus::Withdrawn->value)
            ->where(function ($query) use ($meet): void {
                $query->where('status', EntryStatus::Confirmed->value)
                    ->orWhereHas('athlete.eligibilityReview', fn ($reviews) => $reviews
                        ->where('meet_id', $meet->id)
                        ->where('status', EligibilityStatus::Approved->value));
            })
            ->with([
                'athlete:id,first_name,last_name,school_id',
                'athlete.school:id,name',
                'event:id,sport_id,name,gender,age_division',
                'event.sport:id,name',
            ])
            ->get();

        $athletesBySport = $entries->groupBy('event.sport.name');

        $personnel = Personnel::query()
            ->whereHas('school', fn ($query) => $query->where('district_id', $district->id))
            ->whereIn('role', [PersonnelRole::Coach->value, PersonnelRole::AssistantCoach->value])
            ->with(['school:id,name', 'sports:id,name'])
            ->get();

        $personnelCoaches = $personnel
            ->flatMap(fn (Personnel $coach) => $coach->sports->map(fn (Sport $sport): array => [
                'sport' => $sport->name,
                'name' => $coach->fullName(),
                'role' => $coach->role->label(),
                'school' => $coach->school->name,
            ]));

        $approvedAccountCoaches = CoachAssignmentRequest::query()
            ->where('status', 'approved')
            ->whereHas('meetSport', fn ($query) => $query->where('meet_id', $meet->id))
            ->whereHas('school', fn ($query) => $query->where('district_id', $district->id))
            ->with(['user:id,name', 'meetSport.sport:id,name', 'school:id,name'])
            ->get()
            ->map(fn (CoachAssignmentRequest $request): array => [
                'sport' => $request->meetSport->sport->name,
                'name' => $request->user->name,
                'role' => 'Coach',
                'school' => $request->school->name,
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
                    ])
                    ->sortBy('name')
                    ->values()
                    ->all(),
                'coaches' => ($coachesBySport->get($sportName) ?? collect())
                    ->map(fn (array $coach): array => [
                        'name' => $coach['name'],
                        'role' => $coach['role'],
                        'school' => $coach['school'],
                    ])
                    ->sortBy('name')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
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
