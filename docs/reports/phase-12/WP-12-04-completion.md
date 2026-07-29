# WP-12-04 — Completion Report

Generalize to the Remaining 11 Sports. Status: **done**.

## Repository findings

Confirmed the route/controller layer (WP-12-02) was already fully
generic — `SportPortalSlug` covers all 12 sports, and `sportPortal()`/
`sportPortalPoll()` never special-case Basketball anywhere. The only
genuinely unconsumed piece was the frontend `sport-portals.ts` config
scaffolded in WP-12-02: its `terminology.game` value existed but was
never read by the page, so every sport's section headings still said
"Games" regardless of sport. Closed that gap — the actual generalization
work this WP needed.

Confirmed `Str::slug('Sepak Takraw')` → `sepak-takraw` and
`Str::slug('Table Tennis')` → `table-tennis` match `SportPortalSlug`'s
real enum values exactly, letting the new dataset tests derive each
sport's URL from its real `Sport.name` rather than hardcoding both.

## Implementation

- `resources/js/lib/utils.ts` — two new small utilities: `pluralize()`
  (handles the sibilant-ending case, "match" → "matches" — the only
  real terminology values in `sport-portals.ts` are game/match/bout/
  event, all correctly handled) and `capitalize()`.
- `resources/js/pages/public/sport-portal.tsx` — now imports
  `sportPortals` and looks up `sport.slug`'s real `terminology.game`
  value, building "Today's Games"/"Upcoming Matches"/"Completed Bouts"-
  style headings and empty-state copy per sport instead of a hardcoded
  "games" everywhere. No other page content changed.
- No backend change — confirmed via `git diff --stat` showing the
  identical `PortalController.php`/`routes/web.php` line counts as
  WP-12-02/03's own reports.

## Tests

Two new dataset tests added to `tests/Feature/PublicSportPortalTest.php`:
- `'every sport portal resolves its own real sport, isolated from
  every other sport'` — 11 cases (Volleyball through Swimming), each
  proving the route resolves the correct sport and a different sport's
  match never leaks in, the same isolation property WP-12-02 first
  proved for Basketball alone, now proven for every other real route.
- `'the live board type follows the real sport...'` — 5 cases
  confirming `ScoreboardType::forSport()`'s existing Basketball/Boxing/
  Softball-Baseball/Generic split holds correctly through the new
  generic route (Boxing → boxing, Baseball/Softball →
  softball_baseball, Volleyball/Chess → generic) — verified, not
  assumed from WP-12-02's own basketball-only test.

## Quality gate

- Pest: **766/766** passed, 4,565 assertions (+16 tests, +194
  assertions over WP-12-03's baseline of 750/4,371).
- Pint: clean, no changes needed.
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: only the same 2 pre-existing, unrelated drifted registry
  files — this WP's own changes needed no reformatting.
- `npm run build`: clean.

## Documentation

- `docs/public-portal.md` — extended the Phase 12 section with a
  "Generalized to the remaining 11 sports" paragraph.
- `docs/phases/phase-12-lightweight-sport-mini-portals/CHECKLIST.md` —
  WP-12-04 checked off.

## Remaining issues

`scoringType`/`supportsStandings`/`supportsLeadingScorers`/
`supportsBracket` in `sport-portals.ts` remain unconsumed by the shell
— intentional, not an oversight: real per-sport score-shape adaptation
for the four sports that don't fit the generic team-score list shape
(Athletics, Swimming — event/heat-based, no side score; Boxing —
already has its own dedicated board; Chess — rank-only, no live score
in the usual sense) is WP-12-05's own explicit scope. Chrome extension
remains unavailable this session (checked again) — no live-browser
verification possible; every claim rests on source inspection and the
passing test suite, as every WP in this phase has stated plainly.

## Git status

`git diff --stat` against `app`/`routes` is unchanged from WP-12-02/03
(0 new lines) — confirming this WP's generalization work was entirely
frontend + tests, no backend change needed. New/changed files:
`resources/js/lib/utils.ts` (modified), `sport-portal.tsx` (already
untracked from WP-12-02, further edited), plus the 2 new dataset tests
in the already-untracked test file. No migration, no dependency. Not
committed, per rule.

Next: **WP-12-05 — Sport-Specific Exceptions (Athletics, Swimming,
Boxing, Chess)**, awaiting owner instruction.
