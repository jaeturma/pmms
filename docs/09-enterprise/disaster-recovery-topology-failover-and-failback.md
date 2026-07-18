# PMMS Disaster-Recovery Topology, Failover, and Failback

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 4](../05-devops/backup-restore-disaster-recovery-and-continuity.md#4-disaster-recovery) · [backup-replication-restore-and-point-in-time-recovery.md](backup-replication-restore-and-point-in-time-recovery.md)

**No DR site, replication, DNS, or failover mechanism is provisioned or configured here.** This document evaluates topology *options* on top of the existing Phase 0.8 DR framework — it does not resolve any of that framework's already-open items.

---

## 1. DR Topology Options (Evaluated, None Selected)

| Topology | Description | Cost/Complexity |
|---|---|---|
| Active-passive readiness | A standby environment activated only on failover | Moderate — the pattern implied by the existing "Disaster Recovery" environment row in [../01-architecture/environment-and-configuration-model.md, Section 1](../01-architecture/environment-and-configuration-model.md#1-environment-model) |
| Warm standby readiness | A standby environment kept partially running (e.g., database replication active, application idle) for faster activation than cold standby | Moderate-High |
| Cold standby readiness | A standby environment provisioned only at failover time from backup | Lower ongoing cost, longer RTO |
| Multi-region readiness | Standby infrastructure in a physically separate region | Highest cost/complexity — a Stage 5/6 candidate only |

**No topology, active-passive/warm/cold model, or specific DR site type is selected** — restated absolutely, consistent with [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 4](../05-devops/backup-restore-disaster-recovery-and-continuity.md#4-disaster-recovery)'s "Recovery site or environment" row already stating this is "contingent on the still-unresolved deployment-topology/cloud-provider decision" ([DV-01](../05-devops/devops-open-decisions.md#dv-01--deployment-topology-and-cloud-versus-on-premise)).

## 2. Recommended Evaluation Sequence

> Cold standby readiness first (lowest cost, matches the pilot-scale topology's operational maturity), evaluated for warm standby once Stage 3 (Production Single Organization) operational maturity and real RTO evidence justify the added cost, with multi-region deferred to Stage 5/6.

This is a recommended *evaluation sequence*, not a committed roadmap — restated per working rule 16 ("use terms such as readiness, candidate architecture, target-state option, decision trigger").

## 3. Failover Governance

Restated unchanged from [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 4](../05-devops/backup-restore-disaster-recovery-and-continuity.md#4-disaster-recovery): trigger (a defined unreachability threshold, duration not yet fixed) · decision authority (Infrastructure owner + Security owner) · backup dependency (a hard dependency — DR recovery depends directly on backup integrity, per [backup-replication-restore-and-point-in-time-recovery.md](backup-replication-restore-and-point-in-time-recovery.md)) · secret/key availability (per [../03-security/cryptography-key-and-secret-management.md](../03-security/cryptography-key-and-secret-management.md)) · access control (DR-environment access follows the same governance as Production) · security validation and application/business validation (per Section 5 below).

## 4. DNS and Routing Recovery

A candidate mechanism for redirecting traffic to a recovered environment — restated unchanged as "not yet designed" from Phase 0.8. This document adds: for a multi-tenant deployment, DNS/routing recovery must correctly redirect tenant-specific custom domains (per [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 4](tenant-configuration-branding-entitlement-and-quota-architecture.md#4-white-label-and-custom-domain-readiness)) where they exist, not only the primary platform domain.

## 5. Business and Security Validation After Recovery

**DR recovery must validate business integrity, not only infrastructure health** — restated absolutely per working rule 56. Restated from [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 2](../05-devops/backup-restore-disaster-recovery-and-continuity.md#2-restore-architecture) steps 16–17 (security review, application/business validation): a recovered environment is not trusted as the new primary until official-results/medal-tally state, credential/revocation state, and workflow/event/projection consistency are all explicitly verified — restated per working rule 58's full recovery scope (MySQL, MinIO, keys, secrets, workflows, events, projections, synchronization reconciliation).

## 6. Failback Governance

**A defined process for returning to the primary environment once recovered — not yet designed**, restated unchanged from Phase 0.8. This document adds only the tenant-aware consideration: failback must re-verify tenant-isolation integrity was preserved throughout the failover/recovery window, not merely restored infrastructure health.

## 7. Key and Secret Recovery

Restated unchanged: recoverable per [../03-security/cryptography-key-and-secret-management.md](../03-security/cryptography-key-and-secret-management.md), Section 3 (secret-recovery procedure) — no key-management product is selected.

## 8. Object-Storage, Queue, Event, and Projection Recovery

Restated unchanged from the existing 19-step restore sequence in [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 2](../05-devops/backup-restore-disaster-recovery-and-continuity.md#2-restore-architecture): MinIO objects restored per Section 5's lock-step principle; queue/outbox state reviewed before resuming processing; projections and search indexes rebuilt (never restored directly, since they are derived and rebuildable, per working rules 33–34); offline-sync reconciliation and device-state recovery follow [../01-architecture/offline-sync-runtime-architecture.md, Section 4](../01-architecture/offline-sync-runtime-architecture.md#4-conflict-detection-and-resolution)'s existing conflict-handling discipline, unchanged.

## 9. Multi-Region Readiness (Not Committed)

Evaluated only as a Stage 5/6 candidate, contingent on: a specific data-residency requirement (per [enterprise-security-compliance-audit-and-data-residency-readiness.md, Section 1](enterprise-security-compliance-audit-and-data-residency-readiness.md#1-data-residency-readiness)), a demonstrated latency need for geographically distant users, or a contractual regional-deployment commitment. No region is provisioned or selected.

## 10. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-34 (DR topology selection, contingent on DV-01) and ED-35 (failback process design), both continuing the same unresolved chain as [DV-16](../05-devops/devops-open-decisions.md#dv-16--disaster-recovery-environment-provisioning-timing).
