# PMMS Availability, Resilience, and Failure-Domain Architecture

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/resilience-performance-and-scaling.md, Section 3](../01-architecture/resilience-performance-and-scaling.md#3-availability-and-resilience) · [performance-budget-and-service-level-architecture.md, Section 3](performance-budget-and-service-level-architecture.md#3-per-capability-availability-differentiation)

---

## 1. Availability Model

Availability is defined per capability, restated unchanged from [performance-budget-and-service-level-architecture.md, Section 3](performance-budget-and-service-level-architecture.md#3-per-capability-availability-differentiation) — critical administrative operations, public portal, live scoreboards, reports, AI, and historical archive each carry their own availability posture. **No availability percentage is claimed or targeted without the implementation and evidence to support it** — restated absolutely per working rule 15.

## 2. Resilience Model

Resilience is the platform's ability to continue operating, in a degraded but honest form, when a dependency fails — distinct from availability (whether a capability is reachable at all). PMMS's resilience model rests on: independently scalable/isolable workloads (Section, [scalability-and-workload-isolation-architecture.md](scalability-and-workload-isolation-architecture.md)) · graceful degradation (Section, [backpressure-load-shedding-graceful-degradation-and-brownout.md](backpressure-load-shedding-graceful-degradation-and-brownout.md)) · durable authoritative state (MySQL, restated per working rule 29) · offline-tolerant critical workflows (per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md)).

## 3. Failure Domains

| Failure Domain | Containment Expectation |
|---|---|
| Application node | Stateless — replaceable without data loss, per [horizontal-scaling-and-stateless-application-architecture.md, Section 1](horizontal-scaling-and-stateless-application-architecture.md#1-stateless-application-scaling-requirements) |
| Worker pool | Isolated per queue category — a failing job type does not consume capacity for healthy jobs, per [horizon-queue-worker-and-background-processing-scaling.md, Section 2](horizon-queue-worker-and-background-processing-scaling.md#2-horizon-scaling-documentation-only) |
| Reverb process | Isolated from web/worker capacity; degrades to polling on failure |
| Redis | Non-authoritative — loss is disposable by design, restated unchanged from Phase 0.8 |
| MySQL | Authoritative — loss requires the full recovery architecture in [backup-replication-restore-and-point-in-time-recovery.md](backup-replication-restore-and-point-in-time-recovery.md) |
| MinIO | Authoritative for object content — same recovery discipline |
| Reverse proxy | A single point of failure in the pilot topology, a candidate load-balanced tier in the Production-Oriented Topology |
| Network | Venue-local network failures are addressed by offline-first design, per [business-continuity-meet-day-continuity-and-manual-fallback.md](business-continuity-meet-day-continuity-and-manual-fallback.md) |
| Region | Not addressed by any committed mechanism — multi-region readiness only, per [disaster-recovery-topology-failover-and-failback.md, Section 4](disaster-recovery-topology-failover-and-failback.md#9-multi-region-readiness-not-committed) |
| External provider (AI, notification, SMS/email) | Fallback and disablement, per Section 5 |
| Venue network | Offline-tolerant critical workflows (scoring, access validation) |
| Mobile client | Local draft/queue persistence, resumable sync |
| Device fleet | Individual device failure never blocks other devices' operation, per [public-portal-api-mobile-sync-and-device-scale.md, Section 5](public-portal-api-mobile-sync-and-device-scale.md#5-device-fleet-scaling) |

## 4. Dependency-Failure Behavior (General Pattern)

For every dependency: detection · timeout · retry · circuit-breaker readiness (evaluated, not implemented) · fallback · degraded behavior · alert · recovery · reconciliation · user communication.

## 5. Specific Dependency-Failure Behaviors

| Dependency | Failure Behavior |
|---|---|
| MySQL | Application cannot serve authoritative operations — full outage for write/read-current paths; public projections (already cached/derived) may remain briefly servable from cache |
| Redis | Application degrades to database-backed cache/session (already the confirmed default) — restated as evidence Redis loss is genuinely disposable |
| MinIO | File upload/download unavailable; already-cached signed URLs may remain valid until expiry; no data loss if MySQL metadata remains intact |
| Reverb | Degrades to polling, per [reverb-websocket-and-realtime-scaling.md, Section 6](reverb-websocket-and-realtime-scaling.md#6-graceful-degradation-to-polling) |
| Queue/Horizon | Jobs accumulate in the queue rather than being lost (restated from Phase 0.4's durable-queue design); synchronous critical paths are unaffected since they never depend solely on a queue |
| AI provider | None active (Phase 0.10) — the architecture already requires graceful fallback to non-AI workflows for any future capability |
| Notification provider (email/SMS) | Delivery retries with backoff; failure is observable (per [../08-workflows/notification-and-recipient-resolution-architecture.md, Section 9](../08-workflows/notification-and-recipient-resolution-architecture.md#9-notification-delivery-status)); the underlying decision remains durably recorded regardless |

## 6. Network Partition Behavior

A venue-local network partition is treated as an expected, not exceptional, condition — restated from Phase 0.4's offline-first design for Access Validation and Scoring; a regional or provider-level network partition follows Section 5's dependency-failure patterns.

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-32 (circuit-breaker library/pattern adoption timing).
