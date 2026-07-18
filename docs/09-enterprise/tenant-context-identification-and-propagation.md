# PMMS Tenant Context, Identification, and Propagation

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md) · [multi-tenant-product-and-organization-model.md](multi-tenant-product-and-organization-model.md)

---

## 1. Tenant Identification Sources

Tenant context is resolved only from trusted server-side information:

Authenticated account membership · selected tenant membership · trusted domain mapping · approved API client · device assignment · service identity · meet ownership · explicit platform-administration workflow.

**Tenant identifiers must not be trusted solely from client input** — restated absolutely per working rule 37. A request header, query parameter, or client-supplied tenant ID is never sufficient on its own; the server resolves tenant context from the authenticated actor's own trusted membership record.

## 2. Tenant Context Resolution Sequence (Conceptual)

```text
Authenticated Identity → Trusted Tenant Membership Lookup →
(Selected Tenant, if the account holds more than one) →
Resolved Tenant Context → Authorization Decision Sequence (per authorization-decision-model.md)
```

This resolution occurs *before* the existing 16-step authorization decision sequence — tenant context is an input to authorization, never a downstream consequence of it.

## 3. Tenant Context Propagation

Tenant context must propagate through every surface, never resolved independently or inconsistently per surface:

| Surface | Propagation Mechanism |
|---|---|
| HTTP requests | Resolved server-side per request from the authenticated session, never re-derived per controller inconsistently |
| Inertia pages | Carried in the shared, server-populated Inertia props — never re-fetched client-side from an untrusted source |
| APIs | Resolved per the same authentication mechanism as the API category (per [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md)) |
| Flutter requests | Resolved from the authenticated device/user session, never a client-supplied tenant field |
| Queue jobs | Carried explicitly in the job payload as a resolved value at enqueue time, re-validated at execution time (per [../08-workflows/queue-routing-priority-retry-and-failure-architecture.md, Section 3](../08-workflows/queue-routing-priority-retry-and-failure-architecture.md#3-retry-and-backoff)) |
| Events | Carried in event metadata, per [../08-workflows/event-metadata-versioning-ordering-and-correlation.md, Section 1](../08-workflows/event-metadata-versioning-ordering-and-correlation.md#1-event-metadata-conceptual) — a candidate new `tenant_id` metadata field |
| Notifications | Resolved from the recipient's own tenant membership at dispatch time, per [../08-workflows/notification-and-recipient-resolution-architecture.md, Section 2](../08-workflows/notification-and-recipient-resolution-architecture.md#2-recipient-resolution) |
| Reverb channels | Encoded in the channel name/authorization callback, never inferred from broadcast payload alone |
| Scheduler tasks | Iterate explicitly over known tenants, never assume a single implicit tenant |
| AI requests | Inherits the requesting user's resolved tenant context as part of the intersection-not-union access model, per [../07-ai/ai-identity-authorization-scope-and-audit.md, Section 1](../07-ai/ai-identity-authorization-scope-and-audit.md#1-requesting-user-and-service-identity) |
| Object-storage access | Tenant-aware object keys/metadata, per [minio-object-storage-media-and-delivery-scaling.md, Section 3](minio-object-storage-media-and-delivery-scaling.md#3-tenant-aware-object-keys-and-metadata) |
| Cache keys | Tenant segment included explicitly, per [redis-cache-session-lock-and-rate-limit-scaling.md, Section 3](redis-cache-session-lock-and-rate-limit-scaling.md#4-cache-key-rules) |
| Search queries | Tenant-scoped filter applied server-side, per [reporting-search-analytics-and-data-platform-readiness.md, Section 3](reporting-search-analytics-and-data-platform-readiness.md#2-search-scale) |
| Audit events | Recorded explicitly, never inferred after the fact |
| Logs and metrics | Tagged with tenant context where the underlying data is tenant-owned, supporting tenant-aware observability |

## 4. Context Loss Fails Safe

**Context loss must fail safely** — restated absolutely as this document's governing rule. A request, job, or event that cannot resolve a trusted tenant context is rejected or routed to an explicit manual-review path — never defaulted to "no tenant filter" (which would leak cross-tenant data) or to a guessed tenant (which would misattribute the action).

## 5. Tenant-Aware Authentication and Authorization (Cross-Reference)

Full detail: [tenant-identity-authorization-and-administration.md](tenant-identity-authorization-and-administration.md). Tenant context is resolved before, and is an input to, every authorization decision — it never substitutes for role/scope/assignment authorization, restated from [../01-architecture/authorization-decision-model.md](../01-architecture/authorization-decision-model.md).

## 6. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-07 (the specific mechanism for tenant selection when an account holds multiple tenant memberships).
