# PMMS Enterprise Risk Register

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md) (Phase 0.1 general risk register, prefix `RSK-`) · [../03-security/security-risk-register.md](../03-security/security-risk-register.md) (Phase 0.6 security risk register)

This register tracks enterprise-scaling-and-multi-tenancy-specific risks using ID prefix `ERK-` (Enterprise Risk), distinct from Phase 0.1's `RSK-` and any security-specific risk IDs in Phase 0.6. It does not duplicate either — it covers risks specific to this phase's scope only.

---

| ID | Risk | Likelihood | Impact | Mitigation (Architectural) | Residual Risk |
|---|---|---|---|---|---|
| ERK-01 | A missing tenant filter in a query exposes one tenant's data to another | Medium (human error in a shared-schema model) | Critical | Defense-in-depth isolation (Section, [tenant-data-ownership-and-isolation-architecture.md, Section 3](tenant-data-ownership-and-isolation-architecture.md#3-defense-in-depth-tenant-isolation)) plus mandatory tenant-isolation testing (per [enterprise-testing-benchmarking-and-readiness-gates.md](enterprise-testing-benchmarking-and-readiness-gates.md)) | Present until implementation and test coverage exist |
| ERK-02 | A noisy tenant degrades platform-wide performance for all other tenants | Medium (grows with tenant count) | High | Quotas, rate limits, queue fairness (per [noisy-neighbor-fair-use-and-resource-governance.md](noisy-neighbor-fair-use-and-resource-governance.md)) | Present until quota enforcement is implemented |
| ERK-03 | Public traffic burst starves critical scoring/accreditation workloads during a major meet | Medium–High (predictable at result-announcement moments) | Critical | Workload isolation, load shedding priority order (per [backpressure-load-shedding-graceful-degradation-and-brownout.md](backpressure-load-shedding-graceful-degradation-and-brownout.md)) | Present until isolation is implemented and load-tested |
| ERK-04 | An untested DR recovery fails when actually needed | Medium (untested capabilities frequently fail) | Critical | Restore-drill discipline (per [backup-replication-restore-and-point-in-time-recovery.md, Section 6](backup-replication-restore-and-point-in-time-recovery.md#6-restore-validation)) | Present until a real DR exercise occurs |
| ERK-05 | Scaling optimizations (caching, replicas) introduce stale or inconsistent data into a high-integrity workflow | Medium | Critical | Explicit read-replica/cache exclusion rules for high-integrity paths (per [mysql-performance-replication-partitioning-and-scaling-readiness.md, Section 3](mysql-performance-replication-partitioning-and-scaling-readiness.md#3-read-replica-readiness), working rule 51) | Present until implementation enforces the exclusion |
| ERK-06 | Premature adoption of a complex scaling mechanism (sharding, Kubernetes, multi-region) before evidence justifies it, wasting engineering capacity | Medium | Moderate | Evidence-gated maturity model (per [enterprise-readiness-vision-principles-and-maturity-model.md, Section 3](enterprise-readiness-vision-principles-and-maturity-model.md#3-enterprise-maturity-model)), working rules 14/17 | Low, given this architecture's explicit evidence-gating discipline |
| ERK-07 | A platform administrator's access to tenant lifecycle management is misused to view protected tenant data without authorization | Low–Medium | Critical | Explicit restriction (working rule 38), audited cross-tenant access (working rule 39) | Present until implementation enforces the restriction |
| ERK-08 | Metering inaccuracy leads to incorrect future billing, damaging tenant trust | Low (no billing exists yet) | Moderate (grows once billing exists) | Reconciliation discipline (per [tenant-metering-licensing-subscription-and-billing-readiness.md, Section 2](tenant-metering-licensing-subscription-and-billing-readiness.md#2-metering-architecture)) | Deferred — not yet a live risk |
| ERK-09 | Tenant offboarding deletes data still subject to a legal hold or retention obligation | Low | Critical | Deletion-review gate (per [tenant-onboarding-suspension-offboarding-and-portability.md, Section 5](tenant-onboarding-suspension-offboarding-and-portability.md#5-tenant-deletion-review)) | Present until implementation enforces the gate |
| ERK-10 | A DR failover restores infrastructure but not verified business/security integrity, presenting an unreconciled environment as trustworthy | Medium | Critical | Mandatory business/security validation before treating a recovered environment as primary (per [disaster-recovery-topology-failover-and-failback.md, Section 5](disaster-recovery-topology-failover-and-failback.md#5-business-and-security-validation-after-recovery), working rule 56) | Present until a real DR exercise validates the process |

## Summary of Highest-Priority Enterprise Risks

| Risk | Why It Is Highest Priority |
|---|---|
| **ERK-01** | The single most consequential failure mode multi-tenancy introduces — a tenant-isolation breach is, in substance, a security incident |
| **ERK-03** | Directly threatens PMMS's single most important scaling rule (public traffic must not starve critical workflows) at the platform's highest-visibility moments |
| **ERK-04 / ERK-10** | An unverified DR capability is not a real capability — restated absolutely per working rule 15, this register exists partly to keep that discipline visible |
