# WP-10-07 — New Public Pages: Sports, News, Contact

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template — card-based, structured presentation using the
premium components already established in Phase 8.5/this phase.

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
.ai/ui-ux-rules.md
docs/ui-ux/premium-design-system.md
docs/ui-ux/shared-components.md
docs/public-portal.md
docs/phases/phase-10-premium-portal-redesign/README.md
docs/phases/phase-10-premium-portal-redesign/DESIGN-NOTES.md
docs/reports/phase-10/WP-10-06-completion.md
```

## Rules
- Inspect the repository first.
- This is the only WP in this phase touching the backend — keep it
  minimal: three new read-only routes/controller actions, reusing
  `Meet::published()` scoping and existing models exactly as every
  other public route already does.
- No office-contact content is invented — Contact shows real meet/venue
  data and quick links only (resolved decision).
- Sports must link into real existing data (`results`/`tally` filtered
  by `sport_id`, both already accept it) — not a static dead-end list.
- News reuses the existing `Announcement` model and its `published()`
  scope — drop the current 5-item preview limit, paginate the full list.
- New pages are reachable from the header nav and the new footer's
  quick-links column only — **not** added to `PublicBottomNav`
  (resolved decision).
- No new dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Gallery (deferred, out of scope for this phase). Any office-contact
field or content. Any change to `PublicBottomNav`'s item count.

## Objective
Add three new public pages built from real, already-existing PMMS data:

- **Sports** (`/meets/{meet}/sports` or similar) — a card grid of the
  meet's contested sports (from the existing `Sport`/`Event` catalog),
  each linking into `results`/`tally` pre-filtered by that sport.
- **News** (`/meets/{meet}/news` or similar) — the full, paginated list
  of published announcements (reusing `Announcement::published()`),
  where the current home-page strip only shows a 5-item preview.
- **Contact** (`/meets/{meet}/contact` or similar) — the active meet's
  venue and school-year info plus quick links to the rest of the
  portal; no fabricated office-contact section.

## Acceptance Criteria
- Existing business logic, routes, and authorization untouched — only
  new, additive routes/controller actions.
- Every route scoped through `Meet::published()`, matching every
  existing public route.
- Actual PMMS data used — no hardcoded/invented content.
- No private/admin data exposed on any new page.
- Visual work is responsive and accessible.
- Reduced-motion behavior works.
- Tests added for the three new routes/actions (public-safe fields,
  publication scoping, 404 on unpublished meets — matching existing
  `PublicResultsTest`/`PublicScoreboardTest` conventions).
- Full quality gate green.
- Documentation updated (`docs/public-portal.md`, navigation docs).
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-07-completion.md
```

Report repository findings, files created/modified, backend changes,
visual/frontend changes, reusable components, responsive behavior,
accessibility, privacy verification, tests, remaining issues,
documentation, git status, and next work package.

Next:
```text
WP-10-08 — Motion and Interaction Elevation Pass
```
