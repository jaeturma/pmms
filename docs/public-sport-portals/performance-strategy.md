# Public Sport Portals — Performance Strategy

Matches the original brief's own §17 acceptance criteria (3, 4, 5, 6, 7, 8,
9), verified against real code rather than assumed:

## One featured live event, not a full live feed

`sportPortalLiveNow()` resolves at most one featured match (plus a count of
"other live" matches for the same sport) — never the full live-match list.
The same query runs on the initial page load and on every `/poll`.

## Game lists capped at 10, ordered

`sportPortalData()`'s Today's/Completed/Upcoming queries each end in
`->limit(10)` with a real `orderBy`, proven with a 12-candidate-row test
(WP-12-03) rather than left as an assumption from a single-row test.

## Leading scorers top-5 cap

Not applicable — Leading Scorers has no backing data for any sport
(`data-contract-map.md` §E) and renders `SportPortalUnavailable`
unconditionally, so no list exists to cap.

## No bracket diagram, lazy-loaded or otherwise

Not applicable for the same reason (§F) — Bracket renders
`SportPortalUnavailable`, not a lazy-loaded diagram. No
bracket-diagramming dependency was added or considered further.

## No heavy embedded map

`SportPortalVenueInfo` renders venue name/address as text plus a plain
external link (`https://www.google.com/maps/search/?api=1&query=...`) built
from stored text fields — no embedded map iframe/SDK, no new dependency.

## Data isolation — no cross-sport fetching

`sportPortalData()`'s queries all filter `whereHas('event', fn ($q) =>
$q->where('sport_id', $sport->id))` (or the equivalent for
`individualEventSportPortalData()`) — a match/event belonging to any other
sport never appears. Proven for all 12 sports via a dataset test
(`'games from a different sport never appear on this sport's portal'` plus
the 9-case "resolves its own real sport, isolated from every other sport"
dataset in `tests/Feature/PublicSportPortalTest.php`), not just for the
Basketball pilot.

## Visibility-aware polling (brief §9)

Both polls pause while the browser tab is hidden, via the new
`usePageVisible()` hook (`document.visibilitychange`-based):

- **Live Now** (`SportPortalLiveNowCard`): fetches `GET /{sportSlug}/poll`
  every 7s (within the brief's own 5-10s band) while visible; the effect's
  own cleanup clears the interval whenever `visible` flips to `false`, so
  pausing/resuming needs no separate stop/start bookkeeping.
- **Game lists/venues** (`sport-portal.tsx`): background-refreshes via
  `router.reload({ only: ['todayGames', 'completedGames', 'upcomingGames',
  'venues'] })` every 45s while visible — one shared interval for all four
  props, not four separate timers, and never a different sport's data or a
  full page re-render (`only` scopes the reload).

**Known, honestly-documented test-coverage limitation** (WP-12-06): this
project has zero frontend-unit-testing infrastructure (no Vitest/Jest/
Testing Library) and this phase's own "no new dependency" rule forbids
adding one just to simulate `visibilitychange` in a test. Verified instead
by direct source review (`usePageVisible`'s listener registration/cleanup,
and — for the background reload's failure path — reading `@inertiajs/core`'s
actual compiled source to confirm `router.reload()`'s `onError` defaults to
a silent no-op, so a failed background refresh never surfaces as an
uncaught error or a broken UI state).

## No new dependency anywhere in this phase

No charting library, no bracket-diagramming library, no polling library
beyond Inertia's own `router.reload` (already used by `tally.tsx`'s kiosk
mode and `scoreboard.tsx`) — confirmed via an unchanged `package.json`
dependency list across all of WP-12-01 through WP-12-08.
