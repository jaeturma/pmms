# PMMS Indexing, Performance, and Capacity

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [conceptual-schema-catalog.md](conceptual-schema-catalog.md) · [../01-architecture/resilience-performance-and-scaling.md](../01-architecture/resilience-performance-and-scaling.md) · [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)

This document defines indexing principles, capacity/growth categories, and database observability. **No physical index or partition is created here.**

---

## 1. Indexing Strategy

### Principles

- **Index foreign keys** — every same-context foreign-key column is indexed by default (a Laravel/MySQL near-automatic practice, restated as a firm expectation).
- **Index common scope filters** — meet ownership, organization ownership, and other scope-defining columns (per [../01-architecture/scope-model.md](../01-architecture/scope-model.md)) are indexed, since nearly every query in a multi-meet platform filters by at least one of these.
- **Index active-assignment lookups** — "does this user hold an active assignment matching this scope" is one of the most frequent query shapes in the platform (every authorization decision, per [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md), potentially triggers one), warranting a deliberately optimized composite index.
- **Index participant identifiers** — both internal and public ID columns.
- **Index public identifiers** — every externally-exposed `public_id` column, since external lookups (API requests, QR scans) resolve by public ID, never internal sequential ID.
- **Index status plus scope where operationally justified** — e.g., "all `EligibilityCase` rows with `case_status = 'under_review'` for Meet X" is a realistic operational dashboard query, warranting a composite index once real usage confirms the pattern.
- **Index schedule time ranges** — `Schedule`/`Venue` slot queries filter by time range; a range-friendly index (not just an equality index) is needed.
- **Index tournament relationships** — `Match`/`Heat` lookups by `Tournament`.
- **Index result publication lookups** — "all published results for Meet X" is a high-frequency public-facing query.
- **Index access-scan time and credential references** — `AccessScan` is one of the highest-volume tables (Section 2); its time-range and credential-reference columns need efficient indexing given the volume.
- **Index synchronization status** — for efficiently finding pending/conflicted sync items.
- **Index audit actor, action, target, and time** — `AuditEvent` queries typically filter by one or more of these four dimensions.

### Discipline
- **Avoid excessive indexes** — every index has a write-amplification cost; indexes are added based on actual or realistically anticipated query patterns, not speculatively for every column.
- **Validate indexes with query plans** — an implementation-phase (Phase 0.6+) practice: `EXPLAIN` real queries before finalizing an index design, not guessing.
- **Review write amplification** — particularly relevant for the highest-write-volume tables (`ScoreRecord`, `AccessScan`, `AuditEvent`); each additional index on these tables has a real, measurable cost at scale.
- **Use composite indexes based on actual query patterns** — a composite index's column order matters and should reflect how queries actually filter/sort, not be guessed abstractly.
- **Avoid indexing highly sensitive plaintext unnecessarily** — a Highly Restricted field that is never queried by value (only ever fetched by owning-record ID) does not need its own index, reducing the surface area where sensitive plaintext might appear in index structures or query-plan diagnostics.
- **Consider full-text or search indexes only when justified** — per [public-reporting-and-projection-data.md, Section 3](public-reporting-and-projection-data.md#3-search-indexes), staged and evidence-driven, not default.

## 2. Capacity and Growth Categories

| Volume Category | Description | Representative Tables |
|---|---|---|
| **Low volume** | Hundreds to low thousands of rows total, growing slowly | `Organization`, `Meet`, `SportDefinition`, `EventDefinition`, `Venue`, `Committee` |
| **Moderate volume** | Thousands to tens of thousands, growing per meet cycle | `Delegation`, `OfficialAssignment`, `Schedule`, `BudgetAllocation`, `SecurityIncident` |
| **High volume** | Tens of thousands to hundreds of thousands per meet cycle | `Participant`, `AthleteRegistration`, `EligibilityCase`, `CompetitionEntry`, `AccreditationCredential`, `DocumentRecord` |
| **Very high volume** | Potentially millions per meet cycle, dominated by per-event/per-scan granularity | `ScoreRecord` (every attempt/heat/component), `AccessScan` (every gate/meal/venue scan), `AuditEvent` (every consequential action), `Notification` (every triggering event) |
| **Burst-heavy** | Low steady-state but sharp spikes during specific windows | Public read traffic during result/medal announcements; `AccessScan` during gate opening/meal periods |
| **Long-term historical** | Grows every meet cycle, rarely shrinks, dominant driver of eventual partitioning need | `ScoreRecord`, `AccessScan`, `AuditEvent`, `OfficialResult` history, `MedalAward`/tally snapshots |

### Likely High-Volume Candidates (Confirmed from the Aggregate Catalog)

`AccessScan` (BC-20), `ScoreRecord` (BC-15), `AuditEvent` (BC-32), `Notification` (BC-31), `Match`/`Heat` (BC-12) — all previously flagged "Very High" in [conceptual-schema-catalog.md](conceptual-schema-catalog.md).

## 3. Partitioning and Archival Readiness

- No physical partitioning scheme is selected in this phase.
- The very-high-volume and long-term-historical tables above are the primary future candidates for time-based partitioning (e.g., by meet or by year) once real volume data justifies the operational complexity of partitioning.
- Partitioning readiness is supported by the identifier and versioning strategies already established (every row unambiguously belongs to one meet, one version, one time range), so introducing partitioning later does not require a data-model redesign — only a physical storage-layer change.
- Archival (per [retention-archival-and-disposal.md](retention-archival-and-disposal.md)) is the primary near-term mechanism for managing long-term-historical growth; partitioning is a secondary, later-stage performance optimization, not an immediate requirement.

## 4. Database Observability

Extending [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) (Phase 0.4) with database-specific signals:

- Query latency and slow-query identification (per table/query pattern).
- Connection pool utilization.
- Index usage statistics (identifying unused indexes — a write-amplification cost with no compensating read benefit — and missing indexes suggested by slow queries).
- Table growth rate, particularly for the very-high-volume tables in Section 2.
- Replication lag, if/when a read replica is introduced (not currently planned).
- Lock wait time and deadlock frequency, informing the concurrency-control decisions in [transaction-concurrency-and-locking.md](transaction-concurrency-and-locking.md).
- Backup job success/failure and duration trend, feeding [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md).

**No monitoring vendor or specific tooling is selected** — consistent with [../01-architecture/runtime-open-decisions.md, RD-14](../01-architecture/runtime-open-decisions.md#rd-14--monitoringobservability-stack-selection).

## 5. Open Questions

- Specific composite-index designs — deferred to Phase 0.6, informed by real query patterns.
- Partitioning trigger threshold (row count / table size) — deferred to post-pilot operational data.

Tracked in [data-open-decisions.md](data-open-decisions.md).
