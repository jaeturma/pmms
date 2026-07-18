# PMMS Public Portal, API, Mobile Synchronization, and Device Scale

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) · [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md)

---

## 1. Public Portal Scaling

Patterns: public projections (unchanged, per Phase 0.5) · HTTP caching · CDN readiness (per [cache-cdn-static-asset-and-edge-delivery-architecture.md](cache-cdn-static-asset-and-edge-delivery-architecture.md)) · precomputed pages · stale-while-revalidate evaluation · anonymous rate limiting · bot control · image optimization · workload isolation (Section, [scalability-and-workload-isolation-architecture.md, Section 3](scalability-and-workload-isolation-architecture.md#3-workload-isolation-rules)) · graceful degradation (Section, [backpressure-load-shedding-graceful-degradation-and-brownout.md](backpressure-load-shedding-graceful-degradation-and-brownout.md)).

**Public traffic must not query high-integrity transactional tables directly for every request** — restated absolutely as this section's governing rule; the public portal reads exclusively from BC-29's projections, unchanged since Phase 0.4.

## 2. Administrative Workload Scaling

Prioritized: strong authorization (never relaxed for performance) · current state (administrative views read current, not cached-stale, data by default) · transactional correctness · targeted caching (only where staleness is explicitly acceptable) · pagination · efficient filters · workload-specific queues · real-time state recovery (per [../08-workflows/realtime-broadcast-and-reverb-message-architecture.md, Section 9](../08-workflows/realtime-broadcast-and-reverb-message-architecture.md#9-state-resynchronization)) · protection from public load (restated from Section 1's isolation rule).

## 3. API Scaling

Pagination · request limits · rate limits (per [redis-cache-session-lock-and-rate-limit-scaling.md, Section 8](redis-cache-session-lock-and-rate-limit-scaling.md#8-rate-limiting)) · idempotency (unchanged, per Phase 0.11) · compression · response shaping · versioning · caching where safe · API-client quotas · tenant quotas (per [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 6](tenant-configuration-branding-entitlement-and-quota-architecture.md#6-tenant-quotas)) · device-specific limits · public-versus-private isolation.

This extends, and does not redefine, the six API categories in [../01-architecture/api-and-client-boundaries.md, Section 1](../01-architecture/api-and-client-boundaries.md#1-api-categories) (Internal Mobile API, Device API, Public API, Administrative Integration API, Webhook API, Synchronization API) — each category's scaling posture differs by its own criticality and tenant scope.

## 4. Mobile Synchronization Scaling

Incremental sync · change tokens or version markers · pagination · batching · compression · limited offline datasets (scoped to the operator's actual assignment, per [../01-architecture/offline-and-synchronization-boundaries.md](../01-architecture/offline-and-synchronization-boundaries.md)) · per-device cursor · resumable sync · conflict handling (unchanged, per [../01-architecture/offline-sync-runtime-architecture.md, Section 4](../01-architecture/offline-sync-runtime-architecture.md#4-conflict-detection-and-resolution)) · retry · rate limits · tenant and meet isolation (per [tenant-aware-runtime-workflow-event-and-ai-boundaries.md, Section 6](tenant-aware-runtime-workflow-event-and-ai-boundaries.md#6-tenant-aware-mobile-synchronization)) · stale-device cleanup.

**Avoid full data refresh for every sync** — restated absolutely as this section's governing rule; every sync operation transfers only the delta since the device's last acknowledged cursor, never the operator's entire assigned dataset.

## 5. Device Fleet Scaling

Device identity · tenant ownership · meet and venue assignment · heartbeat · software version · health · credentials · quotas · revocation (prioritized, per [../08-workflows/accreditation-access-validation-and-security-workflows.md, Section 1](../08-workflows/accreditation-access-validation-and-security-workflows.md#1-accreditation-workflow-wf-05-bc-19-high-integrity)) · fleet views (an ICT operational dashboard, per [../08-workflows/committee-logistics-medical-finance-and-ict-workflows.md, Section 4](../08-workflows/committee-logistics-medical-finance-and-ict-workflows.md#4-ict-workflow-wf-25-bc-27-new-numbering)) · staged updates (a device-software rollout reaches devices incrementally, never all at once) · telemetry retention.

## 6. Registration, Accreditation, and QR-Validation Peaks (Cross-Reference)

Full detail: [workload-capacity-and-scale-assumptions.md, Section 4](workload-capacity-and-scale-assumptions.md#4-peak-load-scenarios-named-not-sized) — this document adds only the API/mobile/device-specific mitigation: batched device requests during a registration peak, and QR-validation's own offline-provisional tolerance (per [../08-workflows/accreditation-access-validation-and-security-workflows.md, Section 2](../08-workflows/accreditation-access-validation-and-security-workflows.md#2-access-validation-workflow-wf-16-bc-20-high-integrity-offline-critical)) as the primary defense against a connectivity-driven QR-validation bottleneck.

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-25 (mobile-sync batch-size and pagination defaults, deferred pending pilot evidence).
