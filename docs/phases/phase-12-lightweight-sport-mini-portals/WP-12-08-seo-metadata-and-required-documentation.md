# WP-12-08 — SEO Metadata and Required Documentation

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 12 — Lightweight Sport Mini Portals

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/phases/phase-12-lightweight-sport-mini-portals/README.md
```

## Rules
- Inspect the repository first.
- Each sport route gets a real `<Head title>`/meta description (brief's
  own §12) — no fabricated social-preview image.
- Produce the brief's own required documentation set (§16):
  `docs/public-sport-portals/architecture.md`, `route-map.md`,
  `data-contract-map.md` (can point to this phase's own
  `DATA-CONTRACT-MAP.md` rather than duplicating it), `sport-
  configuration.md`, `performance-strategy.md`, `testing-checklist.md`,
  `implementation-summary.md`.
- No new dependency.
- Run all quality checks.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any new page-specific social-preview image asset.

## Objective
Give every sport route a real title/meta description/canonical URL,
and write the brief's own required documentation set summarizing what
was built, what was deferred, and why.

## Acceptance Criteria
- Every sport route has a distinct, real `<Head>` title and meta
  description.
- `docs/public-sport-portals/` exists with all 7 files the brief names.
- Full quality gate green.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-12/WP-12-08-completion.md
```

Next:
```text
WP-12-09 — Testing and Phase Compliance Review
```
