# WP-10-09 — Admin Shared-Component Visual Polish Pass

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena-inspired spacing/typography, applied to the admin's existing
shared components — not a restructure, and never a "public website"
look bleeding into authenticated pages.

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
.ai/ui-ux-rules.md
docs/ui-ux/shared-components.md
docs/phases/phase-10-premium-portal-redesign/README.md
docs/phases/phase-10-premium-portal-redesign/DESIGN-NOTES.md
docs/reports/phase-10/WP-10-08-completion.md
```

## Rules
- Inspect the repository first.
- Elevate the *shared components* (`page-header.tsx`, `empty-state.tsx`,
  `search-bar.tsx`, `confirm-dialog.tsx`, `pagination-controls.tsx`) so
  the ~20 resource pages that compose from them inherit the change —
  do not edit individual resource pages unless a genuine one-off gap is
  found.
- Reuse existing tokens/colors — no new colors.
- Spot-check representative pages after (a table-heavy page, a
  dialog-form page, a full-page-form page, a print-relevant report
  page) rather than checking all ~20 individually.
- Verify `@media print` layout (`resources/css/app.css`) is unaffected
  by any spacing change to page chrome.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
No restructuring of any admin page's data/logic. No new form/table
abstraction. No charting library.

## Objective
Elevate the shared admin primitives every resource index/form page
already composes from — spacing, typography hierarchy, and visual
polish on `PageHeader`, `EmptyState`, `SearchBar`, `ConfirmDialog`,
`PaginationControls`, and the `*FormDialog` field convention — so the
improvement propagates consistently across the admin app without
touching ~20 nearly-identical pages individually.

## Acceptance Criteria
- Existing business logic, data, and page structure preserved.
- No new colors introduced.
- Spot-checked across at least 4–5 representative admin pages spanning
  different content shapes.
- Print layout confirmed unaffected.
- Visual work is responsive and accessible.
- Tests and quality checks are complete.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-09-completion.md
```

Report repository findings, files modified, visual changes, which pages
were spot-checked and why, responsive behavior, accessibility, tests,
remaining issues, documentation, git status, and next work package.

Next:
```text
WP-10-10 — Admin Sidebar and Dashboard Visual Polish
```
