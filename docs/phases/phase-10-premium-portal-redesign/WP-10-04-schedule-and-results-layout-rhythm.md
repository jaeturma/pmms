# WP-10-04 — Schedule and Results Layout Rhythm

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template — structured cards with consistent proportions and
generous spacing, translated to PMMS's real content (a schedule slot, a
podium result) rather than literal sports-club match cards.

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
docs/reports/phase-10/WP-10-03-completion.md
```

## Rules
- Inspect the repository first.
- Reuse existing tokens/components (`PodiumDisplay`, `LiveBadge`,
  `--animate-card-in`, `PublicMeetNav`) — no new components unless a
  genuine gap is found.
- Preserve all existing routes, filters, and data.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
No real `Tabs` primitive for `PublicMeetNav` (deferred, per
DESIGN-NOTES.md). No change to the underlying schedule/results data or
filtering logic.

## Objective
Apply consistent card spacing/proportions to `public/meet.tsx`
(schedule) and `public/results.tsx` — each schedule slot and each
event's podium/results block should read as a structured, evenly-
proportioned card, with generous rhythm between them, matching Arena's
card-based composition using PMMS's own real data.

## Acceptance Criteria
- Existing business logic, routes, and filters preserved.
- Actual PMMS data used.
- Visual work is responsive and accessible.
- Reduced-motion behavior works.
- Tests and quality checks are complete.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-04-completion.md
```

Report repository findings, files modified, visual/frontend changes,
responsive behavior, accessibility, tests, remaining issues,
documentation, git status, and next work package.

Next:
```text
WP-10-05 — Live Scoreboard and Countdown Composition Refinement
```
