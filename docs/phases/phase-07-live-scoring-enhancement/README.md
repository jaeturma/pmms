# Phase 7 — Live Scoring Enhancement

**Status:** Planned 2026-07-25 — pending owner approval. Execution has not started.
Replaces the unreviewed generic-template draft that occupied this directory
before (git history — same origin as the Phase 3/4/5/6 mixups this project
has repeatedly found and corrected): it invented a "Tournament Manager" role
that doesn't exist in `App\Enums\UserRole`, and claimed the app already has
a "Reverb foundation" to reuse — it doesn't; `composer.json`/`package.json`
have no WebSocket dependency at all. This plan is written fresh for this
codebase, scoped down per owner instruction 2026-07-25: **generic scoreboard
only** (no sport-specific basketball/boxing/softball scoreboards this phase)
and **internal only** (no public live viewing this phase).

## Goal

Let an Admin or Organizer run a live, provisional running score for a match
in progress — visible in real time to anyone who can already see that match
(same audience as the existing `/matches` page) — without touching how
official results work at all. Live scores are a spectator/operations layer
on top of `EventMatch`; the official result still flows through the
existing, human-validated `EventResult`/`ResultPlacement` encode→validate
pipeline (Phase 3), completely unchanged. Ending a live session never
writes a result — an Organizer still explicitly encodes it afterward, same
as today.

## Grounding

- Real roles: `App\Enums\UserRole` — Admin, Organizer, Delegation Officer,
  Viewer. No new role. Operating a live session (start/score/correct/end)
  reuses `manage-meet-data` (Admin+Organizer), the exact gate match
  create/update already uses. Viewing a live session follows the existing
  Matches — list authorization row (`docs/authorization.md`): Admin/
  Organizer see all, Delegation Officer their own delegation's matches
  only, **Viewer forbidden** — live scoring doesn't loosen this.
- `App\Models\EventMatch` / `App\Enums\MatchStatus` (Phase 3, WP-03-04) is
  reused as-is — **not modified**. `MatchStatus` stays exactly
  Scheduled→(Completed|Walkover|Cancelled); live-session state lives in its
  own new table, decoupled from match lifecycle state, so this phase cannot
  destabilize the existing, tested match state machine.
- Phase 3's result-integrity core (`docs/results.md`, WP-03-05 DESIGN-NOTES:
  never silent edits, human validation, single correction path) is the one
  rule this phase must never cross: **a live scoring session cannot create,
  update, or imply a validated result** — it produces a provisional score
  only. Finalizing a match still means an Organizer using the existing
  results-encoding flow.
- **First new dependency in the project.** Every phase through Phase 5 has
  shipped with zero dependencies added — this phase deliberately breaks
  that streak by adding Laravel Reverb (WebSocket broadcasting) plus a
  frontend client, per explicit owner approval 2026-07-25. To keep the risk
  contained: Reverb is additive-only. The live scoreboard must work by
  **polling alone** if Reverb isn't running — it's an enhancement layer,
  never a hard requirement, consistent with the draft's own (correct)
  principle that live scoring is optional and result-only operation is
  mandatory.

## Principles

- Reuse existing match/authorization/audit foundations; add only what
  doesn't exist (the scoring data model, the broadcast layer, the two new
  pages).
- Generic score model only this phase: two-sided running score + a free-text
  period/status label — works for any head-to-head match sport without
  encoding sport-specific rules (basketball fouls/shot clock, boxing
  rounds/judges, baseball innings are explicitly deferred).
- Internal only this phase — no public portal changes, no new privacy
  review needed yet; public live viewing is deferred to its own future WP
  if this version proves out.
- Every score change and correction is audited, same convention as every
  other mutable record in this app.
- One work package at a time; nothing committed or pushed without owner
  instruction.

## Work Packages

| WP | Title |
|---|---|
| WP-07-01 | Live Scoring Foundation |
| WP-07-02 | Generic Live Scoreboard UI |
| WP-07-03 | Live Scoring Accessibility, Testing & Acceptance |

Sequence is strict: each WP assumes its predecessors. Three WPs — deliberately
smaller than the original 8-WP draft, since sport-specific scoreboards and
public exposure are both explicitly out of scope for this pass.

## Visual Checkpoints

1. **After WP-07-01:** an Organizer starts a live scoring session for a
   scheduled match via the API/backend and the running score updates
   correctly, provable via tests, even with no Reverb server running
   (polling works standalone).
2. **After WP-07-02:** an Organizer operates a live scoreboard from a
   laptop while a Delegation Officer or another Organizer watches the score
   update in near-real-time on a second device; a full-screen display mode
   works for a projector/TV.
3. **After WP-07-03:** the whole feature is demonstrable end-to-end
   (start → score → correct → end → still requires a normal result
   encoding afterward) with a green quality gate.

## Exclusions (deferred or out of scope)

Sport-specific scoreboards (basketball, boxing, softball/baseball, or any
other sport's specific rules); public/portal live viewing; auto-creating or
pre-filling `EventResult` from a live session; offline sync beyond simple
polling; a new "scorekeeper"/"Tournament Manager" role; proprietary
scoreboard hardware integration; broadcast production tooling; Flutter; AI.

## Completion

Phase 7 completes via WP-07-03 (full quality gate + compliance review),
mirroring WP-03-11/WP-04-11/WP-05-08. The review report goes to this
directory; the WP log lives in `.ai/current-phase.md`.
