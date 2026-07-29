# WP-11-02 — Rankings Page (Split from Medal Tally)

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion

## Visual Direction
Arena's card-grid/table rhythm, already established on `public/tally.tsx`.

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/medal-tally.md
docs/public-portal.md
resources/js/pages/public/tally.tsx
app/Http/Controllers/PortalController.php
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
docs/reports/phase-11/WP-11-01-completion.md
```

## Rules
- Inspect the repository first.
- One new read-only route/controller action
  (`GET /meets/{meet}/rankings`), reusing `MedalTallyService::standings()`
  exactly as `tally()` already does — no new computation, no new
  aggregate.
- Scoped through `Meet::published()`, matching every existing public
  route.
- `tally.tsx` itself is not restructured — its own "Overall ranking"
  table stays where it is; the new page is an additional destination,
  not a replacement.
- No new dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any change to `MedalTallyService`'s computation; any change to
`tally.tsx`'s existing table; `PublicBottomNav` (unchanged).

## Objective
Add `/meets/{meet}/rankings` (`public.rankings`) — a standalone page
rendering the same municipality/district standings `MedalTallyService::
standings()` already computes, presented at Arena's card-grid rhythm.
Link it from the Medal Tally page and (pending WP-11-08) the header
nav/footer.

## Acceptance Criteria
- New route additive only; every existing route/controller/page
  untouched.
- Scoped through `Meet::published()`; unpublished meets 404.
- No private/admin data exposed.
- Responsive and accessible; reduced-motion behavior works.
- Tests added (public-safe fields, publication scoping, 404 on
  unpublished meets — matching `PublicResultsTest`/`PublicScoreboardTest`
  conventions).
- Full quality gate green.
- Documentation updated (`docs/public-portal.md`, `docs/medal-tally.md`).
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-11/WP-11-02-completion.md
```

Next:
```text
WP-11-03 — Static Gallery Page
```
