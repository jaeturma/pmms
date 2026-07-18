# PMMS Queue Routing, Priority, Retry, and Failure Architecture

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md) · [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 2](../05-devops/application-worker-scheduler-and-realtime-deployment.md#2-queue-worker-and-horizon-deployment)

**No queue configuration, Horizon configuration, or job class is created here.** This document validates and extends the existing Phase 0.4 queue architecture; it does not replace it.

---

## 1. Queue Architecture (Validated Against Phase 0.4)

The 11 queue categories confirmed in [../01-architecture/event-and-queue-architecture.md, Section 1](../01-architecture/event-and-queue-architecture.md#1-queue-categories) remain unchanged and are not redefined here: `critical`, `default`, `notifications`, `documents`, `imports`, `exports`, `media`, `projections`, `analytics`, `integrations`, `maintenance`.

For each queue, Phase 0.4 already defines: purpose · priority · allowed job types (implied) · retry policy direction · timeout direction · idempotency requirement · failure handling · sensitive-data concern · dead-letter/failed-job handling · operational ownership · monitoring. Phase 0.11 adds the workflow-level detail below without contradicting that table.

## 2. Queue Priority and Isolation

Restated from [../01-architecture/event-and-queue-architecture.md, Section 3](../01-architecture/event-and-queue-architecture.md#3-laravel-horizon-architecture) and [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 2](../05-devops/application-worker-scheduler-and-realtime-deployment.md#2-queue-worker-and-horizon-deployment):

- Priority order: `critical` > `default` > low-priority (`analytics`, `maintenance`).
- `critical` workers are never shared with `imports`, `media`, or `exports` — a long-running export or bulk import must never block time-critical score, credential, or security work.
- `notifications`, `documents`, `imports`, `exports`, `media`, `projections`, and `analytics` each scale independently of the `critical` pool.
- Worker pools auto-balance toward the deepest backlog within isolation boundaries, never crossing an isolation boundary to do so.

## 3. Retry and Backoff

| Failure Category | Definition | Retry Direction |
|---|---|---|
| Retryable failure | Transient (network, timeout, temporary lock contention) | Retry with backoff, up to a placeholder maximum-attempts value |
| Non-retryable failure | Validation failure — the input itself is invalid | Fail immediately, surface to the originating actor or a manual queue |
| Transient dependency failure | An external dependency (mail provider, storage) is temporarily unavailable | Retry with backoff; escalate to manual review if the outage persists |
| Authorization failure | The actor's authority has changed since the job was queued | Fail immediately — never retry blindly, restated from [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules) ("jobs must recheck applicable authorization or execution authority") |
| Stale-state failure | The record's state has changed incompatibly since the job was queued (e.g., a result was superseded before a pending job ran) | Reload authoritative state at execution time (restated from the same section); fail or re-derive the action, never blindly apply a stale assumption |
| Poison message | A message that fails every retry attempt and would fail indefinitely | Route to failed-job/dead-letter handling (Section 4), never retried forever |

**Do not retry unauthorized or invalid business transitions blindly** — restated absolutely per this section's governing instruction and working rule 21.

Maximum attempts and backoff-interval numeric values remain placeholders pending real operational data — no number is invented here, consistent with every prior phase's "no invented numbers" discipline.

## 4. Failed Jobs and Dead-Letter Readiness

Define, for every failed job: Failed-job record · job type · safe input reference (never the full sensitive payload) · correlation ID · attempts · error category · next action · owner · retry eligibility · replay approval · resolution · audit.

**A failed job must not be replayed without verifying source state and idempotency** — restated absolutely as this section's governing rule, directly consistent with [outbox-inbox-idempotency-and-message-reliability.md, Section 4](outbox-inbox-idempotency-and-message-reliability.md#4-idempotency). Replaying a failed job against changed source state (e.g., a since-superseded score) without re-validation risks applying an action against stale facts.

Failed-job review feeds [../05-devops/production-support-access-and-data-repair-operations.md, Section 5](../05-devops/production-support-access-and-data-repair-operations.md#5-queue-replay-and-reconciliation) — restated per [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 2](../05-devops/application-worker-scheduler-and-realtime-deployment.md#2-queue-worker-and-horizon-deployment).

## 5. Queue Replay Governance

Queue replay (re-dispatching a failed or historical job) is a privileged, audited operation — restated from [event-metadata-versioning-ordering-and-correlation.md, Section 5](event-metadata-versioning-ordering-and-correlation.md#5-event-persistence-retention-and-replay). Replay approval requires the same authorization rigor as the original action would have required, never a lesser bar simply because it's "just a retry."

## 6. Queue Backlog Management

A backlog-depth threshold per queue category is a candidate operational alert condition — the specific numeric threshold remains a placeholder pending real pilot-load data, per [../05-devops/service-level-capacity-and-performance-management.md](../05-devops/service-level-capacity-and-performance-management.md). Meet-day readiness explicitly includes confirming no pre-existing queue backlog before the day's high-volume period begins, restated from [../05-devops/meet-day-venue-and-offline-operations.md, Section 1](../05-devops/meet-day-venue-and-offline-operations.md#1-meet-day-operations).

## 7. Worker Isolation Summary

Restated from [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 2](../05-devops/application-worker-scheduler-and-realtime-deployment.md#2-queue-worker-and-horizon-deployment): worker pools are sized per queue-category volume, with `critical` given dedicated, isolated worker capacity; deployment restarts rely on Horizon's graceful termination and requeue; Horizon dashboard access is restricted to Platform/Security Administrator roles with elevated audit.

## 8. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-09 (maximum-retry-attempt and backoff-interval numeric values) and WD-10 (queue-backlog alert threshold per category).
