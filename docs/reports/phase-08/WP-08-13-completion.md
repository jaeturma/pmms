# WP-08-13 — Shared Tables, Cards, Charts, Scoreboards, and Filters

**Status:** Complete 2026-07-27. Per this WP's own rules, WP-08-14 has
not been started.

## Scope interpretation

This WP's own reference-image list is, once again, the same wrong
generic set every other WP in this phase started with (this time with
no sport-specific exception the way WP-08-09/10/11/12 each got their
own correct image) — there is no reference image actually depicting
"shared tables/cards/charts/scoreboards/filters" as a concept. Combined
with the objective's generic template wording, this confirms what the
WP-08-12 report already flagged: this WP is a **consolidation/audit
pass** over the shared components built incrementally across WP-08-04
through WP-08-12, not new visual work against a mockup.

## What was done

Delegated a read-only audit (background Explore agent) across every
page using a `Select`-based filter, the shared `Table` components, and
`StatCard` grids, looking specifically for concrete, real inconsistencies
— not stylistic nitpicks with no visible effect. The audit returned 6
categories of findings; each was verified by hand before acting on it,
since several turned out to be correct-by-context rather than bugs:

**False positives, verified and left alone:**

- "Bare `overflow-x-auto` without a border" on 5 tables
  (`dashboard.tsx`'s two Card-embedded widgets, `MedalsBySportCard`,
  `results/index.tsx` and `public/results.tsx`'s per-event tables) — all
  five sit inside a `<Card>` or a `<section className="rounded-xl
  border">` that already provides the border; adding a second one would
  have been a double-border regression, not a fix.
- "Rank/# column width varies (w-10/w-12/w-16)" — the header *text*
  varies too ("Rank" vs "#"), so different widths are correct
  proportional sizing, not an inconsistency.
- A handful of single-filter pages missing the `flex flex-wrap gap-2`
  wrapper most multi-filter pages use — a single flex child renders
  pixel-identical with or without the wrapper; no visual difference to
  fix.
- Two report pages' filter row using `gap-3 items-center` instead of the
  more common `gap-2` — one of them (`reports/schedule-sheet.tsx`)
  genuinely needs `items-center` (it mixes a date `Input` with a text
  label of a different height in the same row); not worth partially
  "fixing" the other for a 4px gap difference with no other visible
  effect.

**Real inconsistencies, fixed:**

1. `incidents/index.tsx`'s status filter (`w-44`) vs. the identical
   "All statuses" filter pattern everywhere else (`w-56` —
   `protests/index.tsx`, `eligibility/index.tsx`, `results/index.tsx`)
   — normalized to `w-56`.
2. The medal-summary `StatCard` grid (Total Gold/Silver/Bronze/Medals —
   the same four cards, same data) used `lg:grid-cols-4` on the internal
   admin tally page (`tally/index.tsx`, WP-08-05) but `sm:grid-cols-4` on
   the two public pages built afterwards (`public/tally.tsx`,
   `public/athletics.tsx`, WP-08-08/09) — the same widget looked
   different on a tablet-width screen depending on which page you were
   on. Normalized `tally/index.tsx` to `sm:grid-cols-4` to match the more
   recently/deliberately chosen breakpoint (WP-08-09's report explicitly
   reasoned about phone-width readability when choosing it).
3. Four `EmptyState` call sites (`accreditation/index.tsx` and
   `reports/delegation-roster.tsx`'s athlete/personnel panels, 2 each)
   omitted a `description`, rendering visibly shorter/plainer than every
   sibling empty state on the same pages. Added the exact wording
   `athletes/index.tsx`/`personnel/index.tsx` already established
   ("Registered athletes will appear here." / "Registered coaches and
   chaperones will appear here."), rather than inventing new phrasing.

Also reconfirmed (no change needed): `dashboard.tsx`'s two distinct
`StatCard` grids and `management/index.tsx`'s matching one are a
genuinely different widget category (operational queue counts, not
medal cards) already consistent with each other — forcing them into the
medal-card breakpoint would have been wrong. `MedalDistributionCard`'s
donut, `dashboard.tsx`'s events-overview segmented bar, and
`TopByPointsCard`'s bar list already share one visual language (same
track/dot classes) despite being three different chart types — no
further extraction needed; merging a donut and a horizontal bar into one
component would be cosmetic DRY, not a real improvement.
`scoring/show.tsx` and `public/scoreboard.tsx` both still render
exclusively through the shared `LiveScoreDisplay`, no local
reimplementation found in either.

New `docs/ui-ux/shared-components.md` — a catalog of every shared
presentational component built across Phase 8 (which file, which WP
first built it, which pages use it), plus the audit findings above, so
a future WP doesn't need to re-run this audit from scratch.

## What was deliberately NOT done

- **No new component extractions** — the audit found the existing
  extraction points (done on each component's real second use site, per
  the project's "no premature abstraction" rule) already cover the
  genuine duplication; nothing new crossed that threshold this pass.
- **No changes to the false-positive findings listed above** — each was
  verified correct-by-context before being left alone, not skipped for
  lack of time.
- **No visual/functional changes to any live scoreboard, medal tally, or
  public portal page beyond the specific fixes listed** — this WP is a
  consistency audit, not a new round of feature work on any one page.

## Verification

- `npx tsc --noEmit` — 0 errors.
- Full quality gate green (below).
- All fixes are pure `className`/prop additions with no backend or
  logic changes, so no new Pest tests were needed; existing tests for
  every touched page (`AccreditationTest`, `IncidentTest`,
  `MedalTallyTest`, `ReportTest`) re-run and confirmed unaffected.
- **Could not get a live visual screenshot** — Claude in Chrome
  extension still disconnected this session.
- **Could not get a live HTTP check against http://pmms.app** — same
  unresolved Apache vhost-routing issue noted since WP-08-05; status
  unchanged, still not treated as a blocker.

## Test results

`vendor/bin/pest` — **695/695 passing**, 3,640 assertions (no new tests
— pure presentational consistency fixes with no new behavior to cover;
existing coverage for every touched page re-run and confirmed green).

## Quality results

| Check | Result |
|---|---|
| `vendor/bin/pint --test` | Passed |
| `vendor/bin/phpstan analyse` (level 7) | Passed, 0 errors |
| `vendor/bin/pest` | Passed, 695/695, 3,640 assertions |
| `npx eslint .` | Passed, 0 errors |
| `npm run format:check` | Passed |
| `npx tsc --noEmit` | Passed, 0 errors |
| `npm run build` | Passed |

## Files created

- `docs/ui-ux/shared-components.md`
- `docs/reports/phase-08/WP-08-13-completion.md` (this report)

## Files modified

- `resources/js/pages/incidents/index.tsx` — status filter width
- `resources/js/pages/tally/index.tsx` — medal-card grid breakpoint
- `resources/js/pages/accreditation/index.tsx` — 2 `EmptyState`
  descriptions
- `resources/js/pages/reports/delegation-roster.tsx` — 2 `EmptyState`
  descriptions
- `docs/phases/phase-08-ui-ux-visual-alignment/CHECKLIST.md` — WP-08-13
  checked off

## Remaining issues

- Chrome extension still unavailable — recommend a real visual check
  before WP-08-14.
- The pmms.app Apache vhost routing issue (noted since WP-08-05) is
  still unresolved.

## Next

WP-08-14 — Responsive Mobile Tablet and Large Display Alignment, on
owner instruction (per this WP's own rule: do not begin the next work
package). Likely another cross-cutting pass rather than new-page work,
similar in shape to this WP — worth confirming scope against its own
reference images (if any are actually relevant this time) before
assuming.
