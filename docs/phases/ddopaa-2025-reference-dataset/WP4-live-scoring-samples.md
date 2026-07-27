# WP4 — Live Scoring Samples (Basketball, Boxing, Softball/Baseball)

## Purpose
Give the dataset one scheduled, one in-progress, and one completed match
for each of the three live-scored sports Phase 7 built dedicated
scoreboards for — exercising the Schedule page's live-link column (added
the session before this initiative) with real, varied sport-specific
state.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
Per sport (Basketball, Boxing, Softball/Baseball) — all `SYNTHETIC_DEMO`,
since no source has real score/round/inning data for any of them:
- One `EventMatch` with status `Scheduled`, no session yet.
- One `EventMatch` with an `in_progress` `ScoringSession` — running score,
  correct sport-specific `sport_state` shape (fouls for basketball, a
  partial round history for boxing, partial inning/count state for
  softball/baseball), a `period_label`.
- One `EventMatch` with status `Completed` and an `ended` `ScoringSession`
  — a full, internally consistent final state (a complete round history
  for boxing that sums to the final score; a complete innings breakdown
  for softball/baseball that sums to the final score).

Uses `ScoringSession`/`ScoreEvent`/`EventMatch` exactly as they exist
today — no new columns, no new states, no bypass of the "never touches
`EventResult`" guarantee Phase 7 already proves.

## Out of Scope
Any new scoreboard type or live-scoring feature; anything that finalizes
these matches into an official result (they stay provisional, matches or
not).

## Deliverables
- New seeder file (live-scoring samples portion)
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- Every completed match's session data is internally consistent
  (round/inning totals sum to the final score).
- No live session ever creates or touches `EventResult`/`ResultPlacement`.
- Tests and quality checks completed.
- Documentation updated where relevant.
- No secrets exposed.
- No commit or push performed.

## Completion Report
Include:
1. Repository findings
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP5 — Demo & Load-Test Tier Wiring
