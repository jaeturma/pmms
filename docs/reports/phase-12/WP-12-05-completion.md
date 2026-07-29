# WP-12-05 — Completion Report

Sport-Specific Exceptions (Athletics, Swimming, Boxing, Chess). Status:
**done**.

## Repository findings

Re-read `PortalController::athletics()` in full before writing any
code and confirmed the load-bearing fact this whole WP hinges on: it
already reads Athletics purely from `EventSchedule` (for the schedule)
and validated `EventResult` (for completed placements) — **it never
touches `EventMatch` at all**, because Athletics events don't fit the
two-sided "match" concept and no scoring session is ever created for
them in real usage. Swimming has no existing precedent page in this
app but shares the identical real shape (individual, heat/event-based,
no side score) — treated identically.

Confirmed Boxing and Chess are both genuinely head-to-head (exactly 2
`Entry` rows per match), so the generic `EventMatch`-based shape
WP-12-02 already built fits them correctly with **zero functional
change needed** — re-verified rather than assumed:
- `ScoringSession::boardType()` already resolves `'boxing'` for a
  Boxing match automatically (same mechanism proven for Basketball in
  WP-12-03), and `LiveScoreDisplay`'s `isBoxingState()`/round-history
  table already renders whatever `sport_state.rounds` a session
  carries — no new frontend code needed, only a test proving it.
- Chess matches marked `Completed` without ever having a
  `ScoringSession` (the realistic case, since chess results normally go
  through the encode→validate `EventResult` flow like every individual
  event) already render with `score_a`/`score_b`/`mark` all `null` —
  `SportPortalGameList`'s existing null-checks already avoid fabricating
  a score; confirmed with a dedicated test rather than left as an
  assumption.

**Real, load-bearing gap confirmed and closed**: Athletics/Swimming
routed through the generic `EventMatch`-based path would have shown
"nothing scheduled, nothing completed, nothing upcoming" forever for
these two sports — not fabricating anything, but also completely
missing the real schedule/result data that does exist via a different
query path. This is the one genuine "the data model makes reuse
impossible" case the WP's own rules anticipated.

## Implementation

- `app/Http/Controllers/PortalController.php`:
  - `sportPortalData()` now branches: `Athletics`/`Swimming` (a new
    `INDIVIDUAL_EVENT_SPORTS` const) route to a new
    `individualEventSportPortalData()`; every other sport keeps the
    exact match-based path unchanged.
  - `individualEventSportPortalData()` — real `EventSchedule` (today/
    upcoming) + validated `EventResult` (completed, top-1 placement
    only) queries, mirroring `athletics()`'s own status-derivation
    logic exactly rather than inventing a different one.
  - `individualEventGameRow()` — builds a `SportPortalGame` row with
    `side_a` = the real winner's name+school once validated (`null`
    before then — no fabricated "TBD vs TBD"), `side_b` always `null`
    (no opposing side concept), and the new `mark` field carrying the
    placement's real recorded mark.
  - `sportPortalGameRow()` (match-based path) now always includes
    `'mark' => null`, keeping the row shape identical across both code
    paths.
- `resources/js/components/sport-portal-game-list.tsx` — `SportPortalGame`
  type gained `mark: string | null`; the participant line now renders
  nothing at all when `side_a` is `null` (no fabricated placeholder),
  and renders just the winner + mark (no "vs") when `side_b` is `null`
  — the individual-event shape — while the existing two-sided "vs"/
  score line is completely unchanged for match-based sports.
- No new dependency, no new migration.

## Tests

4 new tests added to `tests/Feature/PublicSportPortalTest.php`:
- A dataset test (Athletics + Swimming) proving a real upcoming event
  shows no fabricated competitor (`side_a`/`side_b` both `null`) and a
  real completed event shows the actual winner's name+school and mark
  (`score_a` still `null` — never a fabricated numeric score), with
  `liveNow` correctly `null` since no `EventMatch` exists for either
  sport.
- Boxing's live `sport_state.rounds` flowing through to the JSON payload
  unchanged, mirroring WP-12-03's Basketball fouls test.
- Chess with a completed match and no live session never fabricates a
  score (`score_a`/`score_b`/`mark` all `null`), while still showing
  the real pre-live competitor names.
- **Real correction to an existing WP-12-04 test**: the "every sport
  portal resolves its own real sport" dataset originally included
  Athletics/Swimming using an `EventMatch`-based fixture — now
  structurally invalid for these two sports (they no longer read
  `EventMatch` at all). Removed them from that dataset with an
  explanatory comment rather than leaving a now-incorrect test in
  place; their real behavior is covered by the new dedicated test
  instead. Also updated the existing "game rows carry no internal
  fields" test to include the new `mark` field in its `hasAll()` list.

## Quality gate

- Pest: **768/768** passed, 4,624 assertions (net +2 tests over
  WP-12-04's baseline of 766/4,565 — 4 new tests added, 2 outdated
  dataset cases removed).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: `sport-portal-game-list.tsx` (this WP's own file)
  reformatted via a **targeted** `prettier --write` on that one path;
  the same 2 pre-existing, unrelated drifted registry files remain
  untouched.
- `npm run build`: clean, rerun after the formatting/doc-comment pass.

## Documentation

- `docs/public-portal.md` — new "Sport-specific exceptions closed
  (WP-12-05)" paragraph.
- `docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md` —
  WP-12-05 checked off.

## Remaining issues

None found that require further work. `scoringType`/`supportsX` config
flags remain honestly `false`/unconsumed for score-shape purposes
beyond what this WP built. Chrome extension remains unavailable this
session (checked again) — no live-browser verification possible;
every claim rests on source inspection and the passing test suite, as
every WP in this phase has stated plainly.

## Git status

`git diff --stat` against `app`/`routes` shows `PortalController.php`
growing from 253 to 379 additive lines (the two new private methods);
`routes/web.php` unchanged (11 lines, same as WP-12-02). No migration,
no dependency. Not committed, per rule.

Next: **WP-12-06 — Performance and Visibility-Aware Polling**, awaiting
owner instruction.
