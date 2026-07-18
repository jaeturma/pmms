# PMMS Horizontal Scaling and Stateless-Application Architecture

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../05-devops/deployment-topology-and-runtime-units.md](../05-devops/deployment-topology-and-runtime-units.md) · [scalability-and-workload-isolation-architecture.md](scalability-and-workload-isolation-architecture.md)

---

## 1. Stateless Application Scaling Requirements

No local session dependence · no local authoritative files · shared cache where approved (Redis, per [redis-cache-session-lock-and-rate-limit-scaling.md](redis-cache-session-lock-and-rate-limit-scaling.md)) · shared object storage (MinIO, never local disk for authoritative content) · externalized configuration · versioned assets · repeatable deployments · safe health checks · node replacement without service loss · load-balancer readiness.

**Every application node must be replaceable without data loss** — restated as this document's governing rule, directly enabling the Production-Oriented Topology candidate in [../05-devops/deployment-topology-and-runtime-units.md, Section 1](../05-devops/deployment-topology-and-runtime-units.md#1-deployment-topology).

## 2. Session Scaling

Evaluated: database sessions (current confirmed default, `SESSION_DRIVER=database`) · Redis sessions (target direction for Staging/Pilot/Production, per [../01-architecture/caching-and-session-architecture.md, Section 2](../01-architecture/caching-and-session-architecture.md#2-session-architecture)) · stateless API tokens · mobile tokens.

| Requirement | Direction |
|---|---|
| Tenant context | A session carries resolved tenant context, per [tenant-context-identification-and-propagation.md, Section 3](tenant-context-identification-and-propagation.md#3-tenant-context-propagation) |
| Revocation | A session must be revocable server-side at any time — restated from Phase 0.6's identity/session security controls |
| Failover | A horizontally-scaled deployment requires session data reachable from any node — database or Redis session storage, never local in-process session state |
| Session invalidation | Immediate on logout, credential revocation, or security event, regardless of which node handles the request |
| Environment isolation | Session stores are never shared across environments (Local/Dev/Staging/Pilot/Production/DR) |
| Privileged session restrictions | An elevated/impersonation session (per Phase 0.6) carries additional restrictions and shorter validity, unchanged by horizontal scaling |

**No session storage is selected/changed during this phase** — restated absolutely from Phase 0.4's identical discipline; this document confirms the existing target direction without moving it further toward implementation.

## 3. Horizontal Versus Vertical Scaling

Vertical scaling (a larger single server) remains the confirmed pilot-scale starting point, per [../05-devops/deployment-topology-and-runtime-units.md, "Initial Practical Topology"](../05-devops/deployment-topology-and-runtime-units.md#1-deployment-topology). Horizontal scaling (multiple web/worker nodes) is the Production-Oriented Topology candidate, adopted only once vertical scaling's measured limits are reached — restated from [scalability-and-workload-isolation-architecture.md, Section 1](scalability-and-workload-isolation-architecture.md#1-scalability-model).

## 4. Load-Balancer Readiness

A future horizontal deployment requires a load balancer or reverse proxy distributing traffic across web nodes — no specific product is selected, tracked as an existing open decision ([DV-09](../05-devops/devops-open-decisions.md#dv-09--reverse-proxy-product-selection)). Health-check endpoints must exist for safe node rotation, without exposing internal diagnostic detail publicly.

## 5. Deployment Repeatability

Every node in a horizontally-scaled deployment must be produced from the same repeatable deployment artifact — restated from [../05-devops/ci-cd-and-release-pipeline-architecture.md](../05-devops/ci-cd-and-release-pipeline-architecture.md)'s existing CI/CD discipline, extended here only to confirm horizontal scaling does not introduce configuration drift between nodes.

## 6. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-17 (horizontal-scaling trigger threshold, deferred pending pilot evidence) — and the existing [DV-09](../05-devops/devops-open-decisions.md#dv-09--reverse-proxy-product-selection) cross-reference.
