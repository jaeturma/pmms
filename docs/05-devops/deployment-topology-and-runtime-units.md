# PMMS Deployment Topology and Runtime Units

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/phase-0.4-application-integration-runtime-architecture.md](../01-architecture/phase-0.4-application-integration-runtime-architecture.md) · [containerization-and-docker-adoption-roadmap.md](containerization-and-docker-adoption-roadmap.md) · [network-reverse-proxy-tls-and-domain-architecture.md](network-reverse-proxy-tls-and-domain-architecture.md)

This document defines candidate deployment topologies and per-runtime-unit deployment behavior. **No topology is selected without capacity and pilot evidence, and no final cloud provider is chosen unless already approved**, per working rule 23.

---

## 1. Deployment Topology

### Initial Practical Topology

For development or an initial pilot, PMMS may reasonably begin on **one sufficiently sized server**, with process and data isolation maintained through separate service processes (not through skipping isolation):

Reverse proxy · Laravel web runtime · queue workers · scheduler · Reverb · MySQL · Redis · MinIO.

This is explicitly a *pilot-scale* candidate, not a claim of production-grade high availability — restated per working rule 24, no high-availability claim is made for a single-server topology.

### Production-Oriented Topology

As load and institutional commitment grow, possible separation:

Web application nodes (horizontally scalable) · worker nodes (horizontally scalable, isolated from web nodes) · Reverb node(s) (isolated given its persistent-connection nature) · MySQL service (dedicated, possibly managed) · Redis service (dedicated, possibly managed) · MinIO service (dedicated, possibly managed) · a public delivery layer (isolated from administrative capacity, restated from Phase 0.4) · a backup destination (physically/logically separate) · a monitoring system (per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md)).

### Hybrid Venue Topology

Reflecting PMMS's offline-capable, venue-distributed operational reality:

Central cloud or data-center PMMS (the authoritative system) · an optional local venue server (buffering connectivity loss, per [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md)) · mobile devices · scanners · the local venue network · delayed synchronization back to the central system.

**No final topology is selected without capacity and pilot evidence** — restated absolutely; the three candidates above represent an evolution path, not a menu the platform must immediately choose from.

## 2. Runtime Deployment Units

| Unit | Deployment and Restart Behavior |
|---|---|
| Web application | Stateless; multiple instances behind a load balancer/reverse proxy where scaled; a rolling restart is safe once zero-downtime readiness (per [deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md)) is confirmed |
| Queue workers | Horizon-supervised; a deployment restart drains or safely interrupts in-flight jobs, per [../04-quality/queue-realtime-cache-and-storage-testing.md, Section 1](../04-quality/queue-realtime-cache-and-storage-testing.md#1-queue-and-horizon-testing) |
| Horizon supervisors | Manage worker pools per queue category; restart behavior mirrors queue workers |
| Scheduler | A single-execution-guaranteed process (Section, [../01-architecture](../01-architecture/) runtime architecture) — restart must not cause a missed or duplicated scheduled run |
| Reverb | A persistent-connection process; restart drops active WebSocket connections, which clients reconnect to per [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md)'s fallback-polling design — an acceptable, transient, non-data-loss event |
| Public portal | Deployed and scaled independently from the administrative application where the topology separates them, per [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 18](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary) |
| Mobile API | Deployed as part of the core application unless/until traffic patterns justify separation |
| Device API | Same as Mobile API |
| Report workers | A queue-worker specialization for the `documents`/`exports` categories, per [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md) |
| Media workers | A queue-worker specialization for the `media` category |
| Import workers | A queue-worker specialization for the `imports` category |
| Notification workers | A queue-worker specialization for the `notifications` category |

## 3. Application, Worker, Scheduler, and Real-Time Deployment Detail

### Application Runtime Deployment

The Laravel web runtime is deployed as a stateless unit — no runtime holds authoritative in-process state; every request's authoritative data comes from MySQL, per [../02-data/logical-data-architecture.md, Section 2](../02-data/logical-data-architecture.md#2-source-of-truth-model). This statelessness is what makes horizontal scaling and rolling deployment viable in the first place.

### Queue-Worker and Horizon Deployment

Horizon supervises worker pools per the 11 queue categories from [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md), with the `critical` category isolated from bulk categories at the worker-pool level, not merely the queue-name level — restated from Phase 0.4, operationalized here as a deployment requirement (dedicated worker capacity, not shared).

### Reverb Deployment

Reverb is deployed as its own process/service given its persistent-connection nature differs fundamentally from the stateless web runtime's request/response model — co-locating it with the web application is a viable initial-topology simplification, but scaling it independently (Section 1, "Production-Oriented Topology") is anticipated.

### Scheduler Deployment

The scheduler runs as a single logical instance — restated absolutely: **multiple scheduler instances must never run concurrently against the same environment**, since Laravel's scheduler does not natively prevent duplicate execution across separate processes without an explicit overlap-prevention mechanism (a candidate future implementation control, per [../04-quality/queue-realtime-cache-and-storage-testing.md](../04-quality/queue-realtime-cache-and-storage-testing.md)).

## 4. Local Venue Server Deployment

Where a local venue server is used (per the Hybrid Venue Topology, Section 1), it runs a scoped subset of PMMS capability — buffering and locally validating offline-capable workflows (scoring, access validation) per [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md) — never operating as an independent authoritative system. Its deployment lifecycle (provisioning, update, decommissioning) follows [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md).

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably deployment topology selection itself (blocking, mirrors [Phase 0.1, Section 17](../00-product/phase-0.1-product-foundation.md#17-deployment-model) and [../03-security/security-open-decisions.md, SD-22](../03-security/security-open-decisions.md#sd-22--deployment-topology-cross-reference)), and whether a local venue server is provisioned for the first pilot or deferred.
