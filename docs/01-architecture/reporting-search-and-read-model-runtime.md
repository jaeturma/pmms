# PMMS Reporting, Search, and Read-Model Runtime

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md) (Phase 0.2 principles) · [caching-and-session-architecture.md](caching-and-session-architecture.md) · [event-and-queue-architecture.md](event-and-queue-architecture.md)

This document gives runtime shape to the Phase 0.2 reporting/read-model principles. **No database view, search index, or report-generation code is created here.**

---

## 1. Read-Model Categories at Runtime

| Category | Update Timing | Mechanism |
|---|---|---|
| Context-owned operational read models | Synchronous or near-real-time | Updated within the owning context's own transaction or immediately-following synchronous step |
| Cross-context executive projections | Asynchronous | Updated via queued `projections`-category jobs reacting to domain events (per [event-and-queue-architecture.md](event-and-queue-architecture.md)) |
| Public projections (BC-29) | Asynchronous, near-real-time target | Same `projections` queue category, prioritized given public-facing visibility |
| Historical snapshots | Point-in-time, immutable once created | Created at defined lifecycle moments (result certification, tally publication, meet closure) — never recomputed retroactively |
| Export datasets | On-demand | Generated via `exports`-category queued jobs |
| Search indexes | Asynchronous | Updated via `projections`/`analytics`-category jobs (Section 3) |
| Analytics datasets | Scheduled | Computed via `analytics`-category scheduled jobs |

## 2. Runtime Update Rules

- Read models may update **synchronously** for immediate operational needs (e.g., a Tournament Manager's own dashboard reflecting a draw they just generated, within the same request).
- Read models update **asynchronously** for cross-domain dashboards (e.g., an executive meet-readiness dashboard aggregating signals from a dozen committees).
- Read models update **on schedule** for historical analytics (no need for real-time freshness on multi-meet trend analysis).
- Read models update **on demand** for exports (generated when requested, not continuously maintained).
- **Read-model failures must not corrupt source transactions** — a failed projection-rebuild job leaves the read model stale (with a visible freshness timestamp, per [reporting-and-read-model-boundaries.md, "Data Freshness"](reporting-and-read-model-boundaries.md#data-freshness)) but never rolls back or corrupts the authoritative write that triggered it.
- Every read model exposes freshness/version/source metadata (inherited directly from [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md), Phase 0.2), and **correction propagation** follows the same rule: a source correction (e.g., a superseded Official Result) triggers a read-model rebuild, never a manual patch to the projection itself.
- **Rebuild strategy**: every read model must be safely, idempotently rebuildable from its source data — this is both a resilience requirement (Section, [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md)) and a correctness requirement (a read model is a derived artifact, never an independent source of truth that could diverge unrecoverably).

## 3. Search Architecture

### Candidate Search Capabilities
Participant search, athlete search, organization search, meet search, schedule search, result search, public content search, document metadata search, audit search.

### Staged Runtime Approach

1. **MySQL-backed search initially** — full-text/indexed-column search against the authoritative or read-model tables, sufficient for the initial scale and query patterns.
2. **Optimized indexes and dedicated read models** — as specific search use cases (e.g., participant lookup during registration) show measured performance needs, a purpose-built read model or additional indexing is introduced.
3. **External search engine only when justified** — a dedicated search engine (e.g., Meilisearch, Elasticsearch) is introduced only once a specific, measured need (query complexity or volume MySQL cannot serve acceptably) is demonstrated — **not adopted preemptively**, consistent with avoiding premature infrastructure complexity.

### Rules
- Search results respect authorization and data classification — a search index is not a bypass of the normal query-authorization path; a Restricted document's metadata does not appear in a search result for a user without access to it.
- Search indexing (once introduced) is asynchronous, via the `projections`/`analytics` queue categories.

## 4. Generated Documents

Generated reports and official documents (certificates, result sheets) include source and version references (per [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md)) — the generation process reads from the authoritative source (or an already-versioned snapshot) at generation time and stamps the output with what it was generated from, so a printed certificate is traceable to the exact Official Result version it reflects.

## 5. Privacy Filtering at Runtime

Consistent with [reporting-and-read-model-boundaries.md, "Privacy Filtering"](reporting-and-read-model-boundaries.md#privacy-filtering), privacy filtering happens **at the point a read model or search index is built**, in the owning context's Infrastructure layer — never deferred to the consuming report/query to filter after the fact. A cross-context executive dashboard consuming Medical Operations' incident-volume figures receives already-de-identified aggregate data; it never receives row-level records to filter itself.

## 6. Isolation from Transactional Write Load

Per [reporting-and-read-model-boundaries.md, "High-Volume Public Delivery and Analytics Isolation"](reporting-and-read-model-boundaries.md#high-volume-public-delivery-and-analytics-isolation), the specific runtime mechanism for this isolation (read replica, dedicated cache layer, separate query connection pool) is evaluated in [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md) — this document establishes that isolation is required, not the specific mechanism.

## 7. Open Questions

- Timing of introducing a dedicated search engine (Section 3, Stage 3) — deferred per [domain-open-decisions.md, DD-25](domain-open-decisions.md#dd-25--data-warehouse-timing)'s analogous reasoning for a data warehouse.
- Whether historical/cross-meet analytics (a named future-scope capability per [Phase 0.1 product-scope.md](../00-product/product-scope.md#6-future-scope-capabilities)) requires infrastructure beyond the MySQL-backed approach before it becomes an active priority.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
