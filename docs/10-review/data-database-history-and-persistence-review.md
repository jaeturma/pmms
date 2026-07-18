# PMMS Data, Database, History, and Persistence Review

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [../02-data/phase-0.5-data-database-persistence-architecture.md](../02-data/phase-0.5-data-database-persistence-architecture.md), [architecture-completeness-assessment.md, Section 3](architecture-completeness-assessment.md#3-the-physical-database-schema-gap-critical-finding)

---

## 1. The Physical Schema Gap (Restated, Critical)

**This document's most important finding is a cross-reference, not a new one**: no physical database schema, migration, or Eloquent model exists anywhere in the 12-phase architecture — a gap self-acknowledged in Phase 0.7's own text and confirmed unresolved through Phase 0.12. Full detail and recommended resolution: [architecture-completeness-assessment.md, Section 3](architecture-completeness-assessment.md#3-the-physical-database-schema-gap-critical-finding), tracked as [GAP-01](architecture-gap-register.md). Every other finding in this document concerns the *logical* architecture that the eventual physical schema must implement.

## 2. Ownership

[../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md) restates Phase 0.2's data-ownership map at the persistence layer with no reassignment — confirmed non-contradictory.

## 3. Identifiers

Unsigned BIGINT internal keys paired with ULID-based public identifiers for exposed/sync-sensitive/offline-originated records is consistently applied, never contradicted, through Phase 0.9 (public-facing IDs), 0.11 (event/workflow correlation IDs), and 0.12 (tenant-context propagation). **Assessment: Strong.** [PD-02](../02-data/data-open-decisions.md) (ULID vs. UUIDv7 format) remains an open, non-blocking implementation detail.

## 4. Constraints

Composite uniqueness including tenant context (Phase 0.12) is a *logical* addition consistent with, not contradicting, Phase 0.5's naming/constraint standards — no physical constraint yet exists to validate this against (Section 1).

## 5. Temporal History and Versioning

The correction-supersedes-never-overwrites pattern (7 mechanisms: Cancellation, Revocation, Reversal, Supersession, Inactivation, Archival, Correction version) is the architecture's most consistently and repeatedly restated data rule — confirmed applied without weakening in Phase 0.6 (audit), 0.8 (backup/DR), 0.9 (UX), 0.11 (workflow versioning), and 0.12 (tenant deletion review). **Assessment: Strong — the single most mature rule in the data architecture.**

## 6. Audit

Audit events are absolutely append-only — the only domain with "Correction Authority: none," restated verbatim from Phase 0.5 through Phase 0.12's tenant-scoped audit-export architecture. **Assessment: Strong.**

## 7. Source of Truth

Zero conflicts found (see [architecture-consistency-and-contradiction-analysis.md, Section 5](architecture-consistency-and-contradiction-analysis.md#5-source-of-truth-conflicts)).

## 8. Concurrency

Optimistic locking (version columns) as the default concurrency control is consistently referenced through Phase 0.11 (automation-conflict handling) and Phase 0.12 (cache-stampede-protection exclusion of locks from authoritative validation) — no contradiction.

## 9. Imports/Exports

Bulk-processing chunking discipline (Phase 0.4 Section 6) is consistently applied through Phase 0.12's bulk-processing architecture — no contradiction. [PD-13](../02-data/data-open-decisions.md) (historical spreadsheet migratability) and [PD-14](../02-data/data-open-decisions.md) (CSV formula-injection mitigation) remain open, non-blocking implementation details.

## 10. Projections

Public projections and reporting read models are consistently treated as non-authoritative and rebuildable — restated absolutely through Phase 0.9, 0.11, and 0.12 without exception.

## 11. Object Metadata

MySQL-authoritative-metadata-over-MinIO discipline restated unchanged through Phase 0.12's tenant-aware object-key architecture. **Assessment: Strong.**

## 12. Retention

[PD-04](../02-data/data-open-decisions.md) (Retention Periods, 8 Categories) remains **blocking** — carried unresolved since Phase 0.5, restated as blocking in Phase 0.6 ([SD-23](../03-security/security-open-decisions.md)) and referenced but not resolved through Phase 0.12. This is the data architecture's second most consequential open decision after the physical-schema gap.

## 13. Backup

Backup-tier priority (Highest/High/Medium/Medium-Low/Low/None) established in Phase 0.5 is consolidated, not redefined, in Phase 0.12's [backup-replication-restore-and-point-in-time-recovery.md](../09-enterprise/backup-replication-restore-and-point-in-time-recovery.md) — confirmed non-contradictory.

## 14. Schema Risks Without Migrations (Qualitative)

Without creating any migration, this review notes the following schema-design risks the eventual physical schema must specifically address, based on the logical architecture's own stated requirements: (a) every tenant-owned table requires a consistent tenant-key column and composite-uniqueness convention (Phase 0.12); (b) every high-integrity table requires a version-chain design supporting the correction-supersedes pattern without soft-delete columns (Phase 0.5); (c) the four very-high-volume tables (`ScoreRecord`, `AccessScan`, `AuditEvent`, `Notification`) require index and future-partitioning-readiness design from their first migration, per [PD-22](../02-data/data-open-decisions.md).

## 15. Open Questions

PD-04 (retention, blocking), the physical-schema gap (GAP-01), and PD-21/WD-08 (outbox pattern) remain the highest-priority data-architecture decisions — see [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md).
