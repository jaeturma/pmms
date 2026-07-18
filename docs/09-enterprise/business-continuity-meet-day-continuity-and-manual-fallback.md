# PMMS Business Continuity, Meet-Day Continuity, and Manual Fallback

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 5](../05-devops/backup-restore-disaster-recovery-and-continuity.md#5-business-continuity-and-resilience-operations) · [../05-devops/meet-day-venue-and-offline-operations.md](../05-devops/meet-day-venue-and-offline-operations.md)

---

## 1. Business Continuity

Restated and extended from [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 5](../05-devops/backup-restore-disaster-recovery-and-continuity.md#5-business-continuity-and-resilience-operations): business continuity is the operational discipline ensuring PMMS's *business processes* (not merely infrastructure) continue functioning during a disruption — distinct from, but dependent on, the technical DR architecture in [disaster-recovery-topology-failover-and-failback.md](disaster-recovery-topology-failover-and-failback.md).

## 2. Meet-Day Continuity

Meet-day is PMMS's highest-stakes continuity scenario — restated from [../05-devops/meet-day-venue-and-offline-operations.md, Section 1](../05-devops/meet-day-venue-and-offline-operations.md#1-meet-day-operations), which already establishes the change freeze, Reverb-capacity readiness check, and queue-backlog readiness check. This document adds the enterprise-scale continuity layer:

| Element | Direction |
|---|---|
| Meet-day capacity pre-check | Confirms capacity headroom (per [workload-capacity-and-scale-assumptions.md](workload-capacity-and-scale-assumptions.md)) before a large meet's peak load window, extending the existing Reverb/queue readiness checks |
| Meet-day incident command | A defined operational role/process for a live meet-day disruption — evaluated, not committed to a specific structure in this phase |
| Meet-day degradation priority | Follows [backpressure-load-shedding-graceful-degradation-and-brownout.md, Section 2](backpressure-load-shedding-graceful-degradation-and-brownout.md#2-load-shedding-priority-order) exactly — scoring and accreditation are never sacrificed to preserve a non-critical capability during a meet-day incident |
| Multi-tenant meet-day isolation | A capacity incident affecting one tenant's meet-day must not degrade another tenant's concurrent, unrelated meet — restated from [noisy-neighbor-fair-use-and-resource-governance.md](noisy-neighbor-fair-use-and-resource-governance.md) |

## 3. Manual Fallback

Every critical workflow retains a manual, non-digital fallback path — restated from [../05-devops/meet-day-venue-and-offline-operations.md](../05-devops/meet-day-venue-and-offline-operations.md) and Phase 0.9's printable/offline-artifact requirement. Manual fallback is not a degraded afterthought; it is a designed, exercised continuity mechanism for the scenario where digital systems are entirely unavailable (e.g., total venue power/network loss).

| Critical Workflow | Manual Fallback |
|---|---|
| Score entry | Paper scoresheets, later transcribed and reconciled against the digital record |
| Access validation | Physical credential inspection by a Security-assigned official |
| Result certification | Signed paper certification, later entered and reconciled |
| Medal tally | Manual tally computation from paper results, reconciled against system computation once restored |

Reconciliation after a manual-fallback period follows the same discipline as [../08-workflows/workflow-testing-simulation-recovery-and-reconciliation.md, Section 3](../08-workflows/workflow-testing-simulation-recovery-and-reconciliation.md#3-workflow-reconciliation) — discrepancies are surfaced for human review, never silently auto-resolved.

## 4. Offline Continuity and Hybrid Deployment

Restated unchanged from [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) and the Hybrid Venue Topology candidate in [../05-devops/deployment-topology-and-runtime-units.md, Section 1](../05-devops/deployment-topology-and-runtime-units.md#1-deployment-topology): an optional local venue server extends offline continuity for large or remote venues, evaluated, not committed.

## 5. Venue-Local Resilience

A venue's local network, device fleet, and (where deployed) local venue server together form a resilience unit that must continue critical operations (scoring, access validation) independent of central connectivity for the offline-tolerance duration already established in Phase 0.4's offline authorization model — restated unchanged, no new duration is invented here.

## 6. Relationship to Disaster Recovery

Business continuity and meet-day continuity are the *operational* response to a disruption; disaster recovery (per [disaster-recovery-topology-failover-and-failback.md](disaster-recovery-topology-failover-and-failback.md)) is the *technical* response — the two are complementary, and a meet-day incident does not necessarily require a full DR failover if manual fallback and offline continuity are sufficient to bridge the gap.

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-36 (meet-day incident-command structure) and ED-37 (local venue server adoption trigger, mirroring the existing Hybrid Venue Topology open item).
