# WP-11-03 — Static Gallery Page

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion

## Visual Direction
Arena's card-grid rhythm, applied to sport-identity tiles (no photo
pipeline exists — see DESIGN-NOTES for why this is not photographic).

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/public-portal.md
resources/js/components/team-logo.tsx
resources/js/components/sports-medal-strip.tsx
resources/js/pages/public/sports.tsx
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
docs/reports/phase-11/WP-11-01-completion.md
```

## Rules
- Inspect the repository first.
- No `Photo`/media model, no migration, no upload path — frontend-only.
- No fabricated/stock event photography implying real meet moments —
  use icon/initial-based sport-identity tiles (reuse `sportIcon()` from
  `sports-medal-strip.tsx`, exported already for WP-10-07's Sports page).
- Real data drives which tiles render: the meet's actual contested
  sports (`Meet::events()`, same relation `sports()` already queries) —
  not a static hardcoded list.
- One new read-only route/controller action
  (`GET /meets/{meet}/gallery`), scoped through `Meet::published()`.
- No new dependency.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any photo upload/storage capability; any stock or placeholder imagery
implying a real photograph; `PublicBottomNav` (unchanged).

## Objective
Add `/meets/{meet}/gallery` (`public.gallery`) — a card grid of the
meet's contested sports as identity tiles (icon + name + real event
count, same data `sports.tsx` already exposes), at Arena's grid rhythm.
Each tile links into `/results`/`/tally` pre-filtered by `sport_id`,
same integration pattern as the Sports page.

## Acceptance Criteria
- New route additive only, read-only, scoped through `Meet::published()`.
- No fabricated content — every tile traces to `Meet::events()`.
- No private/admin data exposed.
- Responsive and accessible; reduced-motion behavior works.
- Tests added (public-safe fields, publication scoping, 404 on
  unpublished meets).
- Full quality gate green.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-11/WP-11-03-completion.md
```

Next:
```text
WP-11-04 — About Page
```
