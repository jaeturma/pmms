# Public Municipal Teams / Delegations — Implementation Report

Status: **core functionality complete (Stages 1–7 of the owner's own
7-stage plan), not committed**.

## Stage summary

1. **Inspection** — read the actual domain model before writing anything;
   corrected several of the original brief's assumptions (no `Municipality`
   model — `District` already is one; no `Paragames` `AgeDivision` case;
   no `Coach` model — `Personnel`; a team-sport medal has no single "team"
   record). Reported to the owner before implementing; three explicit
   decisions were requested and answered before Stage 2 began (Paragames
   handling, route scoping, team-event medal representation).
2. **Data contract** — `MedalTallyService` extended (not duplicated), new
   `PortalTeamsController`, 3 new routes, new portal types, minimal but
   fully working pages so the contract could be exercised end-to-end
   immediately rather than built blind ahead of any UI.
3. **Teams Index polish** — `PortalTeamCard`, client-side search.
4. **Team Detail polish** — `PortalTeamHero` (large + small-seal logo
   layout), `PortalMedalWinnerCard` (icon + text-labeled medal badges).
5. **Players & Coaches polish** — `sportIcon()`, `PortalPlayerCard`/
   `PortalCoachCard`, client-side Search + Role + Category filters.
6. **Responsive & accessibility review** — found and fixed a real mobile
   layout bug (see below); verified heading hierarchy, focus indicators,
   and no-horizontal-overflow across all three pages at 375–390px.
7. **Tests and documentation** — this report, `docs/features/public-
   municipal-teams.md`, and the final quality-gate run below.

Each stage was verified (full gate + live browser check against real
DdOPAA Meet 2026 data) before the owner explicitly approved moving to the
next — no stage was started without that go-ahead.

## Real findings during implementation

- **Domain model corrections** (full detail in `docs/features/public-
  municipal-teams.md`): `District` = municipality, Paragames = a `Sport`-
  name prefix not an `AgeDivision` case, `Personnel` = coaches (no `Coach`
  model), team-sport medals = N tied `ResultPlacement` rows (no single
  "team" record anywhere in the schema).
- **`isRallySetsState`-style type-guard risk avoided by construction** —
  n/a to this feature; no shape-based frontend type guards were introduced
  here (Inertia props are already page-scoped, not a shared discriminated
  union the way `sport_state` is for live scoring).
