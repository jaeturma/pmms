# PMMS Business Process and State-Machine Architecture

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) · [../02-data/temporal-history-and-versioning-model.md](../02-data/temporal-history-and-versioning-model.md)

---

## 1. Command Architecture

Commands represent authorized intent to change state. Examples (restated from [../01-architecture/workflow-and-command-catalog.md, "Command Candidates"](../01-architecture/workflow-and-command-catalog.md)): `SubmitAthleteRegistration` (`RegisterAthlete`), `AssignEligibilityReviewer`, `ApproveEligibility`, `LockCompetitionEntry` (`LockEntries`), `RecordScore`, `CorrectScore`, `CertifyResult`, `PlaceResultOnHold`, `ResolveProtest`, `CertifyMedalTally` (`RecalculateMedalTally`), `IssueCredential`, `RevokeCredential`, `CloseMeet`.

**Command rules:**

| Rule | Direction |
|---|---|
| One owning context | Every command belongs to exactly one bounded context |
| Explicit actor | Every command records who issued it — a human user or an approved automation/service identity |
| Explicit scope | Every command is validated against the actor's assignment scope, per [../01-architecture/scope-model.md](../01-architecture/scope-model.md) |
| Validated input | Commands are rejected before any state change if input validation fails |
| Authorization | Every command passes the full 16-step authorization decision sequence, per [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) |
| Idempotency where required | Restated from [event-metadata-versioning-ordering-and-correlation.md](event-metadata-versioning-ordering-and-correlation.md) |
| Transaction boundary | A command's state change and its resulting domain event are committed within one transaction, or reconciled via the outbox evaluation in [outbox-inbox-idempotency-and-message-reliability.md](outbox-inbox-idempotency-and-message-reliability.md) |
| Business-rule enforcement | Domain rules are enforced in the Domain/Application layer, never in a controller, listener, or frontend |
| Audit outcome | Every command's outcome (success, rejection, and reason) is recorded |
| Domain events after successful completion | A command emits its domain event only after its state change is durably committed |

## 2. State-Machine Architecture

High-integrity workflows use explicit state models. Each state machine defines:

Valid states · initial state · terminal states · allowed transitions · transition command · required permission · required assignment · separation-of-duties rule · preconditions · side effects · events · notifications · reasons · reopen rules · cancellation rules · history · invalid-transition behavior.

**PMMS deliberately avoids one universal workflow status vocabulary** — restated as this section's governing rule. A `Certified` Official Result, a `Certified` Medal Tally, and a `Confirmed` Delegation are semantically distinct states even though they might share a label; each high-integrity domain's state machine is defined independently in its own workflow document (Sections 20–26), never collapsed into one shared enum.

### Example: Official Result State Machine (illustrative, cross-referencing WF-11/WF-12/WF-13)

`Generated → Pending Validation → Validated → Pending Certification → Certified → [Held ⇄ Certified] → Published → [Corrected | Superseded] → Archived`

- Validation (WF-11) and Certification (WF-12) are separate transitions requiring separate authorization, restated from [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) SOD-02 (entering official ≠ validator).
- `Held` is reachable only via `ProtestFiled`/`ResultPlacedOnHold` and blocks `Published`, restated from [../01-architecture/laravel-architecture.md, Section 6](../01-architecture/laravel-architecture.md#6-workflow-orchestration)'s "Protest filing → result hold" strong-consistency requirement.
- `Corrected`/`Superseded` never overwrite the prior state row, per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession).

## 3. Long-Running Workflow Architecture

Long-running workflows (e.g., Athlete Registration/WF-03, Meet Preparation/WF-01, Accreditation Issuance/WF-05, Protest Resolution/WF-14, Meet Closure/WF-23) require durable process state, tracked conceptually as:

Process instance · workflow definition version · current state · completed steps · pending human tasks · pending timers · related aggregates · correlation ID · last activity · retry state · error state · hold state · cancellation state · completion state · manual intervention status.

**Long-running process state is never kept only in Redis or a queue** — restated absolutely per working rule 22, extending [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 16](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#16-redis-usage-boundaries)'s "Redis is never a system of record" rule to workflow process state specifically. MySQL is the authoritative store for process-instance state; Redis may cache a read-optimized projection of it.

## 4. Process-Instance Correlation

Every long-running workflow instance carries a correlation ID (per [event-metadata-versioning-ordering-and-correlation.md, Section 3](event-metadata-versioning-ordering-and-correlation.md#3-correlation-and-causation)) linking every command, event, notification, and audit record belonging to that one business process — enabling the workflow-instance views required by [workflow-audit-observability-metrics-and-support.md](workflow-audit-observability-metrics-and-support.md).

## 5. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-02 (whether a formal state-machine library/package is ever adopted, versus hand-rolled enum + transition-table enforcement).
