# PMMS MinIO Object Storage, Media, and Delivery Scaling

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md) · [../05-devops/mysql-redis-minio-and-stateful-service-operations.md, Section 3](../05-devops/mysql-redis-minio-and-stateful-service-operations.md#3-minio-operations-architecture)

**No bucket policy, replication configuration, or storage provisioning is created here.**

---

## 1. MinIO Remains Authoritative Only for Object Content

Restated absolutely per working rule 31 — MySQL controls ownership and lifecycle metadata; MinIO stores object bytes only, unchanged since Phase 0.5.

## 2. Capacity and Growth

Object count, multipart-upload usage (large files, e.g., media/document uploads), and lifecycle (transition to lower-cost storage or deletion per retention policy) are named capacity-planning dimensions, feeding [workload-capacity-and-scale-assumptions.md, Section 2](workload-capacity-and-scale-assumptions.md#2-scale-evidence-requirements). No specific growth rate is invented.

## 3. Tenant-Aware Object Keys and Metadata

Object keys follow a structured, tenant-aware convention (conceptually: `{environment}/{tenant}/{context}/{record}/{version}` or equivalent) — never a flat, tenant-ambiguous key space. Object metadata (owner, classification, tenant context) remains authoritative in MySQL per [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md), with the MinIO key itself never treated as the sole source of tenant attribution.

## 4. Bucket Strategy

Evaluated: a single shared bucket with tenant-prefixed keys (consistent with the logical-isolation direction in [tenant-data-ownership-and-isolation-architecture.md, Section 2](tenant-data-ownership-and-isolation-architecture.md#2-recommended-initial-direction)) versus bucket-per-tenant (a Stage 5 candidate for large/regulated tenants, mirroring the database-per-tenant option). No bucket strategy is committed to in this phase.

## 5. Versioning, Replication, and Backup Readiness

Object versioning readiness, cross-region/cross-instance replication readiness, and erasure-coding readiness (if later applicable) are all restated unchanged as "candidate, not committed" from [../05-devops/mysql-redis-minio-and-stateful-service-operations.md, Section 3](../05-devops/mysql-redis-minio-and-stateful-service-operations.md#3-minio-operations-architecture). Backup and restore for MinIO objects follow the same lock-step principle as MySQL backup, restated from [../02-data/backup-restore-and-data-recovery.md, Section 5](../02-data/backup-restore-and-data-recovery.md#5-disaster-recovery-data-requirements).

## 6. Metadata Reconciliation

Object metadata (MySQL) and object existence (MinIO) require periodic reconciliation — restated from [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md)'s existing "reconciliation between metadata and object storage is a required, not optional, operational process," extended here with a scaling note: reconciliation cost grows with object count and must itself be capacity-planned, not assumed instantaneous.

## 7. Signed-Object Delivery and Public-Media Delivery

Restricted and Confidential-tier objects are served only through signed, time-limited URLs generated after authorization — never a direct, permanent MinIO URL. Approved public media (per [../06-design/privacy-security-and-sensitive-data-experience.md](../06-design/privacy-security-and-sensitive-data-experience.md)) may be delivered through the CDN/edge-delivery path defined in [cache-cdn-static-asset-and-edge-delivery-architecture.md, Section 2](cache-cdn-static-asset-and-edge-delivery-architecture.md#2-candidate-content). **CDN and public caches must never receive restricted data** — restated absolutely per working rule 50.

## 8. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-21 (bucket-per-tenant adoption trigger) and the existing MinIO replication/versioning open items from Phase 0.8.