- **Wayfinder nested-route TypeScript gap**: `public.teams` /
  `public.teams.show` / `public.teams.players-coaches`'s generated runtime
  `Object.assign` merge (`teams.show(...)`) works, but the exported const's
  static TypeScript type doesn't reflect the merged properties. Fixed by
  importing the submodule's named exports directly (`import { show,
  playersCoaches } from '@/routes/public/teams'`) rather than the merged
  namespace object.
- **`react-hooks/static-components` lint rule, map-callback-scoped vs.
  top-level-scoped**: a `PortalSportIcon` wrapper component that computed
  `const Icon = sportIcon(sport)` then rendered `<Icon/>` in its own
  top-level render body failed this rule ("Cannot create components during
  render"), even though the exact same pattern already passes in
  `sports-medal-strip.tsx` — because there it's computed *inside a `.map()`
  callback*, not the enclosing component's own body. Fixed by dropping the
  wrapper component and inlining the icon lookup inside `team-players-
  coaches.tsx`'s own `.map()` over sport sections, matching the passing
  precedent's structure exactly.
- **Real mobile bug (Stage 6)**: at ~390px, the Players & Coaches page's
  Search + Role + Category filter row (`flex flex-wrap`, search at
  `flex-1`) squeezed the search input to a barely-usable width once the
  two `<select>`s claimed their content width — not an overflow, just
  badly cramped. Fixed by restructuring to `flex-col sm:flex-row` (search
  gets its own full-width row below `sm`, filters sit together below it).
- **Browser-tooling gotcha**: `resize_window` doesn't change the CDP-
  captured viewport on this machine (`window.innerWidth` stayed desktop-
  sized after a reported-successful resize). Worked around by injecting a
  same-origin `<iframe>` at the target mobile dimensions into a blank tab
  and screenshotting that — a real, independent browsing context whose
  `@media` queries respond correctly to its own explicit width/height.

## What changed

- `app/Services/MedalTallyService.php` — `basePlacements()` gained two new
  optional trailing parameters (`?int $districtId`, `?bool $paragames`);
  every pre-existing call site is unaffected. New public methods:
  `municipalityMedalBreakdown()`, `municipalityMedalWinners()`.
- `app/Http/Controllers/PortalTeamsController.php` — new. `index()`,
  `show()`, `playersCoaches()`, plus private helpers
  (`competingMunicipalities()`, `resolveMunicipality()`,
  `municipalityParticipation()`, `sportPersonnel()`, `meetSummary()`).
- `routes/web.php` — 3 new routes (`public.teams`, `public.teams.show`,
  `public.teams.players-coaches`), meet-agnostic, mirroring the Phase 12
  sport-portal route pattern.
- `resources/js/apps/portal/types/index.ts` — 7 new exported types.
- `resources/js/apps/portal/pages/portal/{teams,team-detail,team-players-
  coaches}.tsx` — new.
- `resources/js/apps/portal/components/{team-card,team-hero,medal-winner-
  card,player-card,coach-card,sport-icon}.tsx` — new.
- `resources/js/apps/portal/components/section-header.tsx` — `title` prop
  widened from `string` to `ReactNode` (backward-compatible; every
  existing caller still passes a plain string).
- `resources/js/apps/portal/layout/portal-header.tsx` — "Teams" nav item.
- `tests/Feature/PublicTeamsTest.php` — new, 15 tests.
- `docs/features/public-municipal-teams.md`,
  `docs/reports/public-municipal-teams-implementation.md` — this pair.

## Tests

`tests/Feature/PublicTeamsTest.php`, 15 tests / 182 assertions — see
`docs/features/public-municipal-teams.md`'s "Testing" section for the full
list of what's covered.

## Quality gate

- **Pest**: 1327/1327 passed, 6,757 assertions (up from 1312/6,575 before
  this feature's first commit-worthy state — 15 new tests, 182 new
  assertions).
- **Pint**: clean on every file this feature touched.
- **PHPStan**: 0 errors on `PortalTeamsController.php` and the extended
  `MedalTallyService.php` (one `Collection<TValue>` non-covariance issue
  during Stage 2 fixed via a precise PHPStan-syntax `int<0, max>` docblock
  annotation, not a cast or a widened type).
- **`tsc --noEmit`**: clean.
- **ESLint**: clean, scoped to this feature's own files. (A broader
  `eslint resources/js/apps/portal` run surfaces ~15 pre-existing issues
  in files this feature never touched — confirmed via `git status` and
  correctly left alone, not this feature's responsibility.)
- **`npm run build`**: succeeds.
- Live-browser-verified at every stage against real DdOPAA Meet 2026 seed
  data (not just factory-seeded test data) — including the team-medal
  grouping ("Compostela Basketball Team"), category-tab filtering, search/
  role/category filtering on Players & Coaches, and mobile layout at
  375–390px via the iframe technique above.

## Documentation

- `docs/features/public-municipal-teams.md` — the reference doc: routes,
  data source, medal logic, category-tab mapping, privacy rules, component
  structure, cache strategy (explicitly not implemented), testing, known
  limitations.
- `docs/reports/public-municipal-teams-implementation.md` — this report.

## Remaining issues / explicitly out of scope

- **No caching layer** — see `docs/features/public-municipal-teams.md`'s
  "Cache strategy" section. The original brief suggested TTLs for each
  page; none were added, since a correct cache-invalidation story (at
  minimum: bust on result validation/correction) wasn't designed or
  discussed, and adding `Cache::remember()` calls without one would risk
  silently stale medal data — a worse outcome than the current always-
  fresh, uncached reads.
- **No pagination** anywhere in this feature (municipality list, medal
  winners, sport sections, athlete/coach rows) — acceptable at this app's
  real scale, flagged as a limitation rather than silently assumed safe
  forever.
- Acceptance criteria 21–22 from the owner's brief ("existing admin
  interface untouched", "public portal design system reused") are
  self-evidently satisfied by the diff (no admin files touched; every new
  component either reuses or extends an existing `apps/portal` component)
  rather than separately re-verified by a dedicated test.

## Git status

**Not committed**, per the standing "never commit without explicit
instruction" rule. All files listed under "What changed" above are new or
modified in the working tree, on top of the already-pushed `e99060c`
commit. Awaiting owner review and an explicit "commit this"/"push it"
instruction.
