# PMMS Deployment Strategies, Rollbacks, and Maintenance

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [database-migration-and-release-safety.md](database-migration-and-release-safety.md) · [../04-quality/resilience-backup-recovery-and-continuity-testing.md](../04-quality/resilience-backup-recovery-and-continuity-testing.md)

This document evaluates deployment strategies, defines zero-downtime readiness, maintenance-mode behavior, and rollback architecture. **No deployment script is created here; no zero-downtime or high-availability capability is claimed without implementation and evidence**, per working rule 24.

---

## 1. Deployment Strategy Evaluation

| Strategy | Assessment |
|---|---|
| In-place deployment | Simple, low infrastructure cost, but higher disruption risk — the current single-server-capable pilot topology's natural starting point |
| Rolling deployment | Useful once multiple application instances exist — new instances come up, old instances drain, with no single moment of full unavailability |
| Blue-green deployment | Safer switching (an entirely parallel environment is validated before traffic cuts over) at higher infrastructure cost — a candidate for Production once the platform's institutional stakes justify the added cost |
| Canary deployment | Useful for controlled traffic exposure (a small percentage of traffic sees the new version first) — a candidate for validating a risky change against real (non-meet-day) traffic before full rollout |
| Feature-flag rollout | Useful for capability control, per [configuration-feature-flag-and-secret-management.md, Section 4](configuration-feature-flag-and-secret-management.md#4-feature-flag-architecture) — **not a substitute for deployment safety**, restated absolutely; a flag controls whether a feature is visible, not whether the underlying deployment itself is safe |

**Recommended initial approach:** in-place deployment for the pilot-scale topology, with rolling deployment as the near-term evolution once more than one application instance exists, and blue-green/canary evaluated for Production once institutional stakes and traffic justify the added infrastructure cost — appropriate for a small team, with a clear evolution path, never claiming a capability (like zero-downtime blue-green) before it's actually implemented and demonstrated.

## 2. Zero-Downtime Readiness

Requirements before zero-downtime deployment can be genuinely claimed:

Backward-compatible application and schema (per [database-migration-and-release-safety.md](database-migration-and-release-safety.md)) · shared session behavior (multi-instance-safe, already satisfied by the database session driver) · queue compatibility (old and new job payload shapes coexist during a rollout) · Reverb compatibility (broadcast payload versioning, per [../04-quality/queue-realtime-cache-and-storage-testing.md, Section 2](../04-quality/queue-realtime-cache-and-storage-testing.md#2-reverb-and-real-time-testing)) · asset versioning (Section 6 below) · cache compatibility (a cached value from the old version doesn't break the new version) · health checks (per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md)) · load-balancer support (once more than one instance exists) · safe worker restart (per [application-worker-scheduler-and-realtime-deployment.md, Section 2](application-worker-scheduler-and-realtime-deployment.md#2-queue-worker-and-horizon-deployment)) · database migration sequencing (per [database-migration-and-release-safety.md, Section 2](database-migration-and-release-safety.md#2-database-release-safety--phased-changes)) · mobile-client compatibility (Section 8, [device deployment considerations, in application-worker-scheduler-and-realtime-deployment.md](application-worker-scheduler-and-realtime-deployment.md)).

**Do not claim zero downtime until demonstrated** — restated absolutely per the phase's own working instruction; this section is a readiness checklist, not an achieved capability.

## 3. Maintenance-Mode Strategy

| Concern | Direction |
|---|---|
| Administrative maintenance | Laravel's built-in maintenance mode (`php artisan down`/`up`) is the confirmed starting mechanism for the administrative application |
| Read-only mode | A candidate degraded mode (serving already-published data without accepting writes) distinct from full maintenance — evaluated, not committed |
| Public portal availability | The public portal's availability during administrative maintenance is a deliberate design choice — restated from [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 18](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary), the public portal's independence from administrative capacity means it can often remain available during an administrative-only maintenance window |
| Queue behavior | Queued jobs pause or continue depending on the maintenance's scope — a schema-changing maintenance window pauses jobs touching the changing tables |
| Reverb behavior | Real-time delivery pauses gracefully during maintenance, with clients falling back to normal polling per [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md) |
| API behavior | API clients receive a clear maintenance-mode response, not a generic error |
| Mobile synchronization | Mobile clients queue their offline work locally and retry once maintenance ends — never lose captured data due to a maintenance window |
| User communication | A maintenance window is communicated in advance where feasible, especially avoiding scheduling during known meet-day windows per working rule 45 |
| Estimated completion updates | A maintenance page communicates expected duration where known |
| Emergency bypass restrictions | Any bypass of maintenance mode (e.g., for the deploying engineer to verify the fix) is itself a controlled, audited action |
| Post-maintenance verification | Maintenance mode is not lifted until the deployment/change is verified successful, per [incident-problem-change-and-release-management.md](incident-problem-change-and-release-management.md) |

## 4. Rollback Architecture

| Dimension | Rollback Consideration |
|---|---|
| Application artifact | Rolling back to the prior built artifact is straightforward if no schema/data change accompanied the release |
| Frontend assets | Prior asset versions remain available briefly (Section 6) to support rollback without a broken-asset window |
| Flutter release | App-store-distributed releases cannot be "rolled back" in the same sense — a problematic mobile release requires a new, corrected release, staged per app-store review timelines |
| Configuration | Configuration changes accompanying a release are rolled back alongside the code |
| Feature flags | The fastest rollback mechanism for a flagged feature — disable the flag, no redeploy required |
| Database | Requires explicit design (Section 5) — restated absolutely, never assumed automatically reversible |
| Object-storage changes | Object deletion/replacement requires retention awareness (per [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md)) — a "rollback" that would delete a newly-created object must account for whether that object has already been referenced/downloaded |
| Queue messages | In-flight messages referencing a new payload shape may not process correctly against rolled-back code — a coordinated rollback considers queue-message compatibility |
| Reverb payload compatibility | Same consideration for in-flight broadcast payloads |
| Public projections | A projection rebuild (per [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md)) may be required post-rollback to reflect the rolled-back state correctly |

### Rules

- **Application rollback may not be safe after an irreversible schema change** — restated absolutely.
- **Forward-fix may be safer** — restated below (Section 5).
- **Data rollback requires explicit design** — there is no generic "undo the last deployment's data effects" mechanism; each high-integrity domain's own correction/versioning discipline (per [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md)) is the actual data-correction path, not a rollback.
- **Object deletion and file replacement require retention awareness** — restated from the table above.
- **Rollback decisions must be auditable** — a rollback is itself a change-management event, per [incident-problem-change-and-release-management.md](incident-problem-change-and-release-management.md).
- **Restore is not a routine rollback mechanism** — restated absolutely; restoring from backup (per [backup-restore-disaster-recovery-and-continuity.md](backup-restore-disaster-recovery-and-continuity.md)) is a disaster-recovery action, not an ordinary deployment-rollback tool, given the data loss it implies for anything written since the backup point.

## 5. Forward-Fix Strategy

When rollback isn't safe (an irreversible schema change has occurred, or data has already been written against the new shape), **forward-fix** — deploying a new, corrected version rather than reverting — is the preferred strategy. This requires the same release-quality gates as any other deployment (per [ci-cd-and-release-pipeline-architecture.md](ci-cd-and-release-pipeline-architecture.md)), applied under incident time pressure without skipping them, restated from [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md)'s discipline against ungated emergency changes.

## 6. Static Asset Deployment

Versioned assets (Vite's built-in content-hash-based filenames) · cache busting (achieved automatically by the versioned filenames) · CDN readiness (a candidate future addition, not yet adopted) · integrity (subresource-integrity hashes, a candidate control) · rollback (prior asset versions retained briefly to support in-flight-request compatibility during a rollback) · public cache (appropriately long cache lifetimes for versioned, immutable assets) · source maps (generated for debugging) · source-map access control (source maps are not publicly served in Production, preventing source-code structure disclosure) · compression (gzip/brotli, standard web-server capability) · image optimization (a candidate build-pipeline addition) · progressive loading (a candidate frontend-architecture consideration, not a deployment concern per se).

## 7. Object-Storage Deployment

MinIO deployment changes (bucket-policy updates, credential rotation) follow the same change-management discipline as any other production-affecting change — restated from [incident-problem-change-and-release-management.md](incident-problem-change-and-release-management.md); a bucket-policy change is reviewed with the same rigor as a database migration given its direct classification/access-control impact, per [../03-security/infrastructure-runtime-and-network-security.md, Section 3](../03-security/infrastructure-runtime-and-network-security.md#3-minio-and-object-storage-security).

## 8. Queue, Reverb, and Cache Deployment Safety

**Queue deployment safety:** a deployment doesn't discard in-flight queued jobs — workers drain gracefully (Section 2, [application-worker-scheduler-and-realtime-deployment.md](application-worker-scheduler-and-realtime-deployment.md#2-queue-worker-and-horizon-deployment)) and job payload shape changes are backward-compatible during the rollout window. **Reverb deployment safety:** a Reverb restart is planned around known usage windows (never during an active meet-day scoring window without a specific, urgent reason) and clients' fallback-polling design (per [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md)) absorbs the transient disconnection. **Cache deployment safety:** a deployment that changes a cached value's shape either versions the cache key or accepts a brief inconsistency window resolved by the cache's own TTL — never assumes the old cached shape is compatible with new code without verification.

## 9. Mobile-Client and API Backward Compatibility

A backend deployment does not break currently-installed mobile app versions still in the field — restated from [../04-quality/flutter-mobile-device-and-offline-testing.md](../04-quality/flutter-mobile-device-and-offline-testing.md); the API contract a deployed mobile version depends on remains functional for at least the app-store-review-driven grace period until users can reasonably be expected to have updated. API backward compatibility more broadly follows [../04-quality/api-contract-and-integration-testing.md, Section 2](../04-quality/api-contract-and-integration-testing.md#2-contract-testing)'s versioning/deprecation discipline — a breaking API change is a new version, not a silent change to an existing one.

## 10. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably when blue-green/canary deployment is adopted (dependent on infrastructure maturity beyond the initial pilot topology) and the minimum-supported-mobile-app-version policy specifics.
