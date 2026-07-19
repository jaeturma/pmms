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

## Flow (all decisions manager-only, `role:admin,organizer`)

1. **Encode** (`result.encoded`) — allowed only while the meet is **active** and
   for events attached to the meet. Encoded results may be re-encoded (same audit
   action, `revision: true`) or deleted; they are working data.
2. **Validate** (`result.validated`) — a second explicit manager decision;
   validator identity and time recorded. Validated results are official and
   **locked**: no edits, no deletion.
3. **Correct** (`result.corrected`) — never a silent edit, per DESIGN-NOTES. A
   correction requires a **reason**, reopens the result to encoded (clearing the
   validation), and the audit record preserves the superseded placements. The
   corrected standing must then be re-encoded and validated again.

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
