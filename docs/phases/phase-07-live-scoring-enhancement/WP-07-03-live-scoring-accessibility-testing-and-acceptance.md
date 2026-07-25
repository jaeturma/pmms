# WP-07-03 — Live Scoring Accessibility, Testing & Acceptance

## Purpose
Close Phase 7 the way WP-03-11/WP-04-11/WP-05-08 closed the phases before
it: accessibility sweep, end-to-end verification, full quality gate,
compliance review.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Accessibility sweep of the operator console and live display (including
  full-screen mode) at phone/tablet/desktop widths — same checklist as
  WP-04-06/WP-05-07: table/control containment, aria-labels on score
  controls (a bare "+"/"-" button needs a label naming the side, e.g. "Add
  point, Home"), decorative icons aria-hidden, heading order, empty states.
- End-to-end verification: a full session (start → score → correct →
  period change → end) works both with Reverb running and with it stopped
  mid-session (polling picks up where the socket left off, no duplicate or
  lost events); concurrent scoring from two operator tabs doesn't corrupt
  the running total (last-write-wins is acceptable, document it if so, but
  it must not silently drop a `score_events` audit row).
- Confirm, with a test if not already covered: the existing result-only
  flow (`/results` encode → validate) works completely unaffected by this
  phase's existence — a match can be finalized with a result and no live
  session was ever started.
- Run the full quality gate: Pint, PHPStan, Pest, ESLint, Prettier, tsc,
  production build — all must pass; remediate failures.
- Review the whole phase against `.ai/` rules and this phase's own
  DESIGN-NOTES; remediate deviations or document accepted ones. Specific
  re-checks: no `EventResult`/`ResultPlacement` write path exists anywhere
  in the live-scoring code; `MatchStatus` enum is unmodified; authorization
  matches the `Matches — list` row exactly, no loosening; Reverb absence
  never breaks the feature.
- Verify the new migrations run cleanly on MySQL and the visual checkpoints
  from the phase README are demonstrable in the browser, including on a
  phone.
- Write `docs/phases/phase-07-live-scoring-enhancement/
  phase-7-compliance-review.md` and update `.ai/current-phase.md` with the
  phase outcome.

## Out of Scope
New features, Phase 8 planning (the renamed former Post-Deployment Support
phase).

## Deliverables
- Compliance review report
- Updated `.ai/current-phase.md`
- Completion report
- Git status summary

## Acceptance Criteria
- Full quality gate green.
- Authorization verified against the existing Matches — list row, no gaps.
- No result-integrity path exists in live-scoring code.
- Reverb-absent operation verified by test.
- Documentation updated.
- No commit or push performed unless explicitly instructed.

## Completion Report
Include:
1. Repository findings
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next phase

Next:
Phase 8 — Post-Deployment Support (the renamed former Phase 7; not begun
here) or Phase 6 — Reports, UAT, Deployment, and Turnover, whichever the
owner picks up next.
