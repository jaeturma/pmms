# WP-12-04 — Generalize to the Remaining 11 Sports

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 12 — Lightweight Sport Mini Portals

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/phases/phase-12-lightweight-sport-mini-portals/README.md
docs/reports/phase-12/WP-12-03-completion.md
```

## Rules
- Inspect the repository first.
- Adapt the existing `SportPortalConfig`/shared components to
  Volleyball, Baseball, Softball, Football, Sepak Takraw, Badminton,
  Table Tennis, Chess, Boxing, Athletics, Swimming — reuse, don't fork
  (brief's own Step 5).
- No new dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Sport-specific exceptions beyond what generic config already handles —
those are WP-12-05's scope.

## Objective
Add the route for each of the remaining 11 sports, all sharing
WP-12-02/03's exact component system, differing only by
`SportPortalConfig` values (scoring type, terminology, Generic vs.
dedicated scoreboard).

## Acceptance Criteria
- All 12 sport routes exist and render.
- No per-sport duplicated page — every route composes from the same
  shared components.
- No fabricated data anywhere.
- Responsive and accessible at every route.
- Tests added per sport (or parameterized across all 12, whichever is
  the less duplicative real test shape).
- Full quality gate green.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-12/WP-12-04-completion.md
```

Next:
```text
WP-12-05 — Sport-Specific Exceptions (Athletics, Swimming, Boxing, Chess)
```
