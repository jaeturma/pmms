# WP-10-03 — Home Hero and Landing Composition Elevation

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template — layout/composition inspiration only. Existing
`.bg-premium-hero` gradient unchanged — this WP is spacing/rhythm, not a
new visual treatment.

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
docs/reports/phase-10/WP-10-02-completion.md
```

## Rules
- Inspect the repository first.
- Keep `.bg-premium-hero`'s gradient definition exactly as-is — do not
  add new colors or a new background treatment.
- `PublicPageHero` is shared by `home.tsx`, `tally.tsx`, and
  `athletics.tsx` — any change to the component itself must be verified
  on all three consumers.
- Preserve all existing data/props (`meet`, `currentLeaders`,
  `upcomingEvents`, `latestResult`, `closingSummary`, `municipalities`).
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
No new hero background treatment (resolved: spacing only, not a new
CSS accent). No new components beyond spacing/layout adjustments to
existing ones.

## Objective
Widen the vertical rhythm of `public/home.tsx` — more generous spacing
between the hero, quick actions, live-now section, the current-leaders/
upcoming-events/latest-result highlights row, and the competing-
municipalities grid — so the page reads as spacious and premium rather
than compact/admin-dense, matching Arena's grid-based breathing room
without changing any color or introducing new components.

## Acceptance Criteria
- Existing business logic and data preserved.
- Actual PMMS data used.
- `PublicPageHero`'s other two consumers (`tally.tsx`, `athletics.tsx`)
  verified unaffected or intentionally and consistently updated.
- Visual work is responsive and accessible.
- Reduced-motion behavior works.
- Tests and quality checks are complete.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-03-completion.md
```

Report repository findings, files modified, visual/frontend changes,
responsive behavior, accessibility, tests, remaining issues,
documentation, git status, and next work package.

Next:
```text
WP-10-04 — Schedule and Results Layout Rhythm
```
