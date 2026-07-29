# WP-11-05 — FAQs Page

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 11 — Public Portal Completion

## Visual Direction
Arena's structured section rhythm, applied to an accordion/list layout
(shadcn `Accordion` primitive — already available via shadcn/ui, not a
new dependency).

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/public-portal.md
resources/js/pages/public/contact.tsx
docs/phases/phase-11-public-portal-completion/README.md
docs/phases/phase-11-public-portal-completion/DESIGN-NOTES.md
docs/reports/phase-11/WP-11-01-completion.md
```

## Rules
- Inspect the repository first.
- One new read-only route/controller action
  (`GET /meets/{meet}/faqs`), scoped through `Meet::published()`.
- Question text is written copy (like any static label), but any
  factual answer (dates, "is this official," how publication works)
  must read from real data/documented behavior — never a hardcoded
  value that could silently go stale.
- Add the shadcn `Accordion` component via its standard CLI/copy
  pattern if not already present — confirm first, since this project
  has added shadcn primitives before without counting as a new
  dependency (it's copied source, not an npm package).
- No new npm dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any invented factual claim not traceable to real data/documented
behavior; `PublicBottomNav` (unchanged).

## Objective
Add `/meets/{meet}/faqs` (`public.faqs`) — common questions about the
portal (how to read results/medal tally, what "published"/"validated"
mean, where to find schedules) with real, current answers (e.g. citing
the active meet's actual dates where relevant), at Arena's structured
rhythm.

## Acceptance Criteria
- New route additive only, read-only, scoped through `Meet::published()`.
- Every factual answer traces to real data or already-documented
  behavior — verified against `docs/public-portal.md`.
- No private/admin data exposed.
- Responsive and accessible (accordion keyboard/aria behavior correct);
  reduced-motion behavior works.
- Tests added (public-safe fields, publication scoping, 404 on
  unpublished meets).
- Full quality gate green.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-11/WP-11-05-completion.md
```

Next:
```text
WP-11-06 — Public Portal Search
```
