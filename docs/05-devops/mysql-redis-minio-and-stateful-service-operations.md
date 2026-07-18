# PMMS MySQL, Redis, MinIO, and Stateful-Service Operations

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/infrastructure-runtime-and-network-security.md](../03-security/infrastructure-runtime-and-network-security.md) · [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md) · [backup-restore-disaster-recovery-and-continuity.md](backup-restore-disaster-recovery-and-continuity.md)

This document defines operational architecture for PMMS's three stateful services. **No database, cache, or object-storage instance is provisioned or connected to by this document**, per working rule 18.

---

## 1. MySQL Operations Architecture

| Area | Direction |
|---|---|
| Authoritative role | Restated absolutely per working rule 32 — MySQL remains the sole authoritative relational store |
| Application account | Least-privilege, per [../03-security/infrastructure-runtime-and-network-security.md, Section 2](../03-security/infrastructure-runtime-and-network-security.md#2-mysql-security) |
| Migration account | Distinct, more privileged, used only during deployment |
| Reporting account | Distinct, read-only, for BC-33 Reporting and Analytics |
| Backup account | Distinct, scoped only to backup-taking operations |
| Network isolation | Restated from [network-reverse-proxy-tls-and-domain-architecture.md, Section 3](network-reverse-proxy-tls-and-domain-architecture.md#3-network-zones) — MySQL lives in the Data zone, unreachable from Public Edge |
| TLS readiness | Database connections use TLS where the deployment topology requires it |
| Connection management | Connection pooling/limits sized to the deployment topology's actual concurrency needs, tuned from Staging/pilot evidence, not guessed |
| Slow-query monitoring | Per [observability-logging-metrics-tracing-and-alerting.md, Section 4](observability-logging-metrics-tracing-and-alerting.md#4-metrics-architecture) |
| Lock monitoring | Same |
| Disk monitoring | Same, informing [service-level-capacity-and-performance-management.md, Section 3](service-level-capacity-and-performance-management.md#3-capacity-planning) |
| Index maintenance | Per [../02-data/indexing-performance-and-capacity.md](../02-data/indexing-performance-and-capacity.md), operationalized once physical schema exists |
| Backup | Per [backup-restore-disaster-recovery-and-continuity.md](backup-restore-disaster-recovery-and-continuity.md) |
| Point-in-time recovery readiness | A candidate control (binary-log-based PITR), per [../02-data/backup-restore-and-data-recovery.md, Section 2](../02-data/backup-restore-and-data-recovery.md#2-backup-requirements) — not committed to a specific mechanism |
| Restore testing | Restated absolutely — a backup nobody has restored from is not verified |
| Version upgrades | Follow the patch-management process, per [patch-vulnerability-and-dependency-operations.md](patch-vulnerability-and-dependency-operations.md) |
| Failover readiness | A candidate future capability (read replica, automated failover) — not committed to; **no high-availability claim made without implementation and evidence**, per working rule 24 |
| Capacity reviews | Per [service-level-capacity-and-performance-management.md, Section 3](service-level-capacity-and-performance-management.md#3-capacity-planning) |

## 2. Redis Operations Architecture

| Area | Direction |
|---|---|
| Queue transport | One of Redis's approved uses, per [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md) |
| Cache | Approved use — never authoritative |
| Locks | Distributed-lock coordination, per [../02-data/transaction-concurrency-and-locking.md, Section 2](../02-data/transaction-concurrency-and-locking.md#2-concurrency-control) |
| Rate limits | Approved use |
| Sessions if approved | A candidate driver alternative to the current database session driver — not adopted by default |
| Reverb runtime support | Redis may back Reverb's scaling/pub-sub coordination if Reverb is horizontally scaled |
| Memory limits | Configured with an eviction policy appropriate to Redis's transient role (Section, below) |
| Eviction policy | An eviction-under-memory-pressure policy is acceptable and expected — restated: nothing authoritative is lost when Redis evicts |
| Persistence expectations | Redis persistence (RDB/AOF) is a performance/recovery-speed optimization, never a substitute for MySQL's authoritative role |
| Monitoring | Per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md) |
| Credential rotation | Per [configuration-feature-flag-and-secret-management.md, Section 3](configuration-feature-flag-and-secret-management.md#3-secret-management) |
| Network isolation | Restated from [network-reverse-proxy-tls-and-domain-architecture.md, Section 3](network-reverse-proxy-tls-and-domain-architecture.md#3-network-zones) |
| Failure behavior | The application degrades gracefully — cache misses fall through to MySQL, queued work delays rather than corrupts |
| Maintenance | Redis restarts/upgrades are low-risk operational events given its transient nature |
| Upgrade process | Follows the patch-management process |
| Recovery expectation | A Redis instance can be recreated empty with no authoritative-data loss — restated absolutely per working rule 33 |

**Redis loss must not cause authoritative-data loss** — restated absolutely per the phase's own working instruction; every operational decision above exists specifically to prove this property in practice, not merely in architecture documentation.

## 3. MinIO Operations Architecture

| Area | Direction |
|---|---|
| Buckets or storage-policy separation | Per [../03-security/infrastructure-runtime-and-network-security.md, Section 3](../03-security/infrastructure-runtime-and-network-security.md#3-minio-and-object-storage-security) — public/restricted separation, environment separation |
| Access credentials | Least-privilege, scoped, rotatable |
| Capacity | Monitored and reviewed per [service-level-capacity-and-performance-management.md, Section 3](service-level-capacity-and-performance-management.md#3-capacity-planning) |
| Object versioning readiness | A candidate control supporting the correction/supersession model, per [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md) — not committed to a specific configuration |
| Lifecycle readiness | Retention/expiry automation, once retention periods are finalized (currently placeholders per Phase 0.5/0.6) |
| Encryption | Server-side encryption at rest, per [../03-security/cryptography-key-and-secret-management.md, Section 1](../03-security/cryptography-key-and-secret-management.md#1-cryptographic-architecture) |
| Backup | Per [backup-restore-disaster-recovery-and-continuity.md](backup-restore-disaster-recovery-and-continuity.md), in lock-step with MySQL metadata |
| Replication evaluation | A candidate future control (cross-region or cross-instance object replication) for disaster-recovery readiness — not committed to |
| Health checks | Per [observability-logging-metrics-tracing-and-alerting.md, Section 1](observability-logging-metrics-tracing-and-alerting.md#1-health-checks) |
| Object reconciliation | Per [../02-data/object-metadata-and-file-lifecycle.md, Section 4](../02-data/object-metadata-and-file-lifecycle.md#4-reconciliation-process-conceptual), operationalized as a scheduled task |
| Orphan detection | Same reconciliation process |
| Missing-object detection | Same reconciliation process |
| Upgrade process | Follows the patch-management process |
| Credential rotation | Per [configuration-feature-flag-and-secret-management.md, Section 3](configuration-feature-flag-and-secret-management.md#3-secret-management) |
| Public and restricted policies | Restated absolutely — no object above Public classification is ever bucket-policy-public |

## 4. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably whether MySQL/Redis/MinIO are self-hosted or managed-cloud services (entangled with topology/cloud-provider decisions) and object-versioning/lifecycle-automation adoption timing.
