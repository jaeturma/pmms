# WP-10-08 — Motion and Interaction Elevation Pass

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template — subtle hover/scroll/transition polish. Plain CSS
only (no Framer Motion — confirmed not installed).

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
.ai/ui-ux-rules.md
.ai/skills/pmms-motion-guidelines.md
docs/ui-ux/premium-design-system.md
docs/phases/phase-10-premium-portal-redesign/README.md
docs/phases/phase-10-premium-portal-redesign/DESIGN-NOTES.md
docs/reports/phase-10/WP-10-07-completion.md
```

## Rules
- Inspect the repository first.
- No Framer Motion, no other new dependency — plain CSS transitions
  only.
- Reuse existing motion tokens (`--ease-premium`,
  `--duration-fast|base|slow`) — do not invent new timing values.
- Confirm, per new class added, that the existing global
  `prefers-reduced-motion` CSS reset (`resources/css/app.css`) covers it
  — by inspection, not assumption.
- Keep motion subtle — no excessive effects.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
No new `@keyframes` unless a genuine gap is found (a hover-lift/shadow
transition is a `transition` utility, not an animation). No Framer
Motion or any other animation library.

## Objective
Add a small, additive layer of micro-interactions using Phase 8.5's
existing motion tokens: a shadow-on-scroll for the new sticky header
(WP-10-02), a hover-lift on schedule/results/sports/news cards, and a
nav-link active-state transition — all plain CSS, all reduced-motion-
safe by construction.

## Acceptance Criteria
- No new dependency.
- Every new transition/animation confirmed covered by the existing
  global reduced-motion reset.
- Motion is subtle, not excessive.
- Visual work is responsive and accessible.
- Tests and quality checks are complete.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-08-completion.md
```

Report repository findings, files modified, motion added, reduced-
motion verification, accessibility, tests, remaining issues,
documentation, git status, and next work package.

Next:
```text
WP-10-09 — Admin Shared-Component Visual Polish Pass
```
