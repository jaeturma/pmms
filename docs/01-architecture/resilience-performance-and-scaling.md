# PMMS Resilience, Performance, and Scaling

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [environment-and-configuration-model.md](environment-and-configuration-model.md) · [event-and-queue-architecture.md](event-and-queue-architecture.md) · [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)

This document defines scaling boundaries, performance principles, availability/resilience patterns, and backup/recovery considerations. **No infrastructure is provisioned and no numerical target is invented here** — where Phase 0.1 did not establish a target, this document says so explicitly rather than fabricating one.

---

## 1. Scaling Boundaries

### Independently Scalable Workloads
Public traffic, administrative web requests, API traffic, queue workers, notifications, imports/exports, media processing, report generation, Reverb connections, public projections, search, mobile synchronization, file storage.

### Rules
- **Scaling follows measured bottlenecks** — capacity is added where monitoring (per [observability-and-error-handling.md](observability-and-error-handling.md)) shows genuine pressure, not speculatively.
- **Public traffic must not starve official scoring or result workflows.** This is the single most important scaling rule in the platform: a traffic spike on the public portal (e.g., during a medal announcement) must be isolated (via caching, read-model separation, and — if warranted — separate deployment units per [environment-and-configuration-model.md, Section 4](environment-and-configuration-model.md#4-runtime-deployment-units)) from the request-handling capacity Scoring (BC-15) and Access Validation (BC-20) need.
- **Queue worker pools isolate long-running jobs** — per [event-and-queue-architecture.md, Section 3](event-and-queue-architecture.md#3-laravel-horizon-architecture), `critical` never shares a worker pool with `imports`/`media`/`exports`.
- **Real-time broadcasting degrades gracefully** — per [realtime-architecture.md, Section 5](realtime-architecture.md#5-fallback-behavior).
- **Database connection limits are considered** — connection pooling and query efficiency are treated as first-class concerns, not an afterthought discovered under load.
- **File processing does not block request workers** — any non-trivial file operation (validation, scanning, resizing) is queued (`documents`/`media` categories), never performed synchronously within an HTTP request.
- **Large imports are chunked** — per [event-and-queue-architecture.md, Section 6](event-and-queue-architecture.md#6-chunking-and-bulk-work).
- **Cache reduces public read pressure** — per [caching-and-session-architecture.md](caching-and-session-architecture.md).

## 2. Performance Architecture

### Performance-Sensitive Workflows
Athlete lookup, accreditation validation, QR scanning, score entry, live result display, medal tally, public portal, tournament brackets, schedule boards, bulk registration import, report export, mobile synchronization.

### Principles
Pagination, lazy loading, indexed queries, read models, caching, batch operations, chunking, background processing, payload minimization, image optimization, real-time event aggregation, avoiding N+1 queries, query-budget awareness (a developer discipline: know roughly how many queries a given page/endpoint should require, and treat an unexplained increase as a regression), performance test readiness (the testing architecture in [testing-architecture.md](testing-architecture.md) anticipates non-functional/load testing without performing it now).

**No numerical performance targets are defined in this phase** beyond what [Phase 0.1 success-framework.md](../00-product/success-framework.md) already establishes as proposed KPIs requiring baseline/pilot data (e.g., "public portal response time" is a named KPI area there, with the actual target explicitly deferred to post-pilot validation, not invented here).

## 3. Availability and Resilience

- **Graceful degradation** — a non-critical dependency failure (Reverb, search, analytics) degrades that specific feature, never the whole platform.
- **Queue retry** — per [event-and-queue-architecture.md, Section 1](event-and-queue-architecture.md#1-queue-categories) category-specific retry policies.
- **Integration timeout** — every external call (once any integration is approved) has a bounded timeout; PMMS never waits indefinitely on an external dependency.
- **Circuit-breaker concept where justified** — for any future external integration prone to intermittent failure, evaluated per-integration rather than applied blanket.
- **Cached public data** — serves stale-but-available public content rather than an error page during a backend hiccup, with clear freshness indication (per [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)).
- **Read-only fallback** — where feasible, a degraded administrative experience (view-only) is preferable to total unavailability during a partial outage.
- **Offline mobile operation** — the primary resilience mechanism for field operations, per [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md).
- **Reverb fallback to polling** — per [realtime-architecture.md, Section 5](realtime-architecture.md#5-fallback-behavior).
- **Failed-job recovery** — per [event-and-queue-architecture.md, Section 1](event-and-queue-architecture.md#1-queue-categories)'s dead-letter/failed-job handling column.
- **File-upload resume or retry** — large uploads (per [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md)) support resumable/retryable transfer where feasible.
- **Database backup, object-storage backup, restore testing** — Section 4 below.
- **Idempotent replay** — every retryable operation (queue jobs, sync uploads, API calls) is idempotent, restated from [event-and-queue-architecture.md, Section 2](event-and-queue-architecture.md#2-job-rules) and [offline-sync-runtime-architecture.md, Section 3](offline-sync-runtime-architecture.md#3-runtime-rules).
- **Reconciliation jobs** — scheduled `maintenance`-category jobs that detect and flag (never silently auto-correct) drift between related records (e.g., a read-model projection that appears to have missed an update).
- **Manual operational fallback** — for the most severe outage scenarios, a paper/manual fallback procedure exists for the highest-priority field operations (per [Phase 0.1 CON-12](../00-product/assumptions-constraints-risks.md#2-constraints), "need for printable documents") — specific procedures are an operational-readiness deliverable, not designed here.

**High-integrity transactions fail safely rather than partially commit** — restated from [laravel-architecture.md, Section 4](laravel-architecture.md#4-transaction-boundaries): a certification, approval, or tally recalculation either fully succeeds within its transaction or fully rolls back; there is no ambiguous partial state for these operations under any failure scenario.

## 4. Backup and Recovery Considerations

### Conceptual Backup Coverage
MySQL (the authoritative relational store), MinIO (documents, media, generated reports), application configuration, encryption keys, audit records (backed up with the same or greater rigor as the data they audit, given their evidentiary role), generated reports, critical Redis data only where genuinely needed (Redis is not a system of record — Section, [caching-and-session-architecture.md](caching-and-session-architecture.md) — so most Redis content requires no backup at all; only genuinely irreplaceable Redis-held state, if any is ever introduced, would need it), mobile offline recovery considerations (a lost/corrupted device should not be a data-loss event for anything already synced), device credentials (revocation lists, per [device-and-service-identity-model.md](device-and-service-identity-model.md)), deployment artifacts.

### Requirements
- **Recovery Point Objective (RPO) placeholder** — not numerically defined in this phase; to be set based on DepEd institutional-record requirements and pilot-meet experience.
- **Recovery Time Objective (RTO) placeholder** — same treatment.
- **Backup verification** — a backup is periodically test-restored to confirm it is actually usable, not merely "a file exists."
- **Restore drills** — scheduled, not merely theoretical.
- **Offsite backup** — physically/logically separated from the primary environment, protecting against a single-site failure.
- **Access control** — backup access follows the same least-privilege discipline as the live data it contains (a MySQL backup containing eligibility/medical data is exactly as sensitive as the live table).
- **Retention** — governed by the same DepEd records-management policy dependency as [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements) and [domain-open-decisions.md, DD-23](domain-open-decisions.md#dd-23--document-retention-ownership) — not invented here.
- **Meet-specific archival** — a closed meet's full record set (per the `MeetArchived` domain event, [domain-events-catalog.md](domain-events-catalog.md)) is archived as a coherent unit, supporting the Phase 0.1 institutional-knowledge goal.
- **Legal and policy validation needs** — backup/retention practices for medical and minor-athlete data require Data Privacy and Legal Stakeholder sign-off (per [Phase 0.1 stakeholder-register.md](../00-product/stakeholder-register.md)) before being finalized.

**No numerical RPO/RTO or retention duration is invented in this document** — every placeholder above is explicitly marked as such.

## 5. Open Questions

- RPO/RTO targets (Section 4) — require DepEd/legal input.
- Circuit-breaker adoption threshold for future integrations — no integrations currently exist to apply it to.
- Whether/when public traffic warrants a physically separate deployment unit (Section 1) — evaluated against real pilot-meet traffic, not decided speculatively.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
