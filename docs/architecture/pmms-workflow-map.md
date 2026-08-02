# PMMS Workflow Map

Companion to [pmms-organizational-realignment-gap-assessment.md](../reports/architecture/pmms-organizational-realignment-gap-assessment.md). Each mandate workflow (§30 A–D) is mapped against what's actually implemented today, verified against controller code, not assumed.

## A. Coach and Athlete Registration

**Mandate:** Coach account → Coach assignment approved → Coach registers athlete → Athlete linked to municipality/SchoolDistrict/school → assigned to sport/category → Coach submits requirements → DSAC eligibility review → Approved/Returned/Rejected → Accreditation → Event entry.

**Today, verified:**

```
DelegationOfficer login (not a Coach login — see OQ-2)
  → creates Athlete (delegation_id + school_id set, AthletePolicy scoped to own delegation, draft + registration-open only)
  → creates Entry (Athlete × Event, EntryController, EntryPolicy)
  → uploads EligibilityDocument (EligibilityController::storeDocument, creates/reopens EligibilityReview)
  → EligibilityReview.status: Pending
  → Admin/Organizer reviews → approve() or returnReview() [EligibilityController.php:288-351]
  → status: Approved | Returned (Returned → re-upload resets to Pending, EligibilityController.php:212-223)
  → once Delegation.status = Approved AND EligibilityReview.status = Approved:
      AccreditationController::store() → Accreditation row created, number assigned
  → Entry.status: Submitted → Confirmed (separate action, EntryController::confirm)
```

**Gaps vs. mandate, precisely:**
- Actor is `DelegationOfficer`, not a `Coach` login — the workflow's shape is identical, its actor identity is not (gap assessment §10).
- No terminal `Rejected` state — only `Pending`/`Approved`/`Returned`, and `Returned` is always resubmittable, never a hard stop.
- "Assigned to sport/category" happens via `Entry.event_id`, which today conflates category+discipline (gap assessment §8) — functionally works, structurally not a clean category assignment.
- Everything else — the linkage integrity checks the mandate explicitly wants prevented (athlete outside the delegation's municipality, athlete in a school district outside the school's municipality, duplicate registration, event-entry limits, finalization without eligibility) — **is already enforced**: `AthletePolicy` scopes creation to the officer's own delegation; `School.district_id`/`School.school_district_id` are set at the registry level, not at athlete-creation time, so an athlete can't be assigned an inconsistent municipality/school-district pair; `Event.max_entries_per_delegation` + `ResultController::assertPlacementsValid()` cap and validate entries; `AccreditationController::store()` hard-requires `EligibilityReview.status === Approved` before accrediting (line 109-113).

## B. Result Workflow

**Mandate:** Tournament Manager/authorized personnel submits result → Technical Officials verify where required → Results Committee confirms → finalized → standings updated → medal awarded → tally recalculated.

**Today, verified:**

```
Admin/Organizer (any event) OR scoped TechnicalOfficial (own sport only)
  → ResultController::store() — EventResult created, status: Encoded
  → [optional] ResultController::update() — re-encode while still Encoded
  → Admin/Organizer only: ResultController::validateResult() — status: Validated
  → MedalTallyService::standings() reads Validated results at read time (no stored tally to drift)
  → medal tally + standings are always current, no separate "recalculate" step needed
```

**Gaps vs. mandate:** no distinct "Results Committee" role (folded into Admin/Organizer, gap assessment §13); no explicit "Technical Officials verify" sub-step (a TO can *encode* their own sport's result, but nothing requires a second TO's sign-off before an Organizer validates it); two-state machine instead of the mandate's richer Draft/Submitted/For-Confirmation/Returned/Reopened/Cancelled list. **Not a gap**: the mandate's "must not silently edit... traceable... reason... preserve original values... audit events" requirement is fully met today by `ResultController::correct()` (requires a `reason`, reopens to `Encoded`, snapshots superseded placements into the audit record) — this is often the hardest part of a results workflow to get right and it's already correct.

## C. Live Scoring

**Mandate:** Match/event → Live Scoring or Result-Only Mode → provisional score → match completed → result submitted → Results Committee confirmation → finalized result.

**Today, verified:** `ScoringSessionController` (full lifecycle: `store`/`score`/`period`/`pause`/`resume`/`end`, plus sport-specific `foul`/`round`/`count`/`inningRun`) produces a `ScoringSession` with a running `score_a`/`score_b` and a `sport_state` JSON blob — this is the mandate's "provisional score." Ending a session (`ScoringSessionController::end()`) **deliberately never touches `EventResult`** (verified by an explicit test, `tests/Feature/ScoringSessionTest.php` "ending a scoring session never creates or touches an EventResult") — a live session is provisional by design, exactly matching the mandate's "provisional score → match completed → result submitted" as two genuinely separate steps, not one. Recording a result after a live match ends is a manual, separate `ResultController::store()` call today — there is no auto-carry-over from the final `ScoringSession` score into an `EventResult`, which is a reasonable design (final score ≠ final official ranking/placement in most sports) but worth confirming is intentional with the product owner if not already.

Access: `role:admin,technical_official` route group (Organizer deliberately excluded as of 2026-08-02, commit `fb8d8c9`) plus `ScoringSessionController::canManage()` sport-scoping. **This is the one workflow where the "TechnicalOfficial sport scope is global, not meet-scoped" gap (gap assessment §7/§9) has the most direct operational consequence** — a Technical Official assigned to Basketball can run *any* meet's basketball scoreboard, forever, not just the meet(s) they were actually accredited/deployed to.

## D. Personnel Assignment

**Mandate:** Person → assigned to meet → assigned to management team or sport → role and scope applied → medical clearance if required → accreditation → active assignment.

**Today, verified:** this workflow **does not exist as a unified flow** — it is two disconnected mechanisms depending on which kind of person:
- A **Delegation Officer** is assigned via `delegation_user` (meet-and-delegation-scoped already, via `Delegation.meet_id`) — closest thing to the mandate's shape today.
- A **Technical Official** is assigned via `sport_user` (sport-scoped, **not** meet-scoped — the recurring gap).
- A **coach/chaperone** (`Personnel` row) has no assignment-to-a-login at all — they're a roster entry under a `Delegation`, full stop.
- **No path exists** for "assign a person to a management team" (no `ManagementTeam` model), "medical clearance if required" (no `MedicalClearance` model), or a role-and-scope-applied step distinct from whichever narrow pivot happens to apply.

**This workflow is the clearest illustration of why WP-REALIGN-04 (generic `MeetSportAssignment`) and WP-REALIGN-09 (`ManagementTeam`/`ManagementTeamMember`) are the highest-leverage new work** — once both exist, this workflow becomes representable end-to-end for the first time, using the same "assignment row + role + scope" shape uniformly instead of three different ad-hoc mechanisms.

## Cross-workflow observation

Every workflow above shares one strength worth preserving deliberately in any new work: **status transitions are explicit, guarded, and audited** (`AuditLogger::record()` calls on every state change, e.g. `eligibility.approved`, `result.validated`, `result.corrected`, `accreditation.granted`) — nothing in the running system does a silent field update on a status-bearing row. Any new domain (Committee, Supply, Food, Billeting, Transport, Medical, DRRM) should follow this same discipline from its first migration, not retrofit auditing later.
