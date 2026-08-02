# Results Encoding & Validation

WP-03-05 — the integrity core of Phase 3. Every result decision is human,
attributable, and audited; the medal tally (WP-03-06) derives from validated
results alone.

## Data model

- `event_results` — one final standing per meet event (unique meet+event):
  `status` (`App\Enums\ResultStatus`: encoded / validated), `encoded_by`/`encoded_at`,
  `validated_by`/`validated_at`.
- `result_placements` — `event_result_id` (cascade), `entry_id` (restrict —
  `EntryController::destroy` also refuses entries with placements), `rank`,
  optional `mark` (score/time text, ≤60), `is_tie`. Unique per (result, entry).

## Flow

1. **Encode** (`result.encoded`) — Admin/Organizer for any event, or (Phase 16) a
   Technical Official for an event whose sport they're assigned to
   (`ResultController::authorizeEncode()`, `User::sports()`) — allowed only while
   the meet is **active** and for events attached to the meet. Encoded results may
   be re-encoded (same audit action, `revision: true`) or deleted; they are
   working data.
2. **Validate** (`result.validated`) — manager-only (`role:admin,organizer`), a
   second explicit decision distinct from encoding; validator identity and time
   recorded. Validated results are official and **locked**: no edits, no
   deletion.
3. **Correct** (`result.corrected`) — manager-only, never a silent edit, per
   DESIGN-NOTES. A correction requires a **reason**, reopens the result to
   encoded (clearing the validation), and the audit record preserves the
   superseded placements. The corrected standing must then be re-encoded and
   validated again.

## Reviewed against the DdOPAA organizational model (WP-REALIGN-08, 2026-08-02)

The gap assessment
(`docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md` §13)
flagged two differences from the approved model: a richer status state machine
(Draft/Submitted/For Confirmation/Returned/Reopened/Cancelled vs. today's
Encoded/Validated) and a distinct "Results Committee" role instead of generic
Admin/Organizer. Both were reviewed and deliberately deferred, not built:

- **State machine** — the two-state Encoded/Validated flow, with `correct()`'s
  required-reason/reopen/audit-preserving-superseded-placements behavior, already
  meets the mandate's real requirement ("must not silently edit... traceable...
  reason... preserve original values... generate audit events"). A richer state
  machine would be a larger, riskier rework (touches this controller, the
  `results/index.tsx` UI, and every consumer of `ResultStatus` — medal tally,
  reports, the public portal) for no corresponding gain in what's actually
  enforced today.
- **Results Committee role** — same reasoning as WP-REALIGN-06's DSAC decision:
  the approved model already plans this as a `ManagementTeam` `team_type`
  (WP-REALIGN-09, not yet built). A standalone role now would likely be thrown
  away or need rework once that table exists, so validate/correct/delete stay
  `role:admin,organizer`-only until then.

## Placement integrity (server-enforced)

- Only **confirmed** entries of the same meet+event are placeable.
- One rank per entry per event (unique pivot + distinct rule).
- Duplicate ranks are rejected unless every placement sharing the rank carries
  the `is_tie` flag.

## Visibility

Validated results are meet outcomes — readable by **all roles**. Encoded results
are working data — visible to managers only (the index filters them out for
everyone else, per product scope).

## UI

`resources/js/pages/results/index.tsx` (sidebar "Results", all roles) — result
cards per event with the placement table, meet/event filters, and for managers:
an encode dialog (active meet → not-yet-encoded event → dynamic placement rows
with rank/entry/mark/tie), edit while encoded, Validate confirmation, a Correct
dialog requiring the reason, and delete for encoded results.

## Audit

`result.encoded|validated|corrected|deleted` via `AuditLogger`, with meet and
event context; encode carries the placement snapshot, corrections carry the
reason plus `superseded_placements`.

**Division initiative:** placement "school" fields (list, snapshot, public
results) are sourced from `placement.entry.athlete.school` — the placed
athlete's own home school, not the delegation's. The medal tally derived
from these results is the one remaining exception — it still excludes
municipal-delegation placements entirely until WP5. See
`docs/delegations.md` and `docs/medal-tally.md`.
