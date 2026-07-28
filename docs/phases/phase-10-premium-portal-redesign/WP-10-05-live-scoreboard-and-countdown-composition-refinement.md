# WP-10-05 — Live Scoreboard and Countdown Composition Refinement

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 10 — Premium Portal Redesign (Arena-Inspired Layout & Composition)

## Visual Direction
Arena Sports Template — the monospace live-countdown/score typography is
already solved (`.text-score`/`.text-clock`, Phase 8.5); this WP is
board/card composition and framing, not new typography or colors.

## Required Reading
```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
.ai/ui-ux-rules.md
.ai/skills/pmms-live-scoreboard-experience.md
.ai/skills/pmms-large-display-guidelines.md
docs/ui-ux/premium-design-system.md
docs/phases/phase-10-premium-portal-redesign/README.md
docs/phases/phase-10-premium-portal-redesign/DESIGN-NOTES.md
docs/reports/phase-10/WP-10-04-completion.md
```

## Rules
- Inspect the repository first.
- `LiveScoreDisplay` is shared by the **internal operator console**
  (`scoring/show.tsx`) and the public scoreboard (`public/scoreboard.tsx`)
  — verify both consumers; the operator console must not visually drift
  toward a public-site look.
- Preserve live-score authority (provisional vs. finalized), kiosk mode,
  polling/disconnected-state behavior, and full-screen mode exactly as
  they work today.
- Reuse existing typography tokens — no new score/clock styles.
- Run all quality checks.
- Update documentation.
- Do not commit or push.
- Do not begin the next work package.

## Exclusions
No change to score/clock typography itself (already solved). No change
to kiosk-mode data scoping or the polling/disconnected mechanism.

## Objective
Elevate the surrounding framing/composition of `LiveScoreDisplay` and
`OpeningCountdown` — spacing around the score panels, the center "bug,"
and the pre-match countdown card — so the board reads as a premium
broadcast board, while the operator console stays operator-appropriate
and the public view stays public-appropriate.

## Acceptance Criteria
- Existing business logic, live-score authority, and kiosk mode
  preserved.
- Actual PMMS data used.
- Both `LiveScoreDisplay` consumers (operator console, public
  scoreboard) verified — the operator console does not read as a public
  marketing page.
- Visual work is responsive and accessible, including kiosk/large-
  display sizing.
- Reduced-motion behavior works.
- Tests and quality checks are complete.
- Documentation updated.
- No commit or push performed.

## Completion Report
Create:
```text
docs/reports/phase-10/WP-10-05-completion.md
```

Report repository findings, files modified, visual/frontend changes,
reusable components, large-display behavior, accessibility, tests,
remaining issues, documentation, git status, and next work package.

Next:
```text
WP-10-06 — Medal Tally Layout Refinement
```
