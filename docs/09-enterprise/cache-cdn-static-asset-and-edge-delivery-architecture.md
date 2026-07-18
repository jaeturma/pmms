# PMMS Cache, CDN, Static-Asset, and Edge-Delivery Architecture

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [redis-cache-session-lock-and-rate-limit-scaling.md](redis-cache-session-lock-and-rate-limit-scaling.md) · [minio-object-storage-media-and-delivery-scaling.md, Section 7](minio-object-storage-media-and-delivery-scaling.md#7-signed-object-delivery-and-public-media-delivery)

**No CDN provisioning or configuration is created here.**

---

## 1. CDN Readiness

A candidate future capability for reducing origin load and improving public-content latency — no CDN provider is selected, consistent with working rule 12 (no provisioning of DNS, certificates, or regions) and the still-unresolved [DV-01](../05-devops/devops-open-decisions.md#dv-01--deployment-topology-and-cloud-versus-on-premise) deployment-topology decision this depends on.

## 2. Candidate Content

May be cached at the edge: static assets (compiled CSS/JS, per the confirmed Vite build pipeline) · approved public media (per [minio-object-storage-media-and-delivery-scaling.md, Section 7](minio-object-storage-media-and-delivery-scaling.md#7-signed-object-delivery-and-public-media-delivery)) · public portal pages (where fully public and non-personalized) · public projections (schedules, published results, medal tally — always reflecting their certified/published state, never provisional) · scoreboard display assets.

## 3. Content Never Cached at the Edge

**Do not cache** administrative pages · private API responses · signed restricted content beyond its own authorization window · tenant-protected data · medical, eligibility, finance, or audit content. Restated absolutely per working rule 50 ("CDN and public caches must never receive restricted data") — this is an architectural exclusion, not a configuration option to be relaxed later.

## 4. Static-Asset Delivery

Versioned, immutable asset filenames (content-hash-based, consistent with Vite's default build output) enable long-lived edge/browser caching without staleness risk — a new deployment produces new filenames, never requiring cache invalidation of the old ones.

## 5. Image and Media Delivery

Approved public images (athlete profile photos where publication is approved, per [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md), event/venue media) may be optimized and delivered through the same edge path as static assets — subject to the same "no automatic public display merely because an image exists" rule restated from Phase 0.9.

## 6. Signed-Object Delivery (Cross-Reference)

Full detail: [minio-object-storage-media-and-delivery-scaling.md, Section 7](minio-object-storage-media-and-delivery-scaling.md#7-signed-object-delivery-and-public-media-delivery) — a signed URL's short validity window is itself a scaling-relevant constraint: it cannot be cached at the edge beyond its own expiry without breaking the authorization it represents.

## 7. HTTP Caching

Standard HTTP cache-control semantics apply to public, non-personalized responses (public schedules, published results) with explicit `Cache-Control` and freshness headers reflecting the underlying projection's actual update cadence — never a blanket long TTL that could mask a since-corrected published record.

## 8. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-26 (CDN provider selection, contingent on DV-01) and ED-27 (edge-cache TTL policy for public projections).
