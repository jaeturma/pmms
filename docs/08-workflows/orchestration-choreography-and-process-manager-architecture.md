# PMMS Orchestration, Choreography, and Process-Manager Architecture

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/laravel-architecture.md, Section 6](../01-architecture/laravel-architecture.md#6-workflow-orchestration) · [business-process-and-state-machine-architecture.md](business-process-and-state-machine-architecture.md)

---

## 1. Process Managers

Use a process manager (an explicit, documented Application-layer orchestrator) when a workflow:

Spans contexts · must coordinate multiple events · may require compensation · has timers · has manual tasks · must track completion · must support recovery.

**Process managers must remain explicit and documented** — never an unlabeled, discoverable-only-by-reading-code chain of event listeners. This restates and formalizes [../01-architecture/laravel-architecture.md, Section 6](../01-architecture/laravel-architecture.md#6-workflow-orchestration)'s named orchestration table, reproduced and extended here:

| Process Manager | Spans | Orchestration Concern | Owning Context (Coordinator) |
|---|---|---|---|
| Eligibility-to-Accreditation Readiness | Eligibility (BC-09) → Accreditation (BC-19) | Accreditation must know eligibility approval is a precondition, not silently poll for it | Accreditation (BC-19) |
| Certified-Result-to-Medal-Tally Recalculation | Official Results (BC-16) → Medal Tally (BC-18) | Must be reliable (outbox evaluation, [outbox-inbox-idempotency-and-message-reliability.md](outbox-inbox-idempotency-and-message-reliability.md)) — a missed event produces an incorrect public tally | Medal Tally (BC-18) |
| Protest-Filing-to-Result-Hold | Protest and Appeals (BC-17) → Official Results (BC-16) | Must be synchronous/strong, never eventually consistent | Protest and Appeals (BC-17), acting on Official Results synchronously |
| Credential-Revocation-to-Access-Denial Propagation | Accreditation (BC-19) → Access Validation (BC-20) | Must be prioritized for offline-device sync, per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) | Access Validation (BC-20), fed by Accreditation |
| Meet-Closure-to-Assignment-and-Credential-Expiration | Meet Administration (BC-04) → all meet-scoped contexts | A wide fan-out requiring explicit process state, never a single unmonitored event cascade | Meet Administration (BC-04) |
| Registration-Acceptance-to-Competition-Entry-Readiness | Athlete Registration (BC-08) → Competition Entries (BC-11) | A gate condition, never automatic entry creation | Competition Entries (BC-11) |

Each process manager's durable state uses the long-running workflow architecture in [business-process-and-state-machine-architecture.md, Section 3](business-process-and-state-machine-architecture.md#3-long-running-workflow-architecture).

## 2. Orchestration Versus Choreography

### Orchestration

Preferred when: a clear workflow owner exists · sequence matters · completion must be tracked · failure recovery is complex · human tasks exist · high-integrity coordination is required.

All six process managers in Section 1 use orchestration.

### Choreography

Acceptable when: consumers react independently to a completed fact · no hidden sequence is required · failure in one consumer does not invalidate source state. Examples: analytics, notifications, projections, and search indexing reacting to a domain event.

**Avoid long chains of listeners that create invisible workflow logic** — restated absolutely per working rule 19. A chain of more than two choreographed reactions to the same event is a signal the workflow needs an explicit process manager instead.

## 3. Saga Readiness

Evaluate saga-style coordination only for workflows spanning multiple contexts with genuine compensation needs. Define, for any future saga candidate:

Initiating action · participating contexts · durable progress · compensating actions · irreversible actions · timeout · failure escalation · manual resolution · audit.

**No saga framework is introduced in this phase** — the six process managers in Section 1 are evaluated as sufficiently bounded (2-context, single-direction fan-out or gate) that hand-rolled orchestration with explicit compensation is preferred over a generic saga framework, per working rule 16 ("do not implement a generic workflow engine prematurely").

## 4. Compensation

Compensating actions (reversing a partially-applied fan-out if a later step fails) are designed explicitly per process manager, never assumed automatic — restated from [../01-architecture/laravel-architecture.md, Section 6](../01-architecture/laravel-architecture.md#6-workflow-orchestration). Example: if Meet-Closure-to-Assignment-Expiration partially completes (some assignments expired, others not, due to a mid-fan-out failure), the process manager's recovery step re-evaluates the full expiration set from durable state — it does not attempt to "undo" already-expired assignments, since expiration is not itself a reversible business action requiring compensation, only a resumable one.

## 5. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-03 (whether any future cross-context workflow grows complex enough to justify a saga framework or dedicated process-manager persistence table).
