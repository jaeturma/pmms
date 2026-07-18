# PMMS Reporting, Search, Analytics, and Data-Platform Readiness

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/reporting-search-and-read-model-runtime.md](../01-architecture/reporting-search-and-read-model-runtime.md) · [../01-architecture/reporting-and-read-model-boundaries.md](../01-architecture/reporting-and-read-model-boundaries.md)

---

## 1. Reporting Scale

Operational reports · public reports · executive dashboards · historical analytics · large exports · post-meet analysis.

Use: read models · snapshots · asynchronous generation (via the `exports`/`analytics` queue categories, per [../08-workflows/queue-routing-priority-retry-and-failure-architecture.md, Section 1](../08-workflows/queue-routing-priority-retry-and-failure-architecture.md#1-queue-architecture-validated-against-phase-04)) · downstream analytics · cached approved results · tenant isolation · privacy filtering (restated unchanged from Phase 0.5's export/privacy discipline).

## 2. Search Scale

Restated unchanged from [../01-architecture/reporting-search-and-read-model-runtime.md, Section 3](../01-architecture/reporting-search-and-read-model-runtime.md#3-search-architecture)'s staged approach: MySQL-backed search initially → optimized indexes and dedicated read models as measured needs emerge → an external search engine only when justified, never adopted preemptively.

**Search indexes must be**: tenant-aware (Section, [tenant-aware-runtime-workflow-event-and-ai-boundaries.md, Section 10](tenant-aware-runtime-workflow-event-and-ai-boundaries.md#10-tenant-aware-object-storage-and-search-cross-reference)) · classification-aware (never indexing Restricted/Highly Restricted content into a broadly-queryable index) · rebuildable · source-version-aware · privacy-filtered · monitored for freshness. Restated absolutely per working rule 34 — search indexes remain derived and rebuildable, never authoritative.

## 3. AI Retrieval Scaling (Cross-Reference)

Full detail: [../07-ai/retrieval-knowledge-and-semantic-search-architecture.md](../07-ai/retrieval-knowledge-and-semantic-search-architecture.md) — no embedding or vector index exists (Phase 0.10 confirmed); if one is ever approved, this document's tenant-isolation and rebuildability requirements apply to it identically to any other search index.

## 4. Cross-Tenant Analytics

Only permitted when: purpose is approved · data is de-identified or appropriately aggregated · small-group disclosure risk is controlled (a de-identified aggregate covering too few records can still re-identify individuals) · tenant agreements allow it · access is restricted · outputs are audited.

**Do not expose one tenant's identifiable data to another** — restated absolutely as this section's governing rule. Restated per working rule 35 — analytics stores remain downstream, never authoritative.

## 5. Data-Warehouse Readiness

Readiness defined for: historical meet analysis · cross-meet trends · platform operations · tenant-specific analytics · de-identified benchmarking (per [tenant-metering-licensing-subscription-and-billing-readiness.md, Section 6](tenant-metering-licensing-subscription-and-billing-readiness.md#6-cross-tenant-analytics-restrictions-and-benchmarking-cross-reference)) · performance analytics.

**Do not move transactional authority to the warehouse** — restated absolutely; a data warehouse, if ever adopted, is a downstream, rebuildable analytical store, never a second authoritative copy of transactional data. This directly extends [../01-architecture/domain-open-decisions.md, DD-25](../01-architecture/domain-open-decisions.md#dd-25--data-warehouse-timing) ("a dedicated data warehouse is explicitly deferred, not built prematurely"), restated unchanged.

## 6. Event-Stream Readiness

A future analytics pipeline may consume domain events as an event stream (rather than periodic batch extraction) — evaluated only alongside the still-undecided outbox pattern (per [../08-workflows/outbox-inbox-idempotency-and-message-reliability.md, Section 1](../08-workflows/outbox-inbox-idempotency-and-message-reliability.md#1-transactional-outbox-evaluation)), since a reliable event stream depends on the same delivery-reliability mechanism.

## 7. Bulk-Processing Architecture

Restated unchanged from [../01-architecture/event-and-queue-architecture.md, Section 6](../01-architecture/event-and-queue-architecture.md#6-chunking-and-bulk-work): bulk report/export/analytics work is chunked, never a single unbounded query or job.

## 8. Isolation From Transactional Write Load

Restated unchanged from [../01-architecture/reporting-search-and-read-model-runtime.md, Section 6](../01-architecture/reporting-search-and-read-model-runtime.md#6-isolation-from-transactional-write-load) — the specific runtime isolation mechanism (read replica, dedicated cache layer, separate connection pool) remains an open decision, cross-referenced in [mysql-performance-replication-partitioning-and-scaling-readiness.md, Section 3](mysql-performance-replication-partitioning-and-scaling-readiness.md#3-read-replica-readiness).

## 9. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-28 (external search-engine adoption trigger) and the existing [DD-25](../01-architecture/domain-open-decisions.md#dd-25--data-warehouse-timing) cross-reference.
