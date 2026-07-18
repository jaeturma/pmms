# PMMS Cost, Resource, and Capacity Governance

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [service-level-capacity-and-performance-management.md](service-level-capacity-and-performance-management.md) · [../00-product/phase-0.1-product-foundation.md, Section 17](../00-product/phase-0.1-product-foundation.md#17-deployment-model)

This document defines cost-governance and cloud-versus-on-premise evaluation. **No cloud provider is selected unless already approved**, per working rule 23 — none is approved as of this phase.

---

## 1. Capacity Reviews

A periodic (cadence not yet fixed) review connecting [service-level-capacity-and-performance-management.md, Section 3](service-level-capacity-and-performance-management.md#3-capacity-planning)'s planning inputs to actual measured usage — confirming whether current provisioning still matches real load, whether growth trends require proactive scaling, and whether any workload has drifted from its expected capacity profile. Owned jointly by the Infrastructure owner and Quality owner, feeding both [service-level-capacity-and-performance-management.md](service-level-capacity-and-performance-management.md) and this document's cost governance.

## 2. Cost Management

| Consideration | Direction |
|---|---|
| Provisioning proportionate to actual need | Restated from [devops-and-platform-operations-strategy.md, Section 2](devops-and-platform-operations-strategy.md#2-devops-principles) — infrastructure is scaled from measured evidence, never provisioned speculatively ahead of demonstrated load, which is itself a cost-governance principle as much as a technical one |
| Environment cost tiering | Lower environments (Local, Automated Test) are inherently low-cost; Staging/Pilot/Production carry progressively more real infrastructure cost, informing provisioning priority per [environment-architecture.md, Section 1](environment-architecture.md#1-environment-strategy) |
| Managed versus self-hosted trade-off | A managed MySQL/Redis/MinIO service reduces operational burden at a cost premium versus self-hosting — evaluated per Section 3, not decided here |
| Storage growth cost | Object storage (MinIO) and database growth (per [../02-data/indexing-performance-and-capacity.md, Section 2](../02-data/indexing-performance-and-capacity.md#2-capacity-and-growth-categories)) directly drive long-term cost, making retention-period finalization (currently a placeholder, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md)) a cost-relevant decision, not merely a compliance one |
| Backup/DR cost | A DR environment (per [backup-restore-disaster-recovery-and-continuity.md, Section 4](backup-restore-disaster-recovery-and-continuity.md#4-disaster-recovery)) roughly doubles infrastructure cost for the components it covers — a deliberate trade-off against recovery-capability need, not a default |
| Vendor cost | No vendor is currently approved, per [../03-security/vendor-and-third-party-risk.md, Section 3](../03-security/vendor-and-third-party-risk.md#3-currently-approved-vendors) — any future vendor's cost is assessed alongside its risk profile during the Phase 0.6 vendor-assessment process |
| Cost ownership | DepEd Leadership + Infrastructure owner jointly, given the institutional (not commercial-revenue-funded) nature of PMMS's operating model |

## 3. Cloud-Versus-On-Premise Strategy

| Option | Consideration |
|---|---|
| Cloud | Lower upfront capital cost, elastic scaling, managed-service options for MySQL/Redis/MinIO reducing operational burden — ongoing operational cost, and a specific provider choice not yet approved |
| On-premise | Higher upfront capital cost, full infrastructure control, potentially better fit for specific DepEd data-residency or budget-cycle preferences — higher operational burden (the team must run everything itself) |
| Hybrid | The Hybrid Venue Topology (per [deployment-topology-and-runtime-units.md, Section 1](deployment-topology-and-runtime-units.md#1-deployment-topology)) already assumes a *venue-level* hybrid regardless of whether the *central* system is cloud or on-premise — this section addresses the central system's hosting model specifically |

**No cloud provider is selected unless already approved** — restated absolutely per working rule 23; this evaluation restates and does not resolve [../00-product/phase-0.1-product-foundation.md, Section 17](../00-product/phase-0.1-product-foundation.md#17-deployment-model)'s still-open deployment-model question, carried unchanged since Phase 0.1 through every subsequent phase (most recently restated as [../03-security/security-open-decisions.md, SD-22](../03-security/security-open-decisions.md#sd-22--deployment-topology-cross-reference)).

## 4. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably the cloud-versus-on-premise decision itself (the single largest unresolved DevOps question, blocking multiple other decisions in this package) and capacity-review cadence.
