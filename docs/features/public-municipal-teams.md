# Public Municipal Teams / Delegations

A public directory of municipal delegations ("Teams") for the currently
active meet — a per-municipality profile page (medal breakdown, medal
winners, athlete/sport counts) plus a players-and-coaches roster, in the
same visual family as Palarong Pambansa's regional-team pages but built
entirely from PMMS's own data model, branding, and design system. No
HTML/CSS/assets were copied from any external site; it was used only as a
UX/layout reference.

## Routes

| Path | Name | Controller action |
|---|---|---|
| `GET /teams` | `public.teams` | `PortalTeamsController::index` |
| `GET /teams/{municipality}` | `public.teams.show` | `PortalTeamsController::show` |
| `GET /teams/{municipality}/players-coaches` | `public.teams.players-coaches` | `PortalTeamsController::playersCoaches` |

All three are guest-accessible (same `throttle:60,1` group as the rest of
the public portal) and — unlike most portal pages, which take an explicit
`{meet}` route parameter — **meet-agnostic in the URL**: each resolves
internally to `Meet::query()->published()->active()->first()`, the exact
same pattern `PortalController::sportPortal()` already established for the
permanent `/{sportSlug}` routes (Phase 12). This keeps a municipality's URL
stable across meets (`/teams/nabunturan` doesn't change from one meet to
the next) at the same tradeoff the sport-portal routes already accept: a
municipality's page is only reachable while its meet is the currently
*active* one, not merely published. `index()` renders a graceful empty
state (`teams: []`, `meet: null`) rather than 404ing when there's no active
meet; `show()`/`playersCoaches()` 404 on an unknown `{municipality}` slug.

`{municipality}` is **not** constrained via a fixed `whereIn` enum the way
`{sportSlug}` is (12 known, stable values) — the set of competing
municipalities varies per meet and can grow over time, so the parameter is
an unconstrained path segment resolved by slug lookup at request time (see
"Municipality resolution" below).

## Data source: `District` *is* the municipality

There is no separate `Municipality` model in this schema. `App\Models\
District` already plays that role throughout PMMS — see its own docblock
("`district_id` means 'municipality' here") and `docs/division.md`. Don't
confuse it with the finer-grained `App\Models\SchoolDistrict` (a real DepEd
sub-municipal district, e.g. "Laak North" within the municipality "Laak"),
which this feature never surfaces.

A municipality's real congressional district is a separate, genuine model:
`App\Models\CongressionalDistrict`, reached via `District::
congressionalDistrict()`.

### Municipality resolution

`District` has no stored `slug` column. `PortalTeamsController::
resolveMunicipality()` slugifies (`Str::slug()`) each of the current meet's
competing municipalities' real names at read time and matches against the
route segment — cheap and simple since a meet's competing-municipality set
is small (tens of rows, not thousands). `competingMunicipalities()` mirrors
`PortalController::competingMunicipalities()`'s own delegation-to-district
resolution (`Delegation::district ?? Delegation::school?->district` — a
delegation is rooted at either a school, in a City division, or a district/
municipality directly, in a Province division; see `docs/delegations.md`),
kept as a private duplicate in this controller (real `District` models,
not that method's plain arrays) rather than shared, since it's a handful of
lines and this controller is meant to stay decoupled from `PortalController
`'s private surface.

### Athletes and coaches: reached via school, never via `Delegation` alone

A municipality's athletes are `Entry` rows (this meet's Confirmed
registrations) whose real school belongs to the municipality — **never**
`Delegation::athletes()` by itself, which would miss a City-division
municipality's athletes pooled under several school-rooted delegations.
Coaches (`App\Models\Personnel` — there is no separate `Coach` model; see
`PersonnelRole::Coach`/`AssistantCoach`/`Chaperone`, Chaperone excluded as a
non-coaching role) have no per-meet registration the way athletes do, so
they're scoped by their own school's municipality plus their real
`sports()` many-to-many assignment instead.

## Medal logic

All medal aggregation reuses `App\Services\MedalTallyService` — the same
authoritative, read-time-derived service (`event_results.status =
'validated'` only, rank 1/2/3 = gold/silver/bronze, `is_tie` shares a rank
across multiple placements) that powers `/meets/{meet}/tally`. **No medal
logic is duplicated.** Two additions, both extending `basePlacements()`
with two new optional trailing parameters (`?int $districtId = null, ?bool
$paragames = null`) rather than a second query builder — every pre-existing
call site (`standings()`, `medalsBySport()`, `recentMedals()`,
`topMedalists()`) is unaffected since the new parameters default away:

