# PMMS Service-Level, Capacity, and Performance Management

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../04-quality/performance-load-concurrency-and-capacity-testing.md](../04-quality/performance-load-concurrency-and-capacity-testing.md) · [../02-data/indexing-performance-and-capacity.md](../02-data/indexing-performance-and-capacity.md)

This document defines service-level management (indicators, objectives, error budgets), capacity planning, and scaling strategy. **No numeric target is invented** — restated absolutely per working rule 46; every target below is a placeholder with a named decision owner, per working rule 47.

---

## 1. Service-Level Management

### Service-Level Indicators (Candidates)

Availability · latency · error rate · successful QR validations · successful score submissions · result-publication delay · queue-processing delay · sync success · backup success · restore success.

### Service-Level Objectives

**Placeholders pending pilot and stakeholder validation** — restated absolutely; no percentage or duration target is set for any indicator above in this documentation. An SLO is only meaningful once real operational data (from a pilot, per [../04-quality/pilot-operational-and-stakeholder-validation.md](../04-quality/pilot-operational-and-stakeholder-validation.md)) informs what's actually achievable and what DepEd actually needs.

### Operational-Level Objectives

Internal operational commitments supporting the (still-placeholder) service objectives — e.g., an internal target for how quickly the Infrastructure owner investigates a triggered alert, distinct from the external-facing SLO it supports. Not yet defined.

### Error Budgets

A candidate practice for mature production operations (once real SLOs exist, an error budget quantifies acceptable unreliability, informing the trade-off between shipping new features and investing in reliability). **Evaluated, not adopted** — premature for a platform that hasn't yet had its first pilot meet.

**Decision owner for all of the above:** Quality owner + Infrastructure owner, jointly, informed by DepEd Leadership's actual operational expectations — tracked in [devops-open-decisions.md](devops-open-decisions.md).

## 2. Availability Objectives

Restated: **no availability percentage (e.g., "99.9% uptime") is claimed or targeted in this documentation** without the implementation and evidence to support it, per working rule 24. Availability expectations differ meaningfully by workload — the public portal's availability expectations during a medal announcement are not the same as an internal reporting dashboard's — and this differentiation is itself deferred to the SLO-setting exercise above, not invented here.

## 3. Capacity Planning

### Inputs

Athletes · delegations · sports · events · venues · officials · scoring stations · QR scanners · mobile devices · concurrent administrators · public viewers · Reverb connections · upload volume · object storage · access scans · audit events · notifications · reports · sync operations.

### Planning Dimensions

| Dimension | Direction |
|---|---|
| Baseline | Steady-state load, once measured |
| Peak | The highest expected simultaneous load (e.g., gate-opening QR scanning, a medal announcement) |
| Burst | Short-duration spikes beyond peak |
| Growth | Expected increase across future meet cycles (division → regional → national scale, per [../00-product/phase-0.1-product-foundation.md](../00-product/phase-0.1-product-foundation.md)'s expansion direction) |
| Retention | How data volume accumulates over time, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) |
| Safety margin | Deliberate headroom above measured peak, not run at the edge of capacity |
| Pilot measurement | The first genuine capacity data point, per [../04-quality/performance-load-concurrency-and-capacity-testing.md, Section 2](../04-quality/performance-load-concurrency-and-capacity-testing.md#2-load-model-inputs) |
| Review cadence placeholder | Not yet fixed — a candidate quarterly or per-meet-cycle review, pending operational rhythm data |

**No numeric capacity figure is invented** — restated absolutely; every input above awaits real DepEd planning figures and pilot measurement, exactly as [../04-quality/performance-load-concurrency-and-capacity-testing.md, Section 2](../04-quality/performance-load-concurrency-and-capacity-testing.md#2-load-model-inputs) already established.

## 4. Scaling Strategy

Independently scalable workloads, per [deployment-topology-and-runtime-units.md](deployment-topology-and-runtime-units.md): public web · administrative web · APIs · queue workers · notifications · reports · imports · exports · media · Reverb · mobile synchronization · search (once introduced) · MinIO capacity.

**Scaling must follow measured evidence** — restated absolutely; a workload scales when its own metrics (Section, [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md)) demonstrate the need, never speculatively ahead of demonstrated load. This mirrors the same "no premature infrastructure investment" discipline applied throughout every prior phase (e.g., the outbox-pattern deferral in Phase 0.5, the search-engine staging in Phase 0.4).

## 5. Performance Operations

Ongoing performance operations connect [../04-quality/performance-load-concurrency-and-capacity-testing.md](../04-quality/performance-load-concurrency-and-capacity-testing.md)'s testing strategy to real production behavior: performance regressions are detected via the metrics in [observability-logging-metrics-tracing-and-alerting.md, Section 4](observability-logging-metrics-tracing-and-alerting.md#4-metrics-architecture), triaged with the same rigor as a functional defect (per [../04-quality/defect-triage-root-cause-and-quality-debt.md](../04-quality/defect-triage-root-cause-and-quality-debt.md)), and a capacity or scaling response is planned from evidence, not assumption.

## 6. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably when the first real SLO-setting exercise occurs (dependent on pilot completion) and the specific capacity-review cadence once established.
