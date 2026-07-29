# WP-11-04 — About Page

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion

## Visual Direction
Arena's structured section/card rhythm, applied to real division/meet
data, matching Phase 10's Contact page precedent.

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/public-portal.md
app/Models/Division.php
resources/js/pages/public/contact.tsx
app/Http/Controllers/PortalController.php
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
docs/reports/phase-11/WP-11-01-completion.md
```

## Rules
- Inspect the repository first.
- One new read-only route/controller action
  (`GET /meets/{meet}/about`), scoped through `Meet::published()`.
- Content is real data only: `Division::current()` (`name`/`type`/
  `areaLabel()`), the active meet's summary (`meetSummary()`, reused
  exactly), and real aggregate counts already computed elsewhere
  (competing municipalities, schools, sports contested) — no invented
  office/history/mission copy.
- No new `Division` field (no address/phone/email — same resolution
  Phase 10 reached for Contact).
- No new dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any `Division` schema change; any invented office-contact or
organizational-history content; `PublicBottomNav` (unchanged).

## Objective
Add `/meets/{meet}/about` (`public.about`) — a page describing the
Division running the meet (name, type/area label) and the meet itself
(school year, dates, venue, real participation counts), built entirely
from data PMMS already stores, at Arena's structured-section rhythm.

## Acceptance Criteria
- New route additive only, read-only, scoped through `Meet::published()`.
- Every fact on the page traces to a real, named model/field — no
  invented copy beyond section labels/descriptions.
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
docs/reports/phase-11/WP-11-04-completion.md
```

Next:
```text
WP-11-05 — FAQs Page
```
