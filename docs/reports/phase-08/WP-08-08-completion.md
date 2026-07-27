# WP-08-08 — Public Medal Tally and Rankings Page

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-09 has
not been started.

## Repository findings

Read the required files. This WP's own reference-image list was again
the wrong generic set — `public-medal-tally.png` (already identified as
the correct reference in WP-08-07's report) is what this WP's content
was checked against; it's the only public-facing reference image, and
this page is exactly what it depicts.

Read `public/tally.tsx`, `PortalController::tally()`, `MedalTallyService`,
`tally/index.tsx` (the internal admin page WP-08-05 already rebuilt
against this same reference), and `docs/public-portal.md`'s binding
"public controllers build their own minimal prop arrays" rule before
changing anything.

## What was found and built

The public tally page was a bare-bones version of what the internal
admin tally page looked like *before* WP-08-05 — no points, no donut,
no summary cards, no by-sport breakdown, no branded header. Since
`public-medal-tally.png` is the same reference WP-08-05 already
implemented against for the internal page, and none of WP-08-05's
additions are privacy-sensitive (aggregate medal counts and points, no
different in kind from the medal counts already shown publicly), this
WP ports that same visual/functional treatment to the public page
rather than reinventing it.

**Backend** (`PortalController::tally()`): added `totals`
(gold/silver/bronze/total summed from the district rows),
`topByPoints` (top 5 districts by points), `bySport`
(`MedalTallyService::medalsBySport()`), `recentMedals`
(`MedalTallyService::recentMedals()`), and an `age_division` filter
(`AgeDivision::tryFrom()`, same defensive pattern as the sport filter)
— all direct calls into the same `MedalTallyService` methods WP-08-05
added for the internal page. No new backend logic was written; this WP
only wires the public controller to what already exists.

**Shared components, extracted on their second use** (the exact
"shared rendering, independent props" pattern `live-score-display.tsx`
already established for live scoring in WP-07-08): `tally/index.tsx`'s
five widgets — `MedalDistributionCard`, `TopByPointsCard`,
`MedalsBySportCard`, `MedalCells`/`MedalHeader` — moved out to
`resources/js/components/` so both the internal and public pages render
from one implementation instead of two copies that could drift apart.
`RankBadge` was already shared (WP-08-05). `public/tally.tsx` was then
rebuilt using these components plus the new backend data, and adopts
`PublicPageHero` (WP-08-07) for its title band instead of a plain `<h1>`
— the branded-hero component's first real second use, as WP-08-07's
report anticipated.

**Found and fixed a real, pre-existing duplication bug** while touching
this file: `tally/index.tsx` had a local `pluralize()` function
identical to `pluralizeAreaLabel()`, which already existed in
`@/lib/utils` and was already used by `app-sidebar.tsx` and
`registry/districts.tsx` — WP-08-05 apparently reinvented it instead of
reusing the existing utility. Replaced the local copy with the shared
one. Also extracted `recentDescription()` (the "+N in the last 24
hours" `StatCard` description formatter) to `@/lib/utils` alongside it,
since it's now needed by both tally pages too.

## What was deliberately NOT done

- **No new `MedalTallyService` logic** — every number on this page was
  already computed by WP-08-05's methods; this WP only exposes them
  publicly.
- **No changes to the school-level reference table's semantics** — still
  district-first/official, school-below/reference-only, matching the
  same rule the internal page and WP-08-05 both preserved.
- **No `PublicPageHero` adoption on `public/results.tsx`,
  `public/meet.tsx`, or `public/scoreboard.tsx`** — out of this WP's
  scope (medal tally specifically); those pages' own restyle, if any,
  belongs to a different WP.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache-vhost-routing issue noted since WP-08-05; status
  unchanged, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **683/683 passing**, 3,507 assertions (2 new tests
in `PublicTallyTest`: the page exposes `totals`/points/`bySport`/
`recentMedals` correctly for a real placement; the age-division filter
narrows results the same way the internal page's does). Every existing
`PublicTallyTest` case re-run and unaffected; `MedalTallyTest` (internal
page) re-run and unaffected by the component extraction.

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 683/683, 3,507 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `resources/js/components/medal-table-parts.tsx` (`MedalCells`/
  `MedalHeader`, extracted from `tally/index.tsx`)
- `resources/js/components/medal-distribution-card.tsx` (extracted)
- `resources/js/components/top-by-points-card.tsx` (extracted)
- `resources/js/components/medals-by-sport-card.tsx` (extracted)
- `docs/reports/phase-08/WP-08-08-completion.md` (this report)

## Files modified

- `app/Http/Controllers/PortalController.php` — `totals`/`topByPoints`/
  `bySport`/`recentMedals`/`age_division` filter on `tally()`
- `resources/js/pages/public/tally.tsx` — full rebuild using the shared
  widgets and `PublicPageHero`
- `resources/js/pages/tally/index.tsx` — now imports the extracted
  widgets instead of defining them locally; local `pluralize()`
  replaced with the existing `pluralizeAreaLabel()`
- `resources/js/lib/utils.ts` — `recentDescription()` added
- `tests/Feature/PublicTallyTest.php` — 2 new tests
- `docs/medal-tally.md`, `docs/public-portal.md` — WP-08-08 sections
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-08
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-09.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-09 — Mobile Ranking and Medal Tally UI, on owner instruction (per
this WP's own rule: do not begin the next work package).
