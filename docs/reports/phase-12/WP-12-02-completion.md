# WP-12-02 — Completion Report

Shared Sport-Portal Shell and Components. Status: **done**.

## Repository findings

Confirmed `matches` has no bracket-tree structure and `EventResult` has
no `match_id` (per WP-12-01's own findings, re-verified before writing
code). Confirmed `ScoringSessionController::board()`'s "suggested
labels" pattern (`entries[i]->athlete->school->name`, used pre-live)
as the real source for a match's competitor names before any scoring
session exists — reused exactly for Today's/Upcoming games rather than
inventing a different resolution. Confirmed `EventMatch` has no stored
`winner`/final-score field of its own — the final score always comes
from the match's own (possibly ended) `ScoringSession`, derived at
render time, never stored redundantly.

**Real gotcha hit and fixed while writing tests**: `Meet::factory()->
active()` sets the meet's **lifecycle status** (`MeetStatus::Active`),
a completely different concept from the `is_active` boolean
`Meet::scopeActive()` actually queries (the single "featured on the
public landing page" flag, set only by the factory's separate
`featured()` state). Every test in this file initially used `->active()
->published()->create()` (matching the exact pattern this whole
session used for every meet-scoped route in Phase 11) and failed with
"meet.name does not exist" — because `sportPortal()` is the **first**
route in this project to actually call `Meet::scopeActive()` itself
(every other public route resolves a specific meet by ID instead).
Fixed by adding `->featured()` to every test's meet factory call,
matching the exact chain `PublicPortalTest.php`'s own `home()` tests
already use (`->active()->published()->featured()->create()`) — this
distinction was previously invisible because nothing else in this
phase's own test suite needed `Meet::scopeActive()` to resolve
correctly.

## Implementation

- `app/Enums/SportPortalSlug.php` — new, 12 cases (slug ↔ real
  `Sport.name`), constraining the route parameter and giving
  `PortalController` a typed way to resolve a slug.
- `routes/web.php` — two new additive routes: `GET {sportSlug}` (named
  `public.sport-portal`) and `GET {sportSlug}/poll` (named
  `public.sport-portal.poll`), both `whereIn(SportPortalSlug::values())`
  — confirmed via `route:list` they register correctly and, by
  construction, can never intercept any other top-level route (no
  existing route path matches any of the 12 slug strings).
- `app/Http/Controllers/PortalController.php` — new `sportPortal()`/
  `sportPortalPoll()` public methods plus private helpers
  (`sportPortalData()`, `sportPortalLiveNow()` — shared by both the
  full page load and the poll endpoint so the "what's live" logic has
  one definition, `sportPortalGameRow()`, `matchCompetitors()`,
  `mapsSearchUrl()`). No existing method touched.
- `resources/js/config/sport-portals.ts` — new, frontend-only
  presentational config (12 sports, terminology/scoring-type labels,
  `supportsStandings`/`supportsLeadingScorers`/`supportsBracket` all
  honestly `false`) — not yet consumed by the shell (WP-12-04/05's own
  scope), scaffolded now per the brief's own required file structure.
- `resources/js/components/sport-portal-game-list.tsx`,
  `sport-portal-venue-info.tsx`, `sport-portal-unavailable.tsx`,
  `sport-portal-live-now.tsx` — new shared components.
- `resources/js/pages/public/sport-portal.tsx` — new shared page,
  composing all of the above; renders an honest "no active meet" empty
  state when `Meet::scopeActive()` resolves nothing.
- `npm run build` rerun to regenerate Wayfinder's route helpers
  (`sportPortal`/`poll`, gitignored, generated).

## Tests

New `tests/Feature/PublicSportPortalTest.php` (10 tests): route
resolution + unknown-slug 404, no-active-meet empty state, a live match
appearing as `liveNow` with a real session payload, multiple
simultaneous live matches counting the extras, an ended session never
appearing as live, Today's/Completed/Upcoming classification with real
competitor names and a real venue, cross-sport exclusion, cross-meet
exclusion, the poll endpoint's JSON shape, and a `missing()`-style
field-shape check on game rows (no `birthdate`/`lrn`/`grade_level`).

## Quality gate

- Pest: **747/747** passed, 4,337 assertions (+10 tests, +147
  assertions over WP-11's final baseline of 737/4,190).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: 3 of this WP's own new files needed formatting (import
  order/wrapping) — fixed via a **targeted** `prettier --write` on
  those 3 paths, not a blanket run; the same 2 pre-existing, unrelated
  drifted registry files remain untouched.
- `npm run build`: clean, rerun once more after the formatting pass.

## Documentation

- `docs/public-portal.md` — new "Sport mini portals (Phase 12)"
  section.
- `docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md` —
  WP-12-02 checked off.

## Remaining issues

None from this WP's own scope. Not yet linked from the header nav/
footer (a smaller, later decision, per `DESIGN-NOTES.md`); not yet
validated end-to-end for a real basketball scenario beyond automated
tests (that's WP-12-03's own scope); visibility-aware polling not yet
built (WP-12-06's own scope — the current poll is a plain 7s interval).

## Git status

`git diff --stat` against `app`/`routes` shows exactly the expected
additive changes: `PortalController.php` (+253 lines, 0 removed),
`routes/web.php` (+11 lines, 0 removed), plus new untracked
`SportPortalSlug.php` and all the frontend files listed above. No
migration, no dependency, no existing route/controller/page touched.
Not committed, per rule.

Next: **WP-12-03 — Basketball Reference Implementation**, awaiting
owner instruction.
