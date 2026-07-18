# PMMS Application, Worker, Scheduler, and Real-Time Deployment

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [deployment-topology-and-runtime-units.md](deployment-topology-and-runtime-units.md) · [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md) · [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md)

This document provides deployment-operational depth for the application, worker, scheduler, and real-time runtime units introduced in [deployment-topology-and-runtime-units.md, Section 2](deployment-topology-and-runtime-units.md#2-runtime-deployment-units), plus the Public Portal, Administrative Portal, Mobile API, and Device API deployment surfaces. **No deployment script or process-manager configuration is created here.**

---

## 1. Application Runtime Deployment

The stateless Laravel web runtime scales horizontally behind a load balancer once traffic justifies more than one instance. Deployment requires: configuration caching (`config:cache`, `route:cache`, `view:cache` — standard Laravel release practice) applied consistently across instances, session storage that works correctly across multiple instances (the database session driver, already the confirmed `.env.example` default, inherently supports this; a Redis-backed session driver would require the same multi-instance consideration if ever adopted), and asset-versioning (Section, [static-asset-deployment, in deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md)) so that in-flight requests during a deployment don't reference stale/missing compiled assets.

## 2. Queue-Worker and Horizon Deployment

| Aspect | Direction |
|---|---|
| Worker pools | Sized per queue category's actual volume (per [../02-data/indexing-performance-and-capacity.md, Section 2](../02-data/indexing-performance-and-capacity.md#2-capacity-and-growth-categories)'s volume flags), with `critical` given dedicated, isolated worker capacity |
| Deployment restart | Horizon's graceful-termination behavior (allowing in-flight jobs to complete, or safely re-queueing them) is relied upon rather than a hard process kill |
| Scaling | Additional worker processes for high-volume queue categories (`documents`, `exports`, `media`) scale independently of the web application tier |
| Dashboard access | The Horizon dashboard is restricted to Platform/Security Administrator roles with elevated audit, restated from [../01-architecture/runtime-security-architecture.md, Section 3](../01-architecture/runtime-security-architecture.md#3-runtime-security-controls) |
| Failed-job handling | Failed jobs are retained for review (not silently discarded), feeding [production-support-access-and-data-repair-operations.md, Section 5](production-support-access-and-data-repair-operations.md#5-queue-replay-and-reconciliation) |

## 3. Reverb Deployment

Reverb runs as a dedicated, persistent-connection-serving process. Deployment considerations: a Reverb restart drops active connections (an acceptable, transient event per [deployment-topology-and-runtime-units.md, Section 2](deployment-topology-and-runtime-units.md#2-runtime-deployment-units)), so restarts are scheduled thoughtfully relative to active meet-day usage, per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md); scaling Reverb independently (multiple Reverb instances behind a WebSocket-aware load-balancing configuration) is a candidate future capability once connection volume during a live meet justifies it, not committed to now.

## 4. Scheduler Deployment

Exactly one scheduler instance runs per environment — restated absolutely from [deployment-topology-and-runtime-units.md, Section 3](deployment-topology-and-runtime-units.md#3-application-worker-scheduler-and-real-time-deployment-detail). A deployment that scales the web-application tier horizontally must not also scale the scheduler process; the scheduler is deployed as a singleton, distinct from the horizontally-scaled web tier, even when co-located on the same infrastructure initially.

## 5. Public Portal Deployment

The public portal (BC-29) reads only from approved projections, per [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 18](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary). Its deployment is architecturally and, where the topology allows, physically isolated from administrative/scoring-critical capacity — a public traffic spike (a medal announcement) scales or degrades independently, never consuming capacity Scoring or Access Validation depend on. Initial-topology co-location (Section 1, [deployment-topology-and-runtime-units.md](deployment-topology-and-runtime-units.md#1-deployment-topology)) is acceptable for a pilot scale, with separation as a defined evolution path, not a permanent state.

## 6. Administrative Portal Deployment

The administrative React/Inertia application is deployed as part of the core Laravel application (server-rendered Inertia responses) — no separate deployment unit is required unless a future architectural change (e.g., a fully decoupled SPA) is adopted, which is not the current direction per [../01-architecture/react-inertia-architecture.md](../01-architecture/react-inertia-architecture.md).

## 7. Mobile API and Device API Deployment

Both are deployed as part of the core application initially, per [deployment-topology-and-runtime-units.md, Section 2](deployment-topology-and-runtime-units.md#2-runtime-deployment-units) — sharing the same web-runtime deployment unit as the administrative portal and public portal, distinguished by route/authentication boundary (per [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md)) rather than by separate infrastructure. Separation into dedicated deployment units is a candidate future evolution if device/mobile traffic volume during a live meet demonstrates a need to isolate it from administrative-portal capacity.

## 8. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably the specific worker-pool sizing methodology (dependent on real queue-volume data, per [../04-quality/performance-load-concurrency-and-capacity-testing.md, Section 2](../04-quality/performance-load-concurrency-and-capacity-testing.md#2-load-model-inputs)) and Reverb horizontal-scaling timing.
