# PMMS Noisy-Neighbor, Fair-Use, and Resource Governance

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 6](tenant-configuration-branding-entitlement-and-quota-architecture.md#6-tenant-quotas) · [redis-cache-session-lock-and-rate-limit-scaling.md, Section 8](redis-cache-session-lock-and-rate-limit-scaling.md#8-rate-limiting)

---

## 1. Noisy-Neighbor Protection

Controls: per-tenant rate limits · quotas (per [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 6](tenant-configuration-branding-entitlement-and-quota-architecture.md#6-tenant-quotas)) · queue fairness (Section 3) · concurrency limits · storage limits · AI budgets (none active, per Phase 0.10's cost-quota architecture) · export limits · report limits · Reverb connection limits · import limits · support alerts (a noisy-tenant pattern triggers an operational alert, not merely a silent throttle).

**Noisy tenants must not degrade other tenants without controls** — restated absolutely per working rule 55, the governing rule of this entire document.

## 2. Resource Governance Allocation Categories

| Category | Definition |
|---|---|
| Shared baseline | The default capacity every tenant receives, undifferentiated |
| Tenant quota | The specific limit a tenant's entitlement tier permits, per [tenant-configuration-branding-entitlement-and-quota-architecture.md](tenant-configuration-branding-entitlement-and-quota-architecture.md) |
| Meet peak allocation | A temporary elevated allocation for a tenant's active meet-day period, evaluated as a candidate mechanism, not committed |
| Critical workload reserve | Capacity reserved for Section, [workload-capacity-and-scale-assumptions.md, Section 3](workload-capacity-and-scale-assumptions.md#3-critical-workloads)'s critical workloads, never allocated away regardless of tenant demand |
| Emergency reserve | Platform-level headroom held back from ordinary allocation, for incident response |
| Dedicated tenant resources | Where contracted (Stage 5+, per [tenant-data-ownership-and-isolation-architecture.md, Section 1](tenant-data-ownership-and-isolation-architecture.md#1-tenant-isolation-strategy-options)) |

## 3. Queue Fairness and Tenant Workload Prioritization

Within a shared queue category, jobs from different tenants are processed fairly (e.g., round-robin or weighted-fair scheduling within the category) rather than strict FIFO, which would let one tenant's large batch job starve another tenant's smaller, time-sensitive job in the same category. This is evaluated as a scaling mechanism, not implemented in this phase.

## 4. Public-Versus-Admin Workload Isolation (Cross-Reference)

Full detail: [scalability-and-workload-isolation-architecture.md, Section 3](scalability-and-workload-isolation-architecture.md#3-workload-isolation-rules) — public traffic is, in effect, the platform's largest "noisy neighbor" risk to administrative/critical workloads, and receives the same isolation discipline as any tenant-level noisy-neighbor risk.

## 5. Critical-Workload Protection (Cross-Reference)

Full detail: [backpressure-load-shedding-graceful-degradation-and-brownout.md, Section 2](backpressure-load-shedding-graceful-degradation-and-brownout.md#2-load-shedding-priority-order) — the same priority order governs resource governance under both noisy-neighbor pressure and general platform overload.

## 6. Fair-Use Enforcement and Support Escalation

A tenant approaching or exceeding fair-use limits receives a warning (per [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 6](tenant-configuration-branding-entitlement-and-quota-architecture.md#6-tenant-quotas)) before hard enforcement; a legitimate need for elevated capacity (e.g., an unusually large regional meet) routes through the support escalation process, not an unmonitored quota override.

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-30 (meet peak allocation mechanism, if ever adopted) and ED-31 (queue fair-scheduling algorithm selection).