- **`municipalityMedalBreakdown(int $meetId, int $districtId): array`** —
  Elementary/Secondary/Paragames/Total gold/silver/bronze/total counts for
  one municipality. `total` is unfiltered (meet + municipality only), so it
  always equals elementary + secondary + paragames combined; elementary and
  secondary each explicitly exclude Paragames-sport placements so a
  Paragames medal is never double-counted into a second tab.
- **`municipalityMedalWinners(int $meetId, int $districtId, ?string
  $category = null): array`** — unlike every aggregate method above, this
  returns one row **per medal actually won** (athlete/team name, sport,
  event, level, gender, school), for the "Medal Winners" list. Groups
  placements by `(event_result_id, rank)` so a team-event medal renders as
  one card, not N duplicate rows — see "Team-event medals" below.

### Category-tab mapping: Elementary / Secondary / Paragames / Total

`AgeDivision` (`App\Enums\AgeDivision`) has exactly two cases, `Elementary`
and `Secondary` — **there is no `Paragames` case anywhere in this schema.**
Paragames is real, but modeled as a **Sport-name prefix**:
`SportsCatalogSeeder` seeds `'Paragames - Athletics'` and `'Paragames -
Swimming'` as literal `Sport` rows. The Paragames tab/filter is therefore a
`Sport.name LIKE 'Paragames%'` check (`str_starts_with()` on the frontend
copy in `PortalTeamsController::sportPersonnel()`, a SQL `LIKE` in
`MedalTallyService::basePlacements()`), not an `age_division` filter — and
because a Paragames event still carries a real (required, non-nullable)
`age_division` value, Elementary/Secondary explicitly exclude Paragames-
sport placements so nothing is ever counted under two tabs at once.

### Team-event medals: N tied placements, not one "team" record

There is **no single "team" record anywhere in this schema** for a team-
sport medal. A team roster is built from individually-Confirmed `Entry`
rows (`MatchRosterController::store()`, capped at 15 players per side) —
`Entry.athlete_id` is required and never null, so there's no placeholder
"team entry" either. A team's medal is recorded as **N individual
`ResultPlacement` rows sharing one `event_result_id` and `rank`**, tied
together via the pre-existing `is_tie` flag (`ResultController::
assertPlacementsValid()` already requires this for any shared rank).
`municipalityMedalWinners()` groups by `(event_result_id, rank)` and, when
`Event::is_team_event` is true, renders the whole group as one card (e.g.
"Compostela Basketball Team", named from the municipality + sport, with the
full roster carried in a separate `roster` array) instead of showing the
same medal N times.

## Public-safe fields only

Every query in this feature follows the same column-scoping discipline
`PortalController` already uses everywhere else — explicit column lists,
never a bare `Athlete::all()`/`Personnel::all()`. Public-safe: athlete
name, event, category/level, school; coach name, role, school. **Never**
exposed: `birthdate`, `lrn` (Learner Reference Number), `photo_upload_id`/
`sports_photo_upload_id`, or `Personnel.phone`/`email` — none of these
fields are selected by any query this feature adds, and `tests/Feature/
PublicTeamsTest.php` has a dedicated test asserting the raw response body
never contains a seeded phone number or email string.

## Component structure

`resources/js/apps/portal/`:

- **Pages** (`pages/portal/`): `teams.tsx`, `team-detail.tsx`,
  `team-players-coaches.tsx` — named to match this app's real Inertia
  page-naming convention (`Inertia::render('portal/teams', ...)` →
  `pages/portal/teams.tsx`, lowercase-kebab, no `Page` suffix), not the
  original brief's literal `TeamsPage.tsx`/`TeamDetailPage.tsx` filenames.
- **Components** (`components/`, flat, `Portal{Thing}` naming — matching
  every existing portal component, not the brief's suggested
  `components/teams/` subfolder): `team-card.tsx` (`PortalTeamCard`),
  `team-hero.tsx` (`PortalTeamHero`), `medal-winner-card.tsx`
  (`PortalMedalWinnerCard`), `player-card.tsx` (`PortalPlayerCard`),
  `coach-card.tsx` (`PortalCoachCard`), `sport-icon.tsx` (`sportIcon()` —
  a portal-local sport→Lucide-icon mapping covering the ~28-sport catalog,
  not just the 12 permanent sport-portal slugs; deliberately not imported
  from the existing `resources/js/components/sports-medal-strip.tsx`
  helper, to keep the portal tree self-contained). Reused as-is, no
  changes needed: `MunicipalityCrest`, `PortalMedalTotalsRow`,
  `PortalTabs`, `PortalSectionHeader` (widened `title` from `string` to
  `ReactNode`, backward-compatible, so a sport-icon-plus-label heading
  could render), `PortalSelect`, `PortalEmptyState`, `PortalHero`.
