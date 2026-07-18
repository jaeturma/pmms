# PMMS Horizon, Queue, Worker, and Background-Processing Scaling

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../08-workflows/queue-routing-priority-retry-and-failure-architecture.md](../08-workflows/queue-routing-priority-retry-and-failure-architecture.md) · [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 2](../05-devops/application-worker-scheduler-and-realtime-deployment.md#2-queue-worker-and-horizon-deployment)

**No Horizon or queue configuration is created here.**

---

## 1. Queue Scaling

Independent worker pools per queue category (the 11-category architecture from Phase 0.4, unchanged) · queue priorities (unchanged) · autoscaling readiness (worker count adjusts to backlog, a candidate future capability) · tenant fairness (Section, [noisy-neighbor-fair-use-and-resource-governance.md, Section 3](noisy-neighbor-fair-use-and-resource-governance.md#3-queue-fairness-and-tenant-workload-prioritization)) · per-job cost awareness · queue latency monitoring · backpressure (Section, [backpressure-load-shedding-graceful-degradation-and-brownout.md](backpressure-load-shedding-graceful-degradation-and-brownout.md)) · concurrency controls · idempotency (unchanged, restated from Phase 0.11) · deployment compatibility (a queue-scaling change must not break in-flight jobs) · failed-job isolation (a failing job type does not consume capacity intended for healthy jobs).

**Queue scaling must preserve idempotency and ordering requirements** — restated absolutely per working rule 52; adding worker capacity never relaxes the idempotency or per-aggregate ordering discipline established in [../08-workflows/event-metadata-versioning-ordering-and-correlation.md, Section 4](../08-workflows/event-metadata-versioning-ordering-and-correlation.md#4-event-ordering).

## 2. Horizon Scaling (Documentation Only)

Documented, not configured: supervisor groups (one per queue category or isolation boundary) · worker allocation (proportional to measured workload volume) · queue balancing (auto-balance within isolation boundaries, restated from [../01-architecture/event-and-queue-architecture.md, Section 3](../01-architecture/event-and-queue-architecture.md#3-laravel-horizon-architecture)) · long-running job isolation (a job exceeding its expected duration is isolated from short-job capacity) · memory controls (worker recycling to prevent memory growth) · metrics (queue depth, processing time, failure rate, per category) · tenant-aware diagnostics (a support engineer can filter Horizon's failed-job view by tenant) · critical-queue protection (the `critical` queue's worker pool is never reduced to free capacity for a lower-priority category, restated absolutely).

## 3. Background-Processing Scaling Principles

| Principle | Direction |
|---|---|
| Bulk processing is chunked | Restated unchanged from [../01-architecture/event-and-queue-architecture.md, Section 6](../01-architecture/event-and-queue-architecture.md#6-chunking-and-bulk-work) |
| Long jobs report progress | Where user-facing, restated unchanged from Phase 0.4 |
| Worker pools scale independently per category | Restated from [scalability-and-workload-isolation-architecture.md, Section 3](scalability-and-workload-isolation-architecture.md#3-workload-isolation-rules) |
| AI-assisted background tasks require the same idempotency as any other job | Restated from [../07-ai/ai-observability-cost-quotas-and-operations.md, Section 4](../07-ai/ai-observability-cost-quotas-and-operations.md#4-failure-and-fallback) — no AI capability is active, but the architecture is consistent should one ever be approved |

## 4. Worker Fleet Sizing

Worker fleet size is derived from measured job throughput and latency requirements per queue category — no specific worker count is invented. Sizing evidence feeds the same Capacity Planning Dimensions table established in [../05-devops/service-level-capacity-and-performance-management.md, Section 3](../05-devops/service-level-capacity-and-performance-management.md#3-capacity-planning).

## 5. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-22 (Horizon autoscaling adoption trigger) and ED-23 (per-category worker-fleet baseline sizing, deferred pending pilot evidence).
