# PMMS Workload, Capacity, and Scale Assumptions

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../05-devops/service-level-capacity-and-performance-management.md, Section 3](../05-devops/service-level-capacity-and-performance-management.md#3-capacity-planning) · [../02-data/indexing-performance-and-capacity.md](../02-data/indexing-performance-and-capacity.md)

---

## 1. Workload Classification

Workloads are classified by: criticality · latency sensitivity · consistency requirement · volume · burst behavior · compute/memory/storage/network intensity · user visibility · tenant scope · offline relevance · recoverability.

### Candidate Workload Groups

| Group | Criticality | Consistency | Burst Behavior |
|---|---|---|---|
| Authentication and authorization | Critical | Strong | Steady, spikes at meet-day start |
| Administrative requests | Standard–High | Strong | Business-hours steady |
| Public portal reads | Standard | Eventual (projections) | Extreme burst during result/medal announcements |
| QR validation | Critical | Strong (with offline-provisional tolerance) | Extreme burst at gate-opening times |
| Score entry | Critical | Strong | Sustained burst during active events |
| Result certification | Critical | Strong | Burst following each event's conclusion |
| Live scoreboard | Standard | Eventual/transient | Sustained high-frequency during competition |
| Mobile synchronization | High | Eventual, reconciled | Burst on reconnect after venue connectivity gaps |
| Files | Standard | Eventual | Burst during accreditation/document intake |
| Imports/exports | Standard | Eventual | Scheduled or on-demand burst |
| Notifications | Standard | Eventual | Burst on mass-trigger events (e.g., `MeetActivated`) |
| Analytics | Low–Standard | Eventual | Off-peak batch |
| AI (candidate, none active) | Low–Standard | Eventual | N/A — no capability enabled per Phase 0.10 |
| Reporting | Standard | Eventual | Scheduled or on-demand |
| Maintenance | Low | N/A | Scheduled, off-peak preferred |

## 2. Scale Evidence Requirements

**No workload volume, concurrent-user number, or growth rate is invented** — restated absolutely per working rule 17. Every capacity decision requires evidence from: pilot meets · concurrent users · public viewers · number of organizations · active meets · athletes · delegations · events · venues · QR scans · score submissions · real-time connections · mobile sync volume · files · storage growth · reports · notifications · queue latency · query latency · peak and average loads · backup duration · restore duration · incident patterns.

This directly extends [../05-devops/service-level-capacity-and-performance-management.md, Section 3](../05-devops/service-level-capacity-and-performance-management.md#3-capacity-planning)'s Capacity Planning Dimensions (Baseline, Peak, Burst, Growth, Retention, Safety margin, Pilot measurement, Review cadence) — this document does not redefine those dimensions, it names the specific evidence that populates them.

## 3. Critical Workloads

Restated absolutely: authentication · authorization · score submission · result certification · credential validation · security revocation · audit recording · official publication state · workflow state transitions. **These workloads must receive stronger protection than optional analytics, large exports, or AI generation** — restated as this document's governing rule, feeding [backpressure-load-shedding-graceful-degradation-and-brownout.md, Section 2](backpressure-load-shedding-graceful-degradation-and-brownout.md#2-load-shedding-priority-order) directly.

## 4. Peak-Load Scenarios (Named, Not Sized)

Registration peaks · accreditation peaks · QR-validation peaks · live-scoring peaks · result-publication peaks · medal-tally peaks · media-upload peaks · reporting peaks · meet-closure workload — each a named scenario for future load-testing (per [enterprise-testing-benchmarking-and-readiness-gates.md](enterprise-testing-benchmarking-and-readiness-gates.md)), none sized numerically here.

## 5. Multi-Meet Concurrency

PMMS's roadmap (per [Phase 0.1, Section 3](../00-product/phase-0.1-product-foundation.md#3-product-identity)) anticipates provincial → division → regional → national scale growth — multi-meet concurrency is a readiness dimension the capacity model must account for, never assumed away, even though [OD-03](../00-product/open-decisions.md#od-03--single-meet-versus-multi-meet-launch) (Single-Meet Versus Multi-Meet Launch) remains open.

## 6. Growth Direction (Qualitative Only)

Expected growth direction: provincial → division → regional → national meet scale, and single-organization → multi-organization tenancy (contingent on OD-02). No numeric growth rate, timeline, or target user count is invented — every such figure is a placeholder pending pilot evidence, per [performance-budget-and-service-level-architecture.md, Section 3](performance-budget-and-service-level-architecture.md#2-service-level-architecture).

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-01 (first capacity-evidence-gathering exercise timing) and ED-02 (multi-meet concurrency target, contingent on OD-03).