- **Types** (`types/index.ts`): `PortalMunicipalityTeam`,
  `PortalMunicipalityProfile`, `PortalMunicipalityMedals`,
  `PortalMunicipalityMedalBreakdown`, `PortalMedalWinner`,
  `PortalTeamAthlete`, `PortalTeamCoach`, `PortalSportPersonnel`.

### Small municipal seal (Team Detail hero)

`District` has exactly one uploaded logo (`logo_upload_id`) — the "large
logo" and "small upper-right seal" in the hero are the **same image
rendered at two sizes** via `MunicipalityCrest`, not two separate uploads.

## Navigation

`portal-header.tsx`'s nav items array gained a "Teams" entry, pushed
unconditionally (not gated behind the `publicNav` shared prop the way
Schedule/Results/Medal Tally are) since the Teams routes are meet-agnostic
in the URL — same reasoning as the existing "Sports" dropdown.

## Search and filters (all client-side)

Every list this feature shows is already fully loaded on its own page and
scoped to one municipality (small, tens of rows) — there is no server round
-trip for search or filtering anywhere in this feature:

- **Teams index**: filters the loaded municipality list by name
  (`useMemo`).
- **Team Detail**: the Elementary/Secondary/Paragames/Total category tabs
  do round-trip to the server (`router.get(..., { category }, {
  preserveState: true })`) since they change which `medalWinners` rows the
  backend returns — this one is *not* purely client-side, unlike the other
  two pages' search boxes.
- **Players & Coaches**: Search (name/sport/event/school) + Role
  (All/Athletes/Coaches) + Category (All/Elementary/Secondary/Paragames),
  all client-side. Category=Paragames filters whole sport *sections*
  (`is_paragames`, a sport-level fact); Category=Elementary/Secondary
  filters individual *athlete rows* (`level`) and additionally hides
  coaches entirely, since a coach has no per-category record in this
  schema (assigned to a sport, not a specific event/age-division) —
  hidden rather than guessed.

## Cache strategy

**Not implemented.** The original brief suggested caching (teams list 5–15
min, municipality profile 5 min, medal totals 30–60s while active,
players/coaches 5–15 min), but no `Cache::remember()` call exists anywhere
in `PortalTeamsController` as shipped — every request reflects the true
current state of validated results with no invalidation logic to design or
maintain. Left as a documented, deliberate gap (see "Known limitations"
below) rather than added speculatively; a caching layer here would need an
explicit invalidation story (at minimum: bust on result validation/
correction) that hasn't been designed or discussed.

## Testing

`tests/Feature/PublicTeamsTest.php` (15 tests) covers: public
accessibility of all three routes; meet-agnostic active-meet resolution
(a published-but-not-active meet is correctly excluded, matching the
sport-portal precedent); 404 on an unknown municipality slug; real
congressional-district display; validated-results-only medal counting
(an encoded/unvalidated result is provably excluded); Paragames
double-counting prevention; cross-municipality exclusion (another
municipality's medals/athletes/coaches never leak into the requested
one's response); individual medal-winner field correctness; team-event
medal grouping (5 tied placements → 1 card with a 5-name roster); category
-tab filtering; players/coaches sport-grouping with the new `is_paragames`/
`level` fields; and public-safe-fields-only (asserts the raw response body
never contains a seeded coach phone number or email address).

## Known limitations

- **No caching** — see "Cache strategy" above.
- **Meet-agnostic-but-active-only reachability** — a municipality's pages
  stop resolving once its meet is no longer the *active* one (even if
  still published), inheriting the same tradeoff the Phase 12 sport-portal
  routes already accept. A meet-scoped `/meets/{meet}/teams/...` variant
  was considered and rejected in favor of matching the clean-URL precedent
  already established for `/{sportSlug}`.
- **No pagination** — every list (municipalities, medal winners, sport
  sections, athletes/coaches within a sport) renders in full. Acceptable
  at this app's real scale (tens of municipalities, dozens of athletes per
  municipality); would need revisiting if that scale changes materially.
- **Coaches have no category dimension** — see "Search and filters" above;
  this is a real modeling fact (`Personnel` is assigned to a `Sport`, not a
  specific `Event`/age-division), not an oversight, but it does mean the
  Category filter's behavior for coaches (hide on Elementary/Secondary,
  keep on Paragames/Total) needs to be understood rather than assumed
  symmetric with the athlete-row filtering.
- **No dedicated sport-icon assets** — `sportIcon()` maps a handful of
  sport-name substrings to existing Lucide icons and falls back to a
  generic `Trophy` for everything else in the ~28-sport catalog; this is a
  decorative approximation, not a claim about a sport's real equipment.
