# PMMS Infrastructure, Runtime, and Network Security

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/phase-0.4-application-integration-runtime-architecture.md](../01-architecture/phase-0.4-application-integration-runtime-architecture.md) · [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md) · [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md)

This document defines security requirements for Redis, MySQL, MinIO, network/runtime boundaries, and service accounts. **No Docker, Nginx, firewall, TLS certificate, or CI/CD configuration is created here.**

---

## 1. Redis Security

| Control | Direction |
|---|---|
| Private-network access | Redis is never reachable from outside the application's own network boundary — restated from [../01-architecture/runtime-security-architecture.md, Section 3](../01-architecture/runtime-security-architecture.md#3-runtime-security-controls) |
| Authentication | A Redis password/ACL is required in every environment beyond Local (the current `.env.example`'s `REDIS_PASSWORD=null` is a Local-only default, never carried into Staging/Production) |
| TLS readiness | Redis connections use TLS where the deployment topology crosses a network boundary that is not already physically/logically isolated |
| Environment separation | Development/Staging/Production Redis instances are fully separate — no shared instance across environments |
| Command restrictions where appropriate | Dangerous commands (e.g., `FLUSHALL`, `CONFIG`) are restricted from the application's own credential where the Redis deployment supports command ACLs |
| No public exposure | Restated as an absolute rule |
| No authoritative records | Restated from [../02-data/logical-data-architecture.md, Section 2](../02-data/logical-data-architecture.md#2-source-of-truth-model) — Redis never holds anything whose loss would be a data-integrity event |
| Sensitive-cache restrictions | Highly Restricted-tier data (medical, auth secrets) is never cached in Redis, even transiently, without a specific justified exception |
| Key naming | A consistent, namespaced key convention (queue/cache/session/lock prefixes) reduces cross-purpose key collisions |
| TTL requirements | Every Redis key that is not a durable queue-backing structure carries a TTL — nothing accumulates indefinitely |
| Queue-data minimization | Queue payloads carry identifiers only, never large sensitive objects, per [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules) |
| Session protection | Session data in Redis (if the session driver is later changed to Redis) follows the same protection as the database session driver |
| Backup assumptions | Redis is explicitly **not backed up as authoritative**, per [../02-data/backup-restore-and-data-recovery.md, Section 1](../02-data/backup-restore-and-data-recovery.md#1-backup-coverage-by-data-category) |
| Eviction behavior | Cache eviction under memory pressure is expected and safe by design — nothing authoritative is lost |
| Failure behavior | The application degrades gracefully (falls back to database queries, queue jobs retry) if Redis is unavailable — Redis unavailability is never a data-loss event |
| Monitoring | Redis health/latency/memory is a database-observability signal, per [../02-data/indexing-performance-and-capacity.md, Section 4](../02-data/indexing-performance-and-capacity.md#4-database-observability) |
| Rotation of credentials | Redis credentials are rotatable without extended downtime |

## 2. MySQL Security

| Control | Direction |
|---|---|
| Separate application account | The application connects with its own least-privilege database credential, never a superuser/root account |
| Least privilege | The application account's grants are scoped to the tables/operations it genuinely needs (no `DROP`/`ALTER` grants for ordinary runtime operation) |
| No root use by application | Restated as an absolute rule |
| Environment separation | Development/Staging/Production databases are fully separate, with no shared credentials |
| Network restriction | MySQL is never reachable from outside the application's own network boundary, mirroring Redis |
| TLS readiness | Database connections use TLS where the deployment topology requires it |
| Strong credentials | Database passwords meet a minimum strength bar and are never reused across environments |
| Rotation | Database credentials are rotatable |
| Backup encryption | Per [cryptography-key-and-secret-management.md, Section 1](cryptography-key-and-secret-management.md#1-cryptographic-architecture) |
| Query logging restrictions | Query logs, if enabled for debugging, exclude or redact Restricted/Highly Restricted column values |
| Sensitive-column protection | Field-level encryption candidates (per [../02-data/audit-and-security-data-architecture.md, Section 3](../02-data/audit-and-security-data-architecture.md#3-encryption-and-sensitive-data)) apply at the column level for the highest-sensitivity fields, pending [../02-data/data-open-decisions.md, PD-06](../02-data/data-open-decisions.md#pd-06--field-level-encryption-candidate-list) |
| Migration-account separation | The account running migrations (schema changes) is distinct from and more privileged than the ordinary application runtime account, used only during deployment |
| Reporting-account separation | A read-only reporting/analytics credential, distinct from the read-write application account, for BC-33 Reporting and Analytics per [../01-architecture/reporting-and-read-model-boundaries.md](../01-architecture/reporting-and-read-model-boundaries.md) |
| Read-only access where appropriate | Any support/reporting access that does not require writes uses a read-only credential |
| Production-repair governance | Direct production database edits occur only through the documented emergency-repair procedure in [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture) — never as a routine support process, per working rule 30 |
| Audit of privileged database access | Any direct database access outside the application's normal query path is itself an audit-relevant privileged-access event |
| Restore-access controls | Restoring from backup requires the same privileged-access governance as any other production-data-affecting action, per [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md) |

## 3. MinIO and Object-Storage Security

| Control | Direction |
|---|---|
| Private buckets by default | No bucket is publicly readable by default; public exposure is an explicit, deliberate configuration for specifically approved content categories only |
| Separate public and restricted object policies | Public-tier objects (e.g., published media) and Restricted/Highly Restricted objects (eligibility evidence, medical attachments) use distinct bucket/policy configurations, never comingled under one access policy |
| No direct unrestricted object URLs | An object is never retrievable via a permanent, unauthenticated direct URL for anything above Public classification |
| Signed temporary access | Restricted-tier downloads use short-lived signed URLs, generated only after the requesting user's authorization is confirmed |
| Short-lived download authorization | Signed URL validity is bounded to the minimum practical window for the download to complete |
| Upload authorization | Every upload is authorized before the object is accepted, per [../01-architecture/object-storage-and-document-runtime.md, Section 2](../01-architecture/object-storage-and-document-runtime.md#2-document-flow) |
| Content-type validation | Declared and actual content type are both checked; a mismatch is treated as suspicious, per [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) |
| Checksum verification | Every stored object's checksum is recorded and periodically re-verified, per [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md) |
| Malware scanning | Per [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) |
| Object key opacity | Object keys carry no sensitive meaning and are not predictable/enumerable, per [../02-data/identifier-and-reference-strategy.md](../02-data/identifier-and-reference-strategy.md) |
| Encryption | Server-side encryption at rest is a candidate control for MinIO, per [cryptography-key-and-secret-management.md](cryptography-key-and-secret-management.md) |
| Bucket access isolation | Environments (Local/Development/Staging/Production) use fully separate buckets/credentials |
| Service-account restrictions | The application's MinIO credential is scoped to only the buckets/operations it needs, per Section 4 below |
| Versioning readiness | Object versioning is a candidate control supporting the correction/supersession model, not yet committed to a specific configuration |
| Retention readiness | Object-level retention mirrors [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) — no period invented here either |
| Legal hold readiness | A legal/operational hold on an object overrides any retention-driven deletion, restated from [../02-data/object-metadata-and-file-lifecycle.md, Section 1](../02-data/object-metadata-and-file-lifecycle.md#1-authoritative-metadata-concepts) |
| Download audit | Every Restricted/Highly Restricted-tier download is an audit-relevant event |
| Orphan-object detection | Per [../02-data/object-metadata-and-file-lifecycle.md, Section 4](../02-data/object-metadata-and-file-lifecycle.md#4-reconciliation-process-conceptual) |
| Missing-object reconciliation | Same reconciliation process, detecting metadata referencing an absent object |
| Backup protection | MinIO backups follow the same lock-step metadata+content principle as [../02-data/backup-restore-and-data-recovery.md, Section 2](../02-data/backup-restore-and-data-recovery.md#2-backup-requirements) |

## 4. Service-Account Security

Restated and extended from [../01-architecture/device-and-service-identity-model.md, Sections 5–8](../01-architecture/device-and-service-identity-model.md#5-service-identity-categories) for the infrastructure-security audience:

| Requirement | Direction |
|---|---|
| Non-human identity | Every service (queue worker, scheduled job, reporting service, AI service, import/export process) has its own identity, never impersonating a human account |
| Narrow permissions | Scoped to exactly the operation the service performs |
| Named ownership | Every service identity has a designated owning team/role |
| Rotation | Service credentials are rotatable on a defined (not-yet-fixed) cadence |
| Expiry | Service credentials are not permanent, unexpiring secrets |
| Environment separation | A service identity's credentials never cross environment boundaries |
| No interactive login unless required | A service account does not have interactive/web login capability unless a specific operational need justifies it |
| No human-password reuse | Restated as absolute |
| No shared administrator role | A service account is never granted the Platform Super Administrator role as a convenience |
| Audit visibility | Every service-identity action is attributed and auditable, never anonymized as "system" |
| Scoped database access | Per Section 2's migration/reporting-account separation |
| Scoped MinIO access | Per Section 3's service-account restrictions |
| Scoped queue access | A queue worker's credential is scoped to the queue categories it processes |
| Revocation | Independently revocable from any human account, per [../01-architecture/access-review-and-revocation.md, Section 7](../01-architecture/access-review-and-revocation.md#7-what-can-be-revoked) |
| Compromise response | Per [../01-architecture/device-and-service-identity-model.md, Section 8](../01-architecture/device-and-service-identity-model.md#8-service-compromise) |

## 5. Network and Runtime Security

| Control | Direction |
|---|---|
| TLS | All traffic (web, API, device, mobile sync) encrypted in transit — non-negotiable for any environment beyond Local, restated from [../01-architecture/runtime-security-architecture.md, Section 3](../01-architecture/runtime-security-architecture.md#3-runtime-security-controls) |
| Internal network segmentation | Database, Redis, MinIO, and Reverb occupy a network segment not directly reachable from the public internet |
| Database isolation | Restated from Section 2 |
| Redis isolation | Restated from Section 1 |
| MinIO isolation | Restated from Section 3 |
| Reverb isolation | Reverb's public-facing WebSocket endpoint is the only intentionally exposed real-time surface; its internal coordination is not separately exposed |
| Administrative endpoint restriction | The Horizon dashboard and any future admin-only tooling are restricted to Platform/Security Administrator roles with elevated audit, and are never exposed without authentication |
| Firewall controls | A future infrastructure-phase configuration concern, architecturally anticipated here, not defined |
| Reverse proxy | A future infrastructure-phase configuration concern |
| Secure headers | Standard hardening headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) applied platform-wide — specific policy values are an implementation-phase decision |
| Rate limiting | Restated from [application-api-and-client-security.md, Section 2](application-api-and-client-security.md#2-api-security) |
| DDoS-readiness | A candidate future control (upstream traffic-scrubbing/CDN capability) — not committed to a specific vendor or mechanism |
| Health-endpoint exposure limits | Health-check endpoints (per [../01-architecture/observability-and-error-handling.md, "Health-Check Type Separation"](../01-architecture/observability-and-error-handling.md)) reveal minimal operational detail publicly |
| Environment separation | Restated across Sections 1–3 |
| Production access | Per [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md) |
| Backup network isolation | Backup storage is not reachable from the same network path as ordinary application traffic |
| Local venue network risks | Venue networks are a named constraint (unreliable/limited connectivity per [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md)) — devices operating on untrusted local venue networks rely on TLS and device/operator authentication, never on network trust |
| Hybrid-cloud trust boundaries | Deployment topology (cloud/on-prem/hybrid) is not yet decided (per [../00-product/phase-0.1-product-foundation.md, Section 17](../00-product/phase-0.1-product-foundation.md#17-deployment-model)); this document's boundaries apply regardless of which topology is eventually chosen |

**No actual network, firewall, or TLS configuration is created by this document** — it defines requirements a future infrastructure phase must satisfy.

## 6. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably deployment topology, specific TLS/header policy values, and DDoS-mitigation approach, all deferred to a later infrastructure phase.
