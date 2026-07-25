# Phase 7 Design Notes

Correction to the superseded draft: there is no "Tournament Manager" role
and no existing Reverb foundation — see `README.md`'s Grounding section.
The rest of the draft's core principles were sound and are kept:

- Live scoring is optional; result-only operation (the existing Phase 3
  encode→validate flow) is mandatory and stays completely unmodified.
- A live score is provisional, never official. Only a validated
  `EventResult`/`ResultPlacement` (Phase 3) is official and feeds standings/
  medal tally. A live scoring session **cannot** create or imply a
  validated result — ending a session does not touch `EventResult` at all.
- Reverb provides near-real-time updates when it's running; polling is not
  a fallback bolted on afterward, it's the baseline the feature is built on
  — the live scoreboard must work correctly with Reverb entirely absent.
- Poor connectivity venues use the existing result-only path (skip live
  scoring, encode the final result directly) — nothing new to build for
  this, it already works today.
- No complex offline synchronization. If a live session's device loses
  connection mid-match, the simplest correct behavior is: the session state
  lives server-side (not in browser memory), so reconnecting (or another
  device) picks it up from the last recorded score — no client-side sync
  logic needed.

Important rules:

- `MatchStatus` (`App\Enums\MatchStatus`) is not modified. Live-session
  state (`in_progress` / `paused` / `ended`) lives entirely in the new
  `scoring_sessions` table, one per `EventMatch`. A match's own lifecycle
  status transition (`Scheduled` → `Completed`/`Walkover`/`Cancelled`) is
  untouched by this phase and still happens via the existing
  `matches.status` endpoint, on the Organizer's own decision, independent
  of whether a live session was used.
- Generic score model: `scoring_sessions` carries `side_a_label`/
  `side_b_label` (free text, suggested from the match's own entries when
  starting a session but editable — not a strict FK, since a match can have
  more than two entries and this phase doesn't model bracket/team
  structure), `score_a`/`score_b` (integers), `period_label` (free text,
  e.g. "Q3", "Round 2", "Top of the 5th" — sport-agnostic), and
  `status_note` (free text). No sport-specific columns.
- Every score change, correction, pause/resume, and end is an append-only
  row in `score_events` (session_id, type, payload JSON, recorded_by,
  recorded_at) — this is both the audit trail and the mechanism a
  reconnecting client can use to catch up, not a separate sync system.
- Authorization is entirely reused, not reinvented: `manage-meet-data`
  gates starting/scoring/correcting/ending a session (same as match
  create/update); viewing a session follows the same rule as `Matches — list`
  (Admin/Organizer all, Delegation Officer own delegation's matches only,
  Viewer forbidden) — `docs/authorization.md`.
- Reverb is genuinely additive: the backend always accepts a plain polling
  GET for current session state; broadcasting a Reverb event is an
  additional side effect of the same write, never the only way to observe
  the score. If Reverb isn't configured/running in an environment, the
  feature keeps working via polling with no code branch needed on the
  frontend beyond "try the socket, otherwise poll."
- No new public exposure this phase — nothing in `PortalController` or any
  guest route changes.
- Reuse the shared component library (`ui/table`, `ui/badge`, `PageHeader`,
  `EmptyState`, `Button`) before introducing anything new, same as every
  prior phase.
