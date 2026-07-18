# PMMS Outbox, Inbox, Idempotency, and Message Reliability

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/event-and-queue-architecture.md, Section 5](../01-architecture/event-and-queue-architecture.md#5-reliable-delivery-outbox-consideration) · [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md)

**No outbox or inbox mechanism is implemented in this phase** — restated per this phase's own working instruction. This document evaluates the pattern conceptually.

---

## 1. Transactional Outbox Evaluation

Evaluate outbox usage for critical post-commit events: Certified results · result holds · credential revocations · public publication · medal-tally recalculation · security alerts · external integrations.

| Element | Direction |
|---|---|
| Same-transaction event persistence | The event record is written in the same database transaction as the state change it describes, so a crash between "commit state" and "dispatch event" cannot silently drop the event |
| Dispatcher | A separate process reads unpublished outbox rows and dispatches them, decoupled from the original request |
| Delivery status | Pending, dispatched, acknowledged, failed |
| Attempts | Tracked per outbox row, feeding retry/backoff (Section 4, [queue-routing-priority-retry-and-failure-architecture.md](queue-routing-priority-retry-and-failure-architecture.md)) |
| Error | Captured without exposing sensitive payload content in logs |
| Replay | An outbox row can be manually re-dispatched by an authorized operator, per [event-metadata-versioning-ordering-and-correlation.md, Section 5](event-metadata-versioning-ordering-and-correlation.md#5-event-persistence-retention-and-replay) |
| Retention | Dispatched outbox rows are retained per the same placeholder retention discipline as every other audit-adjacent record |
| Monitoring | Outbox backlog depth and dispatch latency are observable metrics, per [workflow-audit-observability-metrics-and-support.md](workflow-audit-observability-metrics-and-support.md) |

**Named critical candidates directly extending [../01-architecture/event-and-queue-architecture.md, Section 5](../01-architecture/event-and-queue-architecture.md#5-reliable-delivery-outbox-consideration):** `ResultCertified` reliably reaching Medal Tally; `AccreditationRevoked` reliably reaching the offline-sync revocation-priority mechanism.

**The outbox-table-versus-`after_commit`-dispatch decision remains explicitly deferred to the implementation phase**, restated unchanged from [../01-architecture/event-and-queue-architecture.md, Section 5](../01-architecture/event-and-queue-architecture.md#5-reliable-delivery-outbox-consideration) and tracked in [../01-architecture/runtime-open-decisions.md](../01-architecture/runtime-open-decisions.md) — Phase 0.11 does not resolve this decision, only restates its evaluation criteria.

## 2. Inbox and Consumer Deduplication

Evaluate inbox records for consumers that require durable deduplication (e.g., a webhook-style external integration, or an at-least-once queue delivery where a consumer's side effect is not naturally idempotent):

Consumer identity · message ID · received time · processing status · attempt · result · error · retention · replay policy.

An inbox record lets a consumer recognize "I have already processed this exact message" even after a crash-and-retry, without relying solely on the message producer's delivery guarantees.

## 3. Message Deduplication

Deduplication uses an idempotency key (Section 4) checked at the consumer boundary before any side effect executes — restated from [../01-architecture/offline-sync-runtime-architecture.md, Section 4](../01-architecture/offline-sync-runtime-architecture.md#4-conflict-detection-and-resolution)'s "a duplicate re-upload (same idempotency key) is not a conflict — it is silently deduplicated" principle, generalized from offline sync to message processing broadly.

## 4. Idempotency

**Every retryable action must be idempotent** — restated absolutely per working rule 49. Idempotency is required for:

Offline submissions · score submissions · access scans · credential issuance · credential revocation · report generation · publication · notification dispatch · webhook processing · imports · queue retries · AI-assisted background tasks.

| Element | Direction |
|---|---|
| Idempotency key source | Client-generated (offline/device submissions, per [../01-architecture/offline-sync-runtime-architecture.md, Section 3](../01-architecture/offline-sync-runtime-architecture.md#3-runtime-rules)) or server-generated at command-issuance time |
| Duplicate behavior | Explicitly defined per action — most return the original result without re-executing the side effect; none silently execute twice |
| Idempotency window | A candidate operational parameter (how long a key remains valid for deduplication) — no numeric value invented here |
| Idempotency and correction | A legitimate correction (a new, deliberate command) is distinct from a duplicate (an accidental re-send of the same command) — the idempotency key distinguishes them, never conflated |

## 5. Reliability Is Not the Same as Authority

An outbox, inbox, or idempotency mechanism improves delivery reliability — it never grants a message additional authority. A reliably-delivered `ResultCertified` integration event still triggers Medal Tally's own authorized `RecalculateMedalTally` command through the normal Command Architecture (per [business-process-and-state-machine-architecture.md, Section 1](business-process-and-state-machine-architecture.md#1-command-architecture)), never a direct database write triggered by the message itself.

## 6. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-08 (outbox-table versus `after_commit` dispatch, carried unchanged from Phase 0.4's RD-01/Phase 0.5's PD-21).
