# WP-11-06 — Public Portal Search

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion

## Visual Direction
Arena's card-grid result presentation, applied to a simple query input
plus grouped result sections.

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/public-portal.md
app/Http/Controllers/PortalController.php
app/Models/Announcement.php
app/Models/School.php
app/Models/Sport.php
resources/js/pages/public/results.tsx
resources/js/pages/public/news.tsx
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
docs/reports/phase-11/WP-11-01-completion.md
```

## Rules
- Inspect the repository first.
- One new read-only route/controller action
  (`GET /meets/{meet}/search`), scoped through `Meet::published()`.
- **Privacy boundary is identical to every existing public route** —
  this is the binding rule for this WP:
  - Searchable: school names, sport/event names, published
    announcement titles, and **validated** result placements (athlete
    name + school + placement/mark — the same triple already public on
    `/results`, never anything more).
  - Never searchable/returned: birthdates, LRN, grade level, contact
    details, guardian information, eligibility material, protests,
    incidents, audit data, user accounts, encoded (unvalidated)
    results, internal venue notes, or anything from an unpublished meet.
- Plain `LIKE`/`where` queries against existing tables — no full-text
  search engine, no new dependency (PMMS's real data volume per meet
  doesn't warrant one).
- No new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any search result field beyond the privacy boundary above; any new
search-index dependency (Scout, Meilisearch, Algolia, etc.);
`PublicBottomNav` (unchanged).

## Objective
Add `/meets/{meet}/search` (`public.search`) — a query box returning
grouped, public-safe matches (schools, sports/events, announcements,
validated result placements) for the active published meet, at Arena's
card-grid rhythm. Empty/no-match states use the existing `EmptyState`
component.

## Acceptance Criteria
- New route additive only, read-only, scoped through `Meet::published()`.
- Every returned field independently verified against the privacy
  boundary above — a `missing()`-style test for every excluded field,
  matching `PortalController`'s existing convention.
- No private/admin data exposed under any query string, including
  attempts to search unpublished-meet content.
- Responsive and accessible; reduced-motion behavior works.
- Tests added (public-safe fields, publication scoping, 404 on
  unpublished meets, empty-query and no-match behavior).
- Full quality gate green.
- Documentation updated (`docs/public-portal.md`'s privacy baseline
  section, confirming Search doesn't widen it).
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-11/WP-11-06-completion.md
```

Next:
```text
WP-11-07 — 404 Page Visual Elevation
```
