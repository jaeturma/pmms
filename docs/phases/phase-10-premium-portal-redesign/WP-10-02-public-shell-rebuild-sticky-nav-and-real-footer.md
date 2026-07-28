# WP-10-02 — Public Shell Rebuild: Sticky Nav and Real Footer

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template — layout/composition inspiration only, never its
HTML/CSS/colors/branding. Existing PMMS color tokens unchanged.

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
docs/reports/phase-10/WP-10-01-completion.md
```

## Rules
- Inspect the repository first.
- Reuse existing design tokens (`.bg-premium-hero`, medal/status colors)
  — do not introduce new colors.
- No new dependency.
- Preserve every existing route, `publicNav`/`liveCount` prop, and the
  mobile bottom tab bar's current item count/behavior.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
No new pages (that's WP-10-07). No change to `PublicBottomNav`'s item
count. No new color tokens.

## Objective
Rebuild `PublicLayout`'s header into a sticky nav (desktop/tablet only —
`sm:` and above; stays static below `sm:` since `PublicBottomNav` already
owns mobile navigation, avoiding two fixed bars sandwiching phone
content) and replace the current one-line footer with a real, multi-
column footer component (`PublicFooter`) — brand + real meet info
(venue, school year) + quick links, per the resolved "no invented
office-contact content" decision.

## Acceptance Criteria
- Existing routes, props, and business logic preserved.
- Actual PMMS data used (no placeholder/invented content).
- No overlap or double-scrollbar regression against `PublicBottomNav` on
  mobile, or the sticky header on desktop/tablet.
- Visual work is responsive and accessible.
- Reduced-motion behavior works (if any transition is added, e.g. a
  scroll shadow).
- Tests and quality checks are complete.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-02-completion.md
```

Report repository findings, files created/modified, visual/frontend
changes, reusable components, responsive behavior, accessibility,
tests, remaining issues, documentation, git status, and next work
package.

Next:
```text
WP-10-03 — Home Hero and Landing Composition Elevation
```
