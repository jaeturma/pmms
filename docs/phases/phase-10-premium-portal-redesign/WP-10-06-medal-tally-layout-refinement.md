# WP-10-06 — Medal Tally Layout Refinement

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template — large, spacious ranking presentation. Existing
medal colors (gold/silver/bronze + foreground tokens) unchanged.

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
.ai/ui-ux-rules.md
docs/ui-ux/premium-design-system.md
docs/ui-ux/shared-components.md
docs/phases/phase-10-premium-portal-redesign/README.md
docs/phases/phase-10-premium-portal-redesign/DESIGN-NOTES.md
docs/reports/phase-10/WP-10-05-completion.md
```

## Rules
- Inspect the repository first.
- Rankings stays folded into this page — do not add a separate
  `/rankings` route (resolved decision).
- Reuse existing components (`MedalCells`/`MedalHeader`,
  `MedalDistributionCard`, `TopByPointsCard`, `MedalsBySportCard`,
  `SportsMedalStrip`, `RankBadge`) — do not fork or duplicate them.
- Verify the internal admin equivalent (`tally/index.tsx`) does not
  inherit an unintended public-only spacing assumption from any shared
  component touched here.
- Preserve kiosk mode and the official gold→silver→bronze rank
  ordering exactly as computed today.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
No new `/rankings` route. No change to medal colors or the official
ranking computation/order.

## Objective
Elevate `public/tally.tsx`'s composition — spacing and visual rhythm
around the district-standings table, medal distribution card, top-by-
points card, and medals-by-sport card — for a large, premium ranking
presentation, without changing the underlying ranking logic or colors.

## Acceptance Criteria
- Existing ranking computation and order preserved exactly.
- Actual PMMS data used.
- `tally/index.tsx` (admin) confirmed unaffected, or intentionally and
  consistently updated.
- Visual work is responsive and accessible, including kiosk mode.
- Reduced-motion behavior works.
- Tests and quality checks are complete.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-06-completion.md
```

Report repository findings, files modified, visual/frontend changes,
large-display behavior, accessibility, tests, remaining issues,
documentation, git status, and next work package.

Next:
```text
WP-10-07 — New Public Pages: Sports, News, Contact
```
