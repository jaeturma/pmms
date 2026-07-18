# PMMS MySQL Performance, Replication, Partitioning, and Scaling Readiness

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../02-data/indexing-performance-and-capacity.md](../02-data/indexing-performance-and-capacity.md) · [../05-devops/mysql-redis-minio-and-stateful-service-operations.md, Section 1](../05-devops/mysql-redis-minio-and-stateful-service-operations.md#1-mysql-operations-architecture)

**No replication, clustering, partitioning, or sharding configuration is created here.**

---

## 1. MySQL Remains Sole Authoritative Relational Store

Restated absolutely per working rule 29 — nothing in this document changes MySQL's status as the sole authoritative store for business-critical data, established since Phase 0.5.

## 2. Query and Index Governance

Restated unchanged from [../02-data/indexing-performance-and-capacity.md, Section 1](../02-data/indexing-performance-and-capacity.md#1-indexing-strategy): index discipline is applied per query pattern, not speculatively. This document adds: connection management (connection pooling/limits appropriate to the deployment topology in use), transaction-duration review (a long-held transaction is a scaling risk, not just a correctness risk), and lock analysis (contention on high-write tables, e.g., `ScoreRecord`, `AccessScan`).

## 3. Read Replica Readiness

Evaluated for: public projections · internal reporting · analytics extraction · selected stale-tolerant reads.

**Read replicas must not be used for actions requiring immediate authoritative consistency unless staleness is explicitly acceptable** — restated absolutely per working rule 51. Explicitly prohibited replica use: authorization decisions requiring current state · score certification · credential-revocation checks · high-integrity state transitions · read-after-write workflows without explicit consistency handling.

This directly extends [../01-architecture/reporting-search-and-read-model-runtime.md, Section 6](../01-architecture/reporting-search-and-read-model-runtime.md#6-isolation-from-transactional-write-load), which deferred "the specific runtime mechanism for this isolation (read replica, dedicated cache layer, separate query connection pool)" to this document — **no read-replica decision is made here either**; the isolation mechanism remains an open decision pending capacity evidence, consistent with [MySQL Failover readiness](../05-devops/mysql-redis-minio-and-stateful-service-operations.md#1-mysql-operations-architecture) already being "a candidate future capability... not committed to."

## 4. Partitioning Readiness (Restated, Not Redefined)

Restated unchanged from [../02-data/indexing-performance-and-capacity.md, Section 3](../02-data/indexing-performance-and-capacity.md#3-partitioning-and-archival-readiness): candidate tables are `ScoreRecord`, `AccessScan`, `AuditEvent`, `Notification`, plus the workflow-layer additions from Phase 0.11 (synchronization logs, device telemetry, security events, public access logs). **No physical partitioning scheme is selected in this phase.** Partitioning triggers remain: table size, retention-deletion cost, backup time, query degradation, maintenance time — the specific numeric threshold is tracked as [PD-22](../02-data/data-open-decisions.md#pd-22--partitioning-trigger-threshold), unresolved.

## 5. Sharding Readiness (New Evaluation)

**No sharding concept exists anywhere in PMMS's architecture prior to this document.** Sharding is evaluated only if: database capacity reaches validated limits · tenant extraction is mature (per [tenant-data-ownership-and-isolation-architecture.md, Section 6](tenant-data-ownership-and-isolation-architecture.md#6-tenant-migration-sharding-and-extraction-readiness)) · tenant ownership is complete · operational capability exists · cross-shard processes are understood.

Possible shard keys (documented, not implemented): tenant · region · meet. Major tradeoffs: cross-shard queries (reporting, medal tally spanning multiple meets) become materially more complex; referential integrity across shards is application-enforced, not database-enforced; operational complexity (backup, migration, monitoring) multiplies per shard. **Sharding is a Stage 6 (Large-Scale or National Platform) candidate only** — restated from [enterprise-readiness-vision-principles-and-maturity-model.md, Section 3](enterprise-readiness-vision-principles-and-maturity-model.md#3-enterprise-maturity-model).

## 6. Upgrade Readiness

MySQL version-upgrade readiness follows the same phased, backward-compatible discipline as any schema change, per [../05-devops/database-migration-and-release-safety.md](../05-devops/database-migration-and-release-safety.md) — no specific upgrade cadence is committed to here.

## 7. Backup Performance

Backup duration is a named capacity-planning input (Section, [workload-capacity-and-scale-assumptions.md, Section 2](workload-capacity-and-scale-assumptions.md#2-scale-evidence-requirements)) — restated from [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md), which this document does not redefine; full backup/PITR detail lives in [backup-replication-restore-and-point-in-time-recovery.md](backup-replication-restore-and-point-in-time-recovery.md).

## 8. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-18 (read-replica adoption trigger, extending the still-unresolved [Section 6 cross-reference](../01-architecture/reporting-search-and-read-model-runtime.md#6-isolation-from-transactional-write-load)) and the existing [PD-22](../02-data/data-open-decisions.md#pd-22--partitioning-trigger-threshold) cross-reference.
