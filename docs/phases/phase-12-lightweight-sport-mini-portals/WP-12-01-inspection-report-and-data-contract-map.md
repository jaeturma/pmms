# WP-12-01 — Inspection Report and Data-Contract Map

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 12 — Lightweight Sport Mini Portals

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
docs/phases/phase-12-lightweight-sport-mini-portals/PHASE-12-LIGHTWEIGHT-SPORT-MINI-PORTALS-original-brief.md
docs/public-portal.md
docs/live-scoring.md
```

## Rules
- Inspect the repository first — routes, sport pages, Inertia props/
  APIs, scoreboards, standings, tournament bracket components, existing
  Arena-cloned components, design tokens, admin/public layout
  boundaries (brief's own Step 1).
- Map available backend data to each of the brief's 8 required sections
  — do not assume a field exists (brief's own Step 2).
- No code changes in this WP — inspection and documentation only.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any code change; any assumption that a section's data exists without
verifying it against real source.

## Objective
Produce the two deliverables the owner explicitly asked for before any
code: a repository inspection report and a per-section data-contract
map, honestly identifying which of the brief's 8 required sections
have real backing data today and which do not.

## Acceptance Criteria
- Repository inspected first, against real source (models, migrations,
  controllers, components) — not assumed from the brief's own examples.
- No code changes.
- Every section in the data-contract map is marked Existing/Buildable/
  Missing with real evidence, not a guess.
- Any real conflict (missing data required by the brief) surfaced to
  the owner for a decision, not silently resolved or silently dropped.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-12/WP-12-01-completion.md
```

Next:
```text
WP-12-02 — Shared Sport-Portal Shell and Components
```
