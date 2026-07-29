# WP-11-02 — Completion Report

Rankings Page (Split from Medal Tally). Status: **done**.

## Repository findings

Confirmed `MedalTallyService::standings()` (`app/Services/
MedalTallyService.php:41-111`) already computes the exact
gold/silver/bronze/total/points/`position` shape `tally.tsx`'s "Overall
ranking" table renders, via its private `ordered()` helper — no new
computation was needed, only a new destination for the same data.
Confirmed `PortalController::tally()`'s existing pattern (`Meet::
published()->findOrFail()`, `meetSummary()`) as the template to reuse
exactly.

Checked `PublicMeetNav` (`resources/js/components/public-meet-nav.tsx`)
before deciding where Rankings should link from: it's reserved for the
"core meet trilogy" (Schedule/Results/Medal tally — `docs/public-
portal.md`'s own words), and Phase 10's Sports/News/Contact pages
deliberately never joined it, only the header nav/footer. Followed that
same precedent — Rankings is not added to `PublicMeetNav`; it's linked
from the Medal Tally page directly (a "Full rankings" button) and will
join the header nav/footer in WP-11-08, per the phase's own sequencing
rule.

## Implementation

- `routes/web.php` — one new additive route, `GET /meets/{meet}/
  rankings` → `PortalController::rankings`, named `public.rankings`,
  same `whereNumber('meet')` constraint as every other public meet
  route.
- `app/Http/Controllers/PortalController.php` — new `rankings()` method:
  `Meet::published()->findOrFail()`, then `$tally->standings($meet->id)`
  unfiltered (no sport/age param, matching the WP's "no new aggregate"
  rule), returning `meet`/`districts`/`generatedAt` only.
- `resources/js/pages/public/rankings.tsx` — new page: `PublicPageHero`,
  a full (non-paginated, non-8-row-collapsed — this page's whole point
  is the complete standings) districts table reusing `MedalHeader`/
  `MedalCells`/`RankBadge`, an `EmptyState` for zero medals, and an
  `Alert` linking back to Medal Tally for the sport/school breakdown.
- `resources/js/pages/public/tally.tsx` — added one "Full rankings"
  button next to the existing "Kiosk / TV mode" button; its own
  "Overall ranking" table, filters, kiosk mode, and every other section
  are untouched, per the WP's explicit rule.
- `npm run build` rerun to regenerate Wayfinder's `resources/js/routes/
  public/index.ts` with the new `rankings()` helper (gitignored,
  generated — confirmed via `git status` it doesn't appear as a
  tracked change).

## Tests

New `tests/Feature/PublicRankingsTest.php` (4 tests, matching
`PublicSportsTest`/`PublicTallyTest` conventions): guest access +
unpublished-meet 404, validated-only ranking derivation, cross-meet
exclusion, and a `missing()`-style field-shape check (`position`/
`district`/`gold`/`silver`/`bronze`/`total`/`points` only — no
`created_at`/`updated_at`).

## Quality gate

- Pest: **718/718** passed, 3,932 assertions (+4 tests, +54 assertions
  over WP-11-01's baseline of 714/3,878).
- Pint: 1 file auto-fixed (import order in the new test file).
- PHPStan (level 7): 0 errors.
- ESLint (`--fix`): 0 issues, no files changed.
- `tsc --noEmit`: clean.
- Prettier: `tally.tsx` (this WP's own file) reformatted via a
  **targeted** `prettier --write` on that one path — not a blanket
  `--write` across `resources/`, per the WP-09-01 lesson about not
  reformatting unrelated files. Two pre-existing, unrelated drifted
  files (`registry/school-districts.tsx`, `registry/schools.tsx`)
  remain untouched — confirmed via `git status` they were never part of
  this WP's diff.
- `npm run build`: clean.

## Documentation

- `docs/public-portal.md` — new Rankings page entry in the Pages
  section.
- `docs/medal-tally.md` — new "Standalone Rankings page (Phase 11,
  WP-11-02)" section.
- `docs/phases/phase-11-public-portal-completion/CHECKLIST.md` — WP-11-02
  checked off.

## Remaining issues

None. Rankings is not yet reachable from the header nav/footer or
`PublicBottomNav` — expected, per the phase's own sequencing (WP-11-08
wires all five new pages in together, after they all exist).

## Git status

`git diff --stat` against `app`/`routes`/`resources` shows exactly 3
files changed (`PortalController.php`, `routes/web.php`, `tally.tsx`),
plus the new untracked `rankings.tsx` and test file — no migration, no
dependency manifest touched. Not committed, per rule.

Next: **WP-11-03 — Static Gallery Page**, awaiting owner instruction.
