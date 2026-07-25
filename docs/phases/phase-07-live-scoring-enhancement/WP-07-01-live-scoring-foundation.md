# WP-07-01 — Live Scoring Foundation

## Purpose
Establish the data model, backend endpoints, and broadcast/polling
mechanism a live scoreboard needs — no UI in this WP, proven entirely
through tests. This is the phase's highest-risk WP (first dependency added
to the project) and is isolated on its own, per plan.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Add `laravel/reverb` (composer) — the project's first new dependency;
  document this explicitly in `docs/live-scoring.md` as a deliberate,
  owner-approved exception to the "zero dependencies" streak every prior
  phase has kept. Configure broadcasting per Reverb's standard setup;
  broadcasting must be safe to leave unconfigured/not running in any
  environment (the app must not error if Reverb isn't up).
- `scoring_sessions` table: `match_id` (FK `matches`, restrict on delete,
  one active session per match enforced at the application level), `status`
  (`in_progress`/`paused`/`ended`), `side_a_label`/`side_b_label` (string),
  `score_a`/`score_b` (unsigned int, default 0), `period_label` (nullable
  string), `status_note` (nullable string), `started_by`/`ended_by` (FK
  `users`, nullable), `started_at`/`ended_at` (nullable timestamp).
- `score_events` table (append-only): `scoring_session_id` (FK cascade),
  `type` (`point`/`correction`/`period_change`/`note`/`paused`/`resumed`/
  `ended`), `payload` (JSON), `recorded_by` (FK `users`), `created_at`
  only (no `updated_at` — this table is never updated, only appended to).
- Endpoints, all `manage-meet-data` gated (Admin+Organizer, same as match
  create/update): start a session for a `Scheduled` match, record a score
  event (point/correction/period change/note), pause, resume, end. Ending
  does **not** touch `EventResult`/`ResultPlacement` in any way — confirm
  this with a test, not just by omission.
- A read endpoint (plain GET, works for any authenticated role permitted to
  view the match per the existing `Matches — list` authorization row) that
  returns the current session state — this is the polling contract the
  frontend will use in WP-07-02, and it must return correct data
  independent of whether Reverb is running.
- Broadcast a Reverb event on every score-affecting write, on a private
  channel scoped so only users who could already view that match can
  subscribe (mirror the `Matches — list` authorization rule for channel
  authorization, not a new rule).
- `scoring.started`/`scored`/`corrected`/`paused`/`resumed`/`ended` audit
  events via `AuditLogger`, same convention as every other mutable record.
- Tests: authorization (forbidden roles/actions mirror `Matches — list`
  and match create/update exactly), a full session lifecycle via the
  polling read endpoint with **broadcasting left unconfigured** (proves
  the feature doesn't hard-depend on Reverb), corrections are audited with
  the correct actor, ending a session leaves `EventResult`/
  `ResultPlacement` completely untouched (explicit assertion), only one
  active session per match is enforced.

## Out of Scope
Any UI (WP-07-02); sport-specific score structures; public exposure;
auto-creating or pre-filling a result from a session.

## Deliverables
- Updated source code
- New migration(s)
- Updated documentation (new docs/live-scoring.md, docs/matches.md
  cross-reference, docs/authorization.md, docs/audit-trail.md)
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Tests and quality checks completed, including the Reverb-unconfigured
  polling test.
- Documentation updated.
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
WP-07-02 — Generic Live Scoreboard UI
