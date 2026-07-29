# WP-11-07 — 404 Page Visual Elevation

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion

## Visual Direction
Arena's spacing/typography/card rhythm, applied to the existing guest
404/error page.

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
resources/js/pages/error.tsx
bootstrap/app.php
docs/public-portal.md (Accessibility & mobile review section, WP-04-06)
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
docs/reports/phase-11/WP-11-01-completion.md
```

## Rules
- Inspect the repository first.
- Visual/spacing pass only — `error.tsx`'s existing guest-vs-
  authenticated logic (layout switch, "Back to portal home" vs. "Back
  to dashboard" link, 403/404-specific copy) is functionally correct
  since WP-04-06 and must not change.
- No new route, no new controller logic, no new dependency.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any change to `bootstrap/app.php`'s exception handling; any change to
which link/copy renders for guest vs. authenticated users;
`PublicBottomNav` (unchanged).

## Objective
Give the guest 404/error page the same breathing room and card-grid
discipline as every other page this phase and Phase 10 touched —
spacing, typography scale, and (if applicable) an icon treatment
consistent with `EmptyState`'s existing pattern — without altering its
already-correct functional behavior.

## Acceptance Criteria
- `error.tsx`'s guest/authenticated branching behavior unchanged
  (verified against WP-04-06's existing tests).
- No data/prop/route change.
- Responsive and accessible; reduced-motion behavior works.
- Full quality gate green (existing error-page tests still pass
  unmodified).
- Documentation updated if visual convention changes are worth noting.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-11/WP-11-07-completion.md
```

Next:
```text
WP-11-08 — Navigation and Footer Integration for New Pages
```
