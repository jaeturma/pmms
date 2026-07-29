# WP-12-05 — Sport-Specific Exceptions (Athletics, Swimming, Boxing, Chess)

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 12 — Lightweight Sport Mini Portals

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/phases/phase-12-lightweight-sport-mini-portals/README.md
resources/js/pages/public/athletics.tsx
docs/reports/phase-12/WP-12-04-completion.md
```

## Rules
- Inspect the repository first.
- Small, targeted adapters only — do not fork the whole portal page
  for these four sports unless the data model genuinely makes reuse
  impossible (brief's own Step 6).
- `public/athletics.tsx`'s existing "real shell, no fake live per-
  athlete data" precedent is the model for Athletics/Swimming's own
  mini portal — reuse its honest-banner approach, don't re-litigate it.
- No new dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any new backend data for individual-athlete live tracking (Athletics/
Swimming) or bout-level detail beyond what `ScoreboardType::Boxing`
already provides.

## Objective
Handle the four sports whose shape doesn't fit the generic team-vs-team
game list cleanly: Athletics/Swimming (event/heat-based, no side score,
"leading scorers" concept doesn't apply the same way), Boxing (rounds,
already has a dedicated board), Chess (rank-only, no live score in the
usual sense). Adapt labels/sections per sport, never invent data that
doesn't exist.

## Acceptance Criteria
- Each of the 4 sports' mini portal reads correctly for its own shape
  (no "score" language on Chess, no fabricated leading-scorer stat for
  any of them).
- No fabricated data anywhere.
- Tests added for each sport's adapted behavior.
- Full quality gate green.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-12/WP-12-05-completion.md
```

Next:
```text
WP-12-06 — Performance and Visibility-Aware Polling
```
