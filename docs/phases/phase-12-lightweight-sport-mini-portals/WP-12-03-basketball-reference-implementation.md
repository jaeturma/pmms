# WP-12-03 — Basketball Reference Implementation

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 12 — Lightweight Sport Mini Portals

## Required Reading
```text
.ai/project-rules.md
.ai/work-package-runner.md
docs/phases/phase-12-lightweight-sport-mini-portals/README.md
docs/phases/phase-12-lightweight-sport-mini-portals/DESIGN-NOTES.md
docs/reports/phase-12/WP-12-02-completion.md
```

## Rules
- Inspect the repository first.
- `/basketball` is the reference implementation (brief's own Step 4) —
  validate it completely (desktop/tablet/mobile, loading/empty states,
  live refresh, performance) before generalizing to any other sport.
- Reuse WP-12-02's shell/components exactly — this WP wires them
  together for one real sport, it does not build new shared components
  unless a genuine basketball-specific gap appears.
- No new dependency, no new migration.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
Any other sport's route/page (that's WP-12-04); any new backend logic
for Standings/Leading Scorers/Bracket.

## Objective
Wire the shared shell into a complete, real `/basketball` page: Live
Now (using `live-score-display.tsx`'s existing Basketball fouls
support), Today's/Completed/Upcoming Games, Venue Information, and the
honest "not available yet" states for Standings/Leading Scorers/
Bracket — validated end-to-end as the brief's own pilot.

## Acceptance Criteria
- `/basketball` renders correctly with zero, one, and multiple live
  Basketball matches.
- Game lists respect the brief's own limits (10/10/10, top-5 N/A here).
- No data for any other sport is fetched on this page.
- Responsive at phone/tablet/desktop widths; accessible; reduced-motion
  behavior works.
- Tests added (route, live/no-live states, game-list limits,
  publication scoping, public-safe fields).
- Full quality gate green.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-12/WP-12-03-completion.md
```

Next:
```text
WP-12-04 — Generalize to the Remaining 11 Sports
```
