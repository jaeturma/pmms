# WP-10-01 — Arena Reference Audit and Composition Mapping

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template (https://uicookies.com/demo/theme/arena/) — layout,
spacing, composition, and user-experience inspiration only. Never its
HTML, CSS, colors, fonts, or branding.

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
.ai/ui-ux-rules.md
.ai/skills/pmms-premium-design-system.md
.ai/skills/pmms-public-portal-experience.md
docs/ui-ux/premium-design-system.md
docs/ui-ux/shared-components.md
docs/phases/phase-08-5-premium-sports-experience/
docs/phases/phase-10-premium-portal-redesign/README.md
docs/phases/phase-10-premium-portal-redesign/DESIGN-NOTES.md
```

## Rules
- Inspect the repository first.
- Do not copy Arena's HTML, CSS, colors, fonts, or branding.
- Do not import Bootstrap or any new dependency.
- Preserve PMMS's existing color palette, backend, routes, controllers,
  authorization, and all business logic.
- No code changes in this WP — audit and documentation only.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Gallery, a separate Rankings route, a new Tabs primitive, any new
dependency — all already resolved as out of scope for this phase
(README.md/DESIGN-NOTES.md).

## Objective
Map Arena's real design elements (hero composition, sticky nav with a
CTA, monospace live-countdown styling, structured cards with consistent
aspect ratios, three-column footer, grid-based section rhythm) against
PMMS's actual current state — page by page, component by component —
recording what's already equivalent (a lot, thanks to Phase 8.5) versus
a real, concrete gap each later WP in this phase will close. This is the
shared vocabulary every later WP in this phase points back to, the same
role WP-08.5-01 played opening Phase 8.5.

## Acceptance Criteria
- Repository inspected first.
- No code changes.
- The mapping is concrete (real file/component names), not generic
  adjectives.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-01-completion.md
```

Report repository findings, files created/modified, the composition
mapping itself (or a pointer to where it lives), remaining issues, git
status, and next work package.

Next:
```text
WP-10-02 — Public Shell Rebuild: Sticky Nav and Real Footer
```
