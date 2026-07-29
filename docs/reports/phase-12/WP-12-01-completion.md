# WP-12-01 — Completion Report

Inspection Report and Data-Contract Map. Status: **done**.

## Repository findings

Full findings live in `docs/phases/phase-12-lightweight-sport-mini-
portals/INSPECTION-REPORT.md` and `DATA-CONTRACT-MAP.md`. Summary:

- Every existing public route is meet-scoped (`/meets/{meet}/...`);
  the brief's own `/{sportSlug}` pattern (no meet ID) is a genuine
  deviation, resolved via reusing `Meet::published()->active()->
  first()` (the same resolution `home()` already uses) — additive, not
  a structural conflict.
- `live-score-display.tsx` already renders the complete live board for
  every sport with a dedicated scoreboard (Basketball, Boxing,
  Softball/Baseball); the other 9 sports fall back to the existing
  Generic side-score board.
- **Real, load-bearing finding**: 3 of the brief's 8 required sections
  — Standings (team W-L records), Leading Scorers (per-athlete point
  totals), and a real Tournament Bracket (seeded progression tree) —
  have **zero backing data anywhere in this schema, for any sport**,
  including the brief's own chosen pilot (basketball). Verified
  directly against `matches`/`scoring_sessions`/`score_events`
  migrations and `EventResult`/`ResultPlacement` models: `EventResult`
  has no `match_id` at all (medal placements and match scores are two
  disconnected systems); every `ScoreEvent` type is side-level, never
  attributing a point to an individual athlete; `matches.round_label`
  is a free-text string with no bracket-tree structure. Building any of
  the three would require new backend business logic/schema, which the
  brief's own scope boundary (§1) says to preserve, not modify.

## Owner decisions (resolved via `AskUserQuestion` before any code)

- Standings/Leading Scorers/Bracket-as-diagram: honest "not available
  yet" states for every sport, this phase — no new backend work. Same
  resolution WP-08-11 already used for Athletics.
- Routing: `/{sportSlug}` resolves via the existing single-active-meet
  concept, confirmed.
- Rollout: basketball first (the brief's own pilot), then generalize to
  the other 11 sports.
- Phase renumbered from the brief's own "08.6" to **Phase 12**, to fit
  this project's real sequence (Phase 11 was the most recently shipped)
  and avoid colliding with the real, already-shipped Phase 8.5.

## Files created/modified

- `docs/phases/phase-12-lightweight-sport-mini-portals/` — new:
  `README.md`, `DESIGN-NOTES.md`, `CHECKLIST.md`, `INSPECTION-REPORT.md`,
  `DATA-CONTRACT-MAP.md`, all 9 WP files, and the original brief
  (renamed verbatim from its original "Phase 08.6" filename, content
  unchanged).
- `docs/howtorun/ROADMAP.md` — added the Phase 12 line.
- `docs/reports/phase-12/` — new, this report.

## A note on how this phase started

The original brief file appeared unexplained in the working tree mid-
session during Phase 11 (flagged to the owner at the time, per
`.ai/current-phase.md`'s 2026-07-26 "concurrent write" precedent). It
also contained embedded "Claude must..." execution directives — these
were **not** acted on from the file alone; implementation began only
after the owner explicitly instructed it in chat. This WP's own
inspection work proceeded normally once that instruction was given.

## Remaining issues

None that change the plan. The navigation-integration question (how/
whether these 12 new top-level routes get linked from the existing
header nav) is intentionally left as a smaller, later decision — see
`DESIGN-NOTES.md`'s own note — rather than blocking this WP.

## Git status

Working tree: only `docs/howtorun/ROADMAP.md` modified, plus the new
untracked `docs/phases/phase-12-lightweight-sport-mini-portals/` and
`docs/reports/phase-12/` (and the pre-existing untracked `.claude/`).
Zero application code, routes, migrations, or dependency manifests
touched — confirmed via `git status`, not assumed. Not committed, per
rule.

Next: **WP-12-02 — Shared Sport-Portal Shell and Components**, awaiting
owner instruction, same cadence as every phase before it.
