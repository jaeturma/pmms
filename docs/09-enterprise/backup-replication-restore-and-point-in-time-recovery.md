# PMMS Backup, Replication, Restore, and Point-in-Time Recovery

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md) · [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Sections 1–3](../05-devops/backup-restore-disaster-recovery-and-continuity.md#1-backup-architecture)

**This document consolidates and extends the existing Phase 0.5 data-layer and Phase 0.8 operational-layer backup architecture with enterprise/multi-tenant scaling considerations — it does not redefine either.**

---

## 1. Backup Tiers (Restated, Not Redefined)

Restated unchanged from [../02-data/backup-restore-and-data-recovery.md, Section 1](../02-data/backup-restore-and-data-recovery.md#1-backup-coverage-by-data-category):

| Tier | Data Categories |
|---|---|
| Highest | Official Results, Medal Tally, Protest decisions, Audit events, Eligibility decisions |
| High | Scoring (raw records), Accreditation credentials, Medical records, Financial records, Participant identity, Document metadata + MinIO objects |
| Medium | Registration, entries, assignments, schedules |
| Medium–Low | Logistics records (billeting, food, transport) |
| Low | Notifications |
| None | Cache/session (Redis) — never backed up as authoritative |

## 2. Backup Frequency (Placeholders Only)

**No specific backup frequency is invented** — restated absolutely per working rule 17, unchanged from [PD-24](../02-data/data-open-decisions.md#pd-24--backup-frequency-mechanism-per-tier) (Backup Frequency Mechanism per Tier, unresolved). The tiered priority itself (Section 1) already implies the Highest tier plausibly warrants materially more frequent backup than the Medium tier — restated unchanged, no numeric value set here either.

## 3. Point-in-Time Recovery Readiness

Evaluated: binary-log-based PITR for MySQL (a candidate mechanism, restated as "not committed to" from [../05-devops/mysql-redis-minio-and-stateful-service-operations.md, Section 1](../05-devops/mysql-redis-minio-and-stateful-service-operations.md#1-mysql-operations-architecture)). PITR granularity (how close to a specific moment recovery can target) is a capacity/cost tradeoff evaluated only once backup-tier evidence exists.

## 4. Replication Readiness (Distinct From Read Replicas)

Replication for backup/DR purposes (a standby copy for recoverability) is distinct from read-replica scaling (per [mysql-performance-replication-partitioning-and-scaling-readiness.md, Section 3](mysql-performance-replication-partitioning-and-scaling-readiness.md#3-read-replica-readiness), which serves read-scaling, not DR). Both may eventually use the same underlying MySQL replication mechanism, but serve different architectural purposes and are evaluated separately — a read replica is not automatically a valid DR target unless it meets the same integrity/currency requirements as a dedicated backup/DR copy.

## 5. Offsite, Immutable, and Air-Gapped Backup Readiness

| Control | Direction |
|---|---|
| Offsite backup | Restated unchanged as an existing requirement from [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 1](../05-devops/backup-restore-disaster-recovery-and-continuity.md#1-backup-architecture) |
| Immutable backup readiness | A candidate control (write-once, time-locked backup storage) evaluated for ransomware/tamper resilience — not committed to a specific mechanism |
| Air-gapped backup readiness | A candidate control for the highest-assurance backup tier — evaluated only if a specific regulatory or contractual requirement (e.g., a Stage 5 enterprise tenant) demonstrates the need |
| Backup encryption | Restated unchanged — backups are encrypted at rest, consistent with Phase 0.6's encryption discipline |

## 6. Restore Validation

Restated unchanged from [../05-devops/backup-restore-disaster-recovery-and-continuity.md, Section 2](../05-devops/backup-restore-disaster-recovery-and-continuity.md#2-restore-architecture)'s 19-step conceptual restore sequence — **backups must be restorable**, restated absolutely per working rule 57; an unverified backup is not a backup, only a claim. Restore-drill discipline (periodic, evidenced test restores) remains the confirmed verification mechanism.

## 7. Recovery Point and Recovery Time Objectives

**RPO and RTO remain explicitly placeholders** — restated absolutely, this is the single longest-carried open decision across this entire architecture effort (chained RD-18 → PD-23 → SD-24 → DV-17, now continued here). No numeric value is invented in this document either. What this document adds: the tiered backup priority (Section 1) implies a tiered RPO/RTO expectation will eventually follow the same tiering, once real numeric values are set.

## 8. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-33, continuing the RPO/RTO chain: [RD-18](../01-architecture/runtime-open-decisions.md) → [PD-23](../02-data/data-open-decisions.md#pd-23--rporto-numeric-targets) → [SD-24](../03-security/security-open-decisions.md) → [DV-17](../05-devops/devops-open-decisions.md#dv-17--numeric-rporto-targets) → ED-33.
