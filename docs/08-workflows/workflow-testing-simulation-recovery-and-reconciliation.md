# PMMS Workflow Testing, Simulation, Recovery, and Reconciliation

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../04-quality/queue-realtime-cache-and-storage-testing.md](../04-quality/queue-realtime-cache-and-storage-testing.md) · [../04-quality/high-integrity-sports-workflow-testing.md](../04-quality/high-integrity-sports-workflow-testing.md)

**No test code, factory, fixture, or CI workflow is created here.**

---

## 1. Workflow Testing

Every workflow, extending [../04-quality/risk-based-testing-model.md](../04-quality/risk-based-testing-model.md)'s risk-tiered depth to workflow-specific test types:

| Test Type | Purpose |
|---|---|
| State-transition testing | Every valid and invalid transition is tested, per [../04-quality/domain-and-application-testing.md](../04-quality/domain-and-application-testing.md) |
| Event-contract testing | Producer/consumer compatibility per event version, per [../04-quality/api-contract-and-integration-testing.md](../04-quality/api-contract-and-integration-testing.md) |
| Queue/job testing | Retry, idempotency, and failure-handling behavior, per [../04-quality/queue-realtime-cache-and-storage-testing.md](../04-quality/queue-realtime-cache-and-storage-testing.md) |
| Notification testing | Delivery, deduplication, and mandatory-notice-cannot-be-suppressed behavior |
| Automation testing | Every automation entry (per [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model)) is tested before activation, restated per working rule 12 (Section, [responsible-automation-and-authority-boundaries.md, Section 1](responsible-automation-and-authority-boundaries.md#1-responsible-automation-principles-13)) |
| SOD/authorization testing | Every structural separation-of-duties rule is verified to actually block the prohibited combination, never merely documented |
| High-integrity sports-workflow testing | No sport-specific rule invented for a test scenario, restated absolutely from [../04-quality/high-integrity-sports-workflow-testing.md, Section 1](../04-quality/high-integrity-sports-workflow-testing.md#1-governing-principle) |
| Regression/UAT | Restated from [../04-quality/regression-smoke-exploratory-and-uat-strategy.md](../04-quality/regression-smoke-exploratory-and-uat-strategy.md) |

## 2. Workflow Simulation

A candidate practice of exercising a workflow definition end-to-end against synthetic data before real-world activation — evaluated, not committed to a specific tool or mechanism in this phase. Simulation is distinct from testing: testing verifies individual transitions and contracts; simulation verifies the whole workflow's emergent behavior (timing, fan-out, escalation) under realistic load and timing conditions.

## 3. Workflow Reconciliation

Define reconciliation for: domain state versus workflow state · workflow state versus pending tasks · outbox versus consumers · notification intent versus delivery · certified results versus medal tally · credential status versus access validation · offline submissions versus server state · public projection versus publication state.

Reconciliation is a scheduled or on-demand comparison producing a discrepancy report for human review — it never silently "corrects" a discrepancy by picking one side automatically, restated from the same discipline as [../01-architecture/offline-sync-runtime-architecture.md, Section 4](../01-architecture/offline-sync-runtime-architecture.md#4-conflict-detection-and-resolution)'s conflict handling, generalized from offline sync to workflow reconciliation broadly.

## 4. Workflow Recovery

| Failure Point | Recovery Approach |
|---|---|
| A crashed process manager mid-fan-out | Resume from durable process-instance state (Section, [business-process-and-state-machine-architecture.md, Section 3](business-process-and-state-machine-architecture.md#3-long-running-workflow-architecture)), never restart from scratch and risk duplicate side effects (idempotency protects against this) |
| A missed or dropped event | Outbox-based redelivery where evaluated (Section, [outbox-inbox-idempotency-and-message-reliability.md, Section 1](outbox-inbox-idempotency-and-message-reliability.md#1-transactional-outbox-evaluation)), or manual reconciliation detection followed by authorized replay |
| A failed job exhausting retries | Failed-job record review (Section, [queue-routing-priority-retry-and-failure-architecture.md, Section 4](queue-routing-priority-retry-and-failure-architecture.md#4-failed-jobs-and-dead-letter-readiness)), manual verification of source state before replay |
| A stalled long-running instance | Support-tool inspection and, where appropriate, manual intervention (Section, [workflow-audit-observability-metrics-and-support.md, Section 6](workflow-audit-observability-metrics-and-support.md#6-pause-resume-cancellation-withdrawal-reopen-and-manual-intervention)) |

## 5. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-31 (whether a dedicated workflow-simulation tool is ever built, or whether staging-environment rehearsal suffices).
