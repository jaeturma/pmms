# PMMS Tenant-Aware Runtime, Workflow, Event, and AI Boundaries

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../08-workflows/phase-0.11-event-workflow-notification-automation-architecture.md](../08-workflows/phase-0.11-event-workflow-notification-automation-architecture.md) · [../07-ai/phase-0.10-ai-assisted-platform-architecture.md](../07-ai/phase-0.10-ai-assisted-platform-architecture.md)

This document extends every relevant Phase 0.10/0.11 architecture with an explicit tenant dimension — it does not redefine any workflow, event, queue, notification, or AI-governance rule already established.

---

## 1. Tenant-Aware Workflows

Every workflow instance (per [../08-workflows/business-process-and-state-machine-architecture.md, Section 3](../08-workflows/business-process-and-state-machine-architecture.md#3-long-running-workflow-architecture)) carries its resolved tenant context as part of its durable process-instance state — a workflow never spans tenants implicitly; a cross-tenant workflow (Section, [multi-tenant-product-and-organization-model.md, Section 3](multi-tenant-product-and-organization-model.md#3-open-structural-questions-not-finalized-without-product-validation)) requires explicit design, not an accident of shared infrastructure.

## 2. Tenant-Aware Events

Event metadata (per [../08-workflows/event-metadata-versioning-ordering-and-correlation.md, Section 1](../08-workflows/event-metadata-versioning-ordering-and-correlation.md#1-event-metadata-conceptual)) gains a candidate `tenant_id` field alongside its existing `organization_id`/`meet_id` scope fields — an event consumer resolves and respects this field identically to how it already respects scope, never processing an event outside its own tenant boundary unless the consumer is an explicitly-designated platform-level, cross-tenant process.

## 3. Tenant-Aware Queues

Queue jobs carry resolved tenant context in their payload (per [tenant-context-identification-and-propagation.md, Section 3](tenant-context-identification-and-propagation.md#3-tenant-context-propagation)), re-validated at execution time per [../08-workflows/queue-routing-priority-retry-and-failure-architecture.md, Section 3](../08-workflows/queue-routing-priority-retry-and-failure-architecture.md#3-retry-and-backoff). Tenant fairness within a shared queue is addressed in [noisy-neighbor-fair-use-and-resource-governance.md, Section 3](noisy-neighbor-fair-use-and-resource-governance.md#3-queue-fairness-and-tenant-workload-prioritization).

## 4. Tenant-Aware Notifications

Recipient resolution (per [../08-workflows/notification-and-recipient-resolution-architecture.md, Section 2](../08-workflows/notification-and-recipient-resolution-architecture.md#2-recipient-resolution)) is always scoped to a recipient's own tenant membership — a notification never crosses tenant boundaries unintentionally, and any legitimate cross-tenant notification (e.g., a platform-wide maintenance announcement) is explicitly marked as platform-originated, never presented as a tenant-specific notice.

## 5. Tenant-Aware Real-Time Channels

Restated from [../08-workflows/realtime-broadcast-and-reverb-message-architecture.md, Section 2](../08-workflows/realtime-broadcast-and-reverb-message-architecture.md#2-channel-taxonomy-restated-and-extended): every non-public Reverb channel's authorization already resolves scope from trusted server-side assignment data — a tenant dimension is a further authorization input, never a separate mechanism. A public channel (Section 4 of the same document) draws only from approved public projections, which are themselves tenant-scoped where their source data is tenant-owned.

## 6. Tenant-Aware Mobile Synchronization

Mobile sync (per [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md)) resolves tenant context from the authenticated device/user session at sync time — a device's local dataset is scoped to exactly the tenant(s) its bound operator is authorized for, never a broader cache. Full scaling detail: [public-portal-api-mobile-sync-and-device-scale.md, Section 3](public-portal-api-mobile-sync-and-device-scale.md#4-mobile-synchronization-scaling).

## 7. Tenant-Aware Auditing

Every audit record (per [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md)) carries its tenant context explicitly — restated as a direct extension, not a redefinition, of the existing 27 audit-event categories. Tenant-scoped audit export is addressed in [enterprise-security-compliance-audit-and-data-residency-readiness.md, Section 2](enterprise-security-compliance-audit-and-data-residency-readiness.md#2-enterprise-audit-exports).

## 8. Tenant-Aware Observability

Logs and metrics are tagged with tenant context where the underlying data is tenant-owned — supporting tenant-specific operational dashboards (a candidate future capability for tenant administrators, per [tenant-metering-licensing-subscription-and-billing-readiness.md](tenant-metering-licensing-subscription-and-billing-readiness.md)) without exposing one tenant's operational data to another.

## 9. Tenant-Aware Analytics

Restated absolutely from [reporting-search-analytics-and-data-platform-readiness.md, Section 4](reporting-search-analytics-and-data-platform-readiness.md#4-cross-tenant-analytics): analytics are tenant-scoped by default; cross-tenant analytics require the explicit governance in that section — approved purpose, de-identification or appropriate aggregation, controlled access, audited output.

## 10. Tenant-Aware Object Storage and Search (Cross-Reference)

Full detail: [minio-object-storage-media-and-delivery-scaling.md, Section 3](minio-object-storage-media-and-delivery-scaling.md#3-tenant-aware-object-keys-and-metadata) and [reporting-search-analytics-and-data-platform-readiness.md, Section 3](reporting-search-analytics-and-data-platform-readiness.md#2-search-scale).

## 11. Tenant-Aware AI

Extends [../07-ai/ai-identity-authorization-scope-and-audit.md, Section 1](../07-ai/ai-identity-authorization-scope-and-audit.md#1-requesting-user-and-service-identity)'s intersection-not-union access model with a tenant dimension: an AI request's effective access is the intersection of the requesting user's tenant-scoped authorization, the AI service identity's own restricted scope, **and** the resolved tenant context — never broader than any one of the three. No AI capability is approved for implementation (restated unchanged from Phase 0.10); this section only ensures that whenever a capability is eventually approved, it inherits tenant isolation from day one rather than requiring retrofit.

## 12. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-11 (whether tenant-specific AI provider/model selection is ever supported, versus one platform-wide provider decision applying to all tenants).
