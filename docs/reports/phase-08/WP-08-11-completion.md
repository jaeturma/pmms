# WP-08-11 — Athletics Live Event UI

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-12 has
not been started.

## Scoping decision (owner-directed)

This WP's reference (`desktop-athletics-live-event.png` — correctly
listed this time, along with the mobile ranking reference already used
by WP-08-09) shows a full live track-meet dashboard: a running race
clock with a lane-by-lane track animation, live per-athlete positions/
times/gaps, live field-event (long jump, shot put) standings, a "Meet
Records" register, weather conditions, and a live-updates activity feed.

Checked what's real before writing any code, confirming and extending
WP-08-01's original flag: `App\Enums\ScoreboardType` has no Athletics
case, so a live session for an Athletics match would fall back to the
generic two-sided scoreboard, which doesn't fit a multi-competitor race
or field event at all. More fundamentally, **no scoring event anywhere
in this app attributes a time or mark to an individual athlete
mid-event** — this app's entire live-scoring data model
(`ScoringSession`/`EventMatch`) is built around two-sided team matches;
Athletics results, like every individual event, are only ever recorded
after the fact through Phase 3's encode→validate flow. "Meet Records"
is a wholly separate historical-records concept that doesn't exist in
any form. This is a bigger structural gap than WP-08-10's basketball
one — not a missing visual style or a missing `sport_state` shape, but
a fundamental mismatch between the reference's multi-competitor race
concept and this app's two-sided match data model.

Presented the owner three options before writing any code: (1) a real
shell using only what's real — schedule, medal totals, validated
results once they exist — with an honest notice that live race tracking
isn't available; (2) new (if modest) backend infrastructure for real
per-athlete athletics results, distinct from the existing `ScoringSession`
model; (3) defer this WP entirely given the scale of the gap. **The
owner chose option 1.** Everything below reflects that; no live clock,
shot clock, field-event live board, or meet-records register was built,
and no new backend data model was introduced.

## What was found and built

New public route `/meets/{meet}/athletics` (`public.athletics`) →
`PortalController::athletics()` → `public/athletics.tsx`. For a selected
day (same day-selector pattern `public/meet.tsx` already established):

- Every Athletics-sport `EventSchedule` slot (found via `whereHas('event',
  ...->where('sport_id', $athleticsSportId))`, the real "Athletics" sport
  row), with a real Upcoming/Ongoing/Completed status — the exact same
  time-window-vs-`now()` derivation `DashboardController::
  eventsOverview()` already established in WP-08-04 (Completed = has a
  validated result; Ongoing = today's slot whose time window contains
  now; Upcoming = everything else), reused rather than reinvented.
- Once an event's result is validated, its real top-3 placements with
  real marks — `EventResult`/`ResultPlacement`, the exact same data
  `/meets/{meet}/results` already shows publicly, just filtered to
  Athletics and attached inline per event instead of on a separate
  results list. An unvalidated (encoded) result correctly shows nothing
  (proven by a dedicated test) — corrections/re-encoding are invisible
  here exactly like everywhere else on the portal.
- A medal-totals summary (Gold/Silver/Bronze/Total `StatCard`s) scoped
  to Athletics only — `MedalTallyService::standings($meetId,
  $athleticsSportId)`, summed across districts, reusing the existing
  service unchanged.
- An explicit `Alert` banner: "Live per-athlete race tracking isn't
  available for Athletics yet. Standings below reflect officially
  validated results only..." — states the real scope plainly rather than
  letting the absence of a live clock/field-event board go unexplained.

Linked from `/meets/{meet}` via a new "Athletics schedule and results"
button — shown only when the meet actually has Athletics events
scheduled (`hasAthletics`, derived from the same `EventSchedule` query
`meet()` already runs, no extra query: `$slots->contains(fn ($slot) =>
$slot->event->sport->name === 'Athletics')`).

## What was deliberately NOT done

- **No live race clock, shot clock, or per-athlete live position/time/
  gap tracking** — no real state exists; a fake counting clock or
  fabricated positions would violate "use actual PMMS data."
- **No live field-event (long jump, shot put, etc.) standings board** —
  same reason; field-event attempts aren't tracked live anywhere.
- **No "Meet Records" register** — a wholly separate feature (historical
  best-ever marks per event) that doesn't exist in any form in this app;
  building it would be new scope well beyond a visual-alignment WP.
- **No weather display** — decorative, no real data source.
- **No live-updates activity ticker** — would need a new public-facing
  event-status-change feed; the real status (Upcoming/Ongoing/Completed)
  is already shown per event instead.
- **No new `ScoringSession`/`ScoreboardType` infrastructure for
  Athletics** — the owner's explicit choice; see the scoping decision
  above.
- **No `PublicMeetNav` tab added for this page** — it isn't one of that
  shared component's three fixed destinations (Schedule/Results/Medal
  Tally) and not every meet has Athletics events; reached via a
  conditional link from the meet page instead, with its own "Back to
  schedule" link, the same discovery pattern the live-scoreboard page
  (WP-07-08) already uses.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache vhost-routing issue noted since WP-08-05; status
  unchanged, still not treated as a blocker.
- Hit the project's own documented gotcha: a brand-new Inertia page
  (`public/athletics.tsx`) needs `npm run build` to run before its
  first page-render test can pass (`Vite manifest` lookup fails
  otherwise) — rebuilt, then all new tests passed.
- Accidentally regenerated Wayfinder routes without the `--with-form`
  flag while adding the new route by hand (`php artisan
  wayfinder:generate` without the flag the Vite plugin normally passes),
  which briefly broke every page using a route's `.form()` helper
  (auth pages, settings). Caught immediately by `tsc --noEmit`;
  regenerated correctly with `--with-form` and confirmed clean. These
  generated directories are gitignored, so no diff was ever at risk —
  noted here only because it's a real gotcha worth remembering for any
  future WP that hand-adds a route.

## Test results

`vendor/bin/pest` — **693/693 passing**, 3,620 assertions (6 new tests
in `PublicAthleticsTest`: guests can view the page and unpublished meets
404; only Athletics-sport events appear, never other sports; a scheduled
event with no validated result shows "upcoming" with no placements; a
validated result shows "completed" with real top-3 placements and marks;
medal totals reflect only Athletics-sport medals; an unvalidated
(encoded) result never appears as a completed placement).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 693/693, 3,620 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `resources/js/pages/public/athletics.tsx`
- `tests/Feature/PublicAthleticsTest.php`
- `docs/reports/phase-08/WP-08-11-completion.md` (this report)

## Files modified

- `routes/web.php` — new `public.athletics` route
- `app/Http/Controllers/PortalController.php` — new `athletics()`
  action; `meet()` gained `hasAthletics`
- `resources/js/pages/public/meet.tsx` — conditional "Athletics
  schedule and results" link
- `docs/public-portal.md` — new page entry recording the scoping
  decision and what was/wasn't built
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-11
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-12.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-12 — Softball and Baseball Live Scoreboard UI, on owner
instruction (per this WP's own rule: do not begin the next work
package). Unlike Athletics, Softball/Baseball already has a real
`ScoreboardType`/`sport_state` foundation (WP-07-06) — this WP should
be closer in shape to WP-08-10's basketball restyle than to this WP's
shell-only outcome, though it's worth re-checking what per-player data
(if any) that WP's reference expects before assuming so.
