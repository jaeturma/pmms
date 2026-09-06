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

## Non-medal auto-acceptance

A **non-medal outcome** — a `versus` result, a result for an event that awards no
medals, or a direct "standing" result whose placements carry no medal quantities
— is **auto-accepted the moment it is submitted**. `ResultWorkflowController`
(`isNonMedalFinalResult()` / `autoAcceptNonMedalResult()`) collapses the Event
Secretariat's validate + accept steps into one automatic `Official` transition
(`result.made_official` audit with `automatic: true`) from `submit()`,
`acceptWithDeferredIssues()`, and `storeDirect()`.

It stays fully reversible: the Event Secretariat can still **return it for
correction** (`returnResult()` — accepted from `Official` for a non-medal final
result, clearing `official_by`/`official_at` and any medal awards) or **cancel
it** (`cancel()` — likewise). `ResultController::index` exposes `is_non_medal`,
`can_return`, and the widened `can_cancel` for the frontend.

Medal results are unaffected — they keep the explicit encode → submit →
validate → accept workflow.

## Direct Event Result medal rows

A Direct Event Result (`result_source = 'direct'`, submitted by a Tournament ICT
via `ResultWorkflowController::storeDirect()`) carries a **variable list of medal
rows** — not a fixed Gold/Silver/Bronze podium. The form (`DirectResultForm` in
`resources/js/pages/results/index.tsx`) starts with three rows (Gold, Silver,
Bronze, tally count `1`); the ICT can add rows ("Add Medal") or remove them
before submission. Any combination is valid: `Gold/Silver/Bronze`,
`Gold/Silver/Bronze/Bronze`, `Gold/Gold/Silver`, `Gold/Gold/Gold`, repeated
delegations, repeated medal types.

- Each row is stored as its own `result_placements` row with an explicit
  `medal_type` (`gold`/`silver`/`bronze`), its `delegation_id`, optional athlete
  attribution (individual events only — team events show no per-row athlete
  dropdown), a `mark` (score/points/time), and `tally_quantity` (the medal count,
  independent of the score and never multiplied by a roster).
- `rank` mirrors the medal position (`gold` → 1, `silver` → 2, `bronze` → 3) so
  the rank-keyed tally code keeps working; multiple rows may share a rank.
- Payload: `medal_placements[]` (`{medal_type, delegation_id, mark, count,
  attribution}`). The legacy fixed `gold_/silver_/bronze_*` shape is still
  accepted and folded into `medal_placements` before validation.

On acceptance (`makeOfficial()`), `MedalAwardService::synchronizeDirectRows()`
creates **one `MedalAward` per row**, keyed by `result_placement_id` — never
collapsed because the event, delegation, or medal type repeats, so two identical
`Gold → Compostela → 1` rows contribute `Compostela Gold +2`. Zero-count rows
create no award. The service always deletes the result's awards first, so a
**repeated Accept is idempotent** and a **reopen + resubmit reconciles** the
tally (rebuilds the award set from the current rows) rather than appending.
`MedalTallyService::medalUnits()` and `municipalityMedalWinners()` likewise skip
the "collapse duplicate (result, rank, delegation)" rule for direct results.

## Visibility

Validated results are meet outcomes — readable by **all roles**. Encoded results
are working data — visible to managers only (the index filters them out for
everyone else, per product scope).

**Non-medal / standing / versus results are kept off the public results page**
(`/meets/{meet}/results` — `PortalController::results()` uses
`PublicEventResults::withMedals()`). They appear only per Sports Event, reached
by browsing **Sports → a sport → an event** (`portal/sport-event`, which renders
`standings` and `versusResults` alongside medal `results`).

On the public sport portal (`/{sport}` — `portal/sport-portal`), an event whose
medal has already been awarded (an `Official` result with a medal award in the
active meet) is flagged: `PortalController::sportProfile()` adds
`events[].medal_awarded`, and `PortalSportEvents` renders that card with a
maroon background and light text.

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
