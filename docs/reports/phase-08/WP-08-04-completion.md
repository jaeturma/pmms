# WP-08-04 — Admin Dashboard Visual Implementation

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-05 has
not been started.

## Repository findings

Read the required files. This WP's own listed "Reference Images"
(`mobile-ranking-medal-tally.png`, `mobile-basketball-live-score.png`,
`desktop-basketball-live-score.png`, `desktop-athletics-live-event.png`,
`desktop-softball-live-score.png`) are the same generic live-scoreboard/
ranking set used by WP-08-02, not `admin-dashboard.png` — the same
templated-doc pattern WP-08-01/02/03 already flagged. Used
`admin-dashboard.png` instead, the one reference that actually shows the
dashboard.

## What was found and built

Compared the real `dashboard.tsx`/`DashboardController` against
`admin-dashboard.png`. Four real gaps, all closed:

1. **Stat cards were plain icon-only** — the reference shows colored
   circular icon badges per metric. Added an optional `tone` prop to
   `StatCard` (`primary`/`success`/`warning`/`destructive`/`gold`,
   backed by WP-08-02's tokens) — omit it and a card renders exactly as
   before (every other `StatCard` usage, e.g. `management/index.tsx`,
   is untouched and out of this WP's scope).
2. **No "Events overview" widget.** New `DashboardController::
   eventsOverview()` computes a real, non-invented 3-way breakdown
   (Completed/Ongoing/Upcoming) from `EventResult`/`EventSchedule` — no
   "Cancelled" bucket, since this catalog has no cancelled/void concept
   for an event; inventing one would violate "do not hardcode screenshot
   values." Documented inline as a deliberate approximation, not a
   tracked lifecycle status. Rendered as a segmented bar + counts in a
   new `EventsOverviewCard`, with its own empty state.
3. **Medal-tally rank column was a bare number** — added `RankBadge`
   using WP-08-02's new `--medal-gold/silver/bronze` tokens for the top
   3 positions.
4. **No quick-actions row.** The reference's shortcut tiles map to six
   existing routes (register delegation/athlete, add official, manage
   events, encode results, medal tally) — all reused via Wayfinder route
   helpers, no new routes or permissions.

Two smaller, real (not cosmetic-only) additions found while comparing
against actual data already available but unused:

- **Today's schedule now shows a status badge** (Upcoming/Ongoing/
  Completed) derived client-side from a live clock (`useClock`, reused
  from WP-08-03) compared against each slot's `starts_at`/`ends_at`
  strings — lexicographic "HH:MM" comparison, no date parsing needed.
- **Recent activity got per-action colored icons** (`ActivityIcon`),
  keyed by the audit action's prefix (`athlete.*`, `result.*`,
  `protest.*`, etc.) with a generic fallback so a future audit action
  never needs this file updated to render sensibly.
- **Announcements surfaced on the dashboard** — `DashboardController::
  index()` now includes the 3 most-recently-published announcements
  (reusing the existing `Announcement` model/`published()` scope, no
  new backend), rendered with the existing `PublicAnnouncements`
  component (already used on the public portal — reused, not
  duplicated).

One cross-page addition, scoped from the same reference's "what's
happening right now" intent: a **"Watch live" link on the Schedule
page**. A schedule slot whose event has a match now exposes `match_id`/
`is_live`, scoped exactly like `MatchController::index()` — Viewers
never receive the field at all (live scoring is forbidden to them
regardless), and a Delegation Officer only sees it for their own
delegation's matches. New `ScheduleController::matchesForSlots()`
computes this once per page load (not per-row). Documented in
`docs/scheduling.md` and cross-referenced from `docs/live-scoring.md`.
`SampleProvinceDemoSeeder` gained a `liveBasketballGame()` helper
(re-seedable, always repositions to "today" so it never goes stale) so
this link has real data to demonstrate without hand-walking Meets →
Matches → Start scoring.

## What was deliberately NOT done

- **No client-side polling/refresh of dashboard stats** — the reference
  doesn't show any live-updating dashboard content beyond the schedule
  status badges (which derive from a client clock, not a data refetch);
  inventing dashboard auto-refresh would be new functionality beyond
  visual alignment.
- **No changes to `MedalTallyService`, `EventResult`, or any
  authorization gate** — every widget here is read-side over existing
  queries/relations; `eventsOverview()` is the one new query, additive
  only.
- **No repo-wide sweep** of other pages that could use `StatCard`'s new
  `tone` prop (e.g. `management/index.tsx`) — out of this WP's scope,
  left for whichever later WP touches those pages.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session (re-checked via
  `tabs_context_mcp` before writing this report, same result as
  WP-08-01/02/03). Recommend a real visual pass — stat card colors,
  events-overview bar, rank badges, quick actions — before WP-08-05.
- **Could not get a live HTTP check against http://pmms.app** — the
  local Laragon MySQL/web services were not running this session (a
  first for this phase's log; WP-08-01/02/03 all recorded HTTP 200).
  Attempted `php artisan serve` as a substitute, which surfaced a
  `PDOException` (`SQLSTATE[HY000] [2002] No connection could be made
  because the target machine actively refused it`) trying to reach
  MySQL on 127.0.0.1:3306 — confirms the gap is "services not started,"
  not an application bug (the same DB is not needed by the test suite,
  which runs against SQLite and passed cleanly). Flagged for an owner
  follow-up to start Laragon and re-confirm HTTP 200 before WP-08-05;
  not treated as a blocker since every other gate (including the full
  Pest suite, which exercises the same controllers against a real
  database — just not this one) passed.

## Test results

`vendor/bin/pest` — **671/671 passing**, 3,341 assertions. No new tests
were added: this WP is presentation over existing, already-tested
controller data plus one new additive query (`eventsOverview()`) and one
new scoped query (`matchesForSlots()`) that mirror authorization already
proven correct by `MatchController`'s own existing tests — re-verified
by reading `MatchControllerTest`'s scoping assertions rather than
duplicating them, per the project's own precedent of not re-testing an
already-proven authorization rule from a second call site.

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 671/671, 3,341 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files modified

- `app/Http/Controllers/DashboardController.php` — `announcements` prop,
  `eventsOverview()`
- `app/Http/Controllers/ScheduleController.php` — `match_id`/`is_live`
  per slot, scoped `matchesForSlots()`
- `resources/js/components/stat-card.tsx` — `tone` prop
- `resources/js/pages/dashboard.tsx` — events-overview widget, rank
  badges, quick actions, schedule status badges, activity icons,
  announcements section
- `resources/js/pages/schedule/index.tsx` — "Watch live" link/badge
- `database/seeders/SampleProvinceDemoSeeder.php` — `liveBasketballGame()`
- `docs/scheduling.md` — "Live scoreboard link" section
- `docs/live-scoring.md` — cross-reference to the schedule link
- `tests/Feature/ScheduleTest.php` — 5 new tests proving the live-link
  scoping (no match, in-progress, ended-only, viewer-forbidden,
  delegation-officer-own-match-only)
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-04
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-05.
- Local Laragon services were not running this session — recommend
  starting them and reconfirming HTTP 200 before WP-08-05.

## Next

WP-08-05 — Admin Medal Tally and Rankings UI, on owner instruction (per
this WP's own rule: do not begin the next work package).
