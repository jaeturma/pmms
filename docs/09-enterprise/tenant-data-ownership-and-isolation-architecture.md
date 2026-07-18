# PMMS Tenant Data Ownership and Isolation Architecture

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../02-data/data-open-decisions.md, PD-01](../02-data/data-open-decisions.md#pd-01--tenant-column-timing) · [../01-architecture/domain-open-decisions.md, DD-21](../01-architecture/domain-open-decisions.md#dd-21--tenant-boundaries)

---

## 1. Tenant-Isolation Strategy Options

### Shared Database and Shared Schema (Tenant Key on Tenant-Owned Records)

**Benefits:** operational simplicity, lower infrastructure cost, easier reporting and upgrades.
**Risks:** greater consequence of a missing tenant filter, noisy-neighbor risk, complex large-tenant extraction.

### Shared Database with Separate Schemas

MySQL supports multiple schemas within one server instance, but per-tenant schema management (migrations applied N times, connection routing per schema) adds operational complexity disproportionate to PMMS's current evidence base.

### Database per Tenant

**Benefits:** stronger isolation, easier tenant-level restore, custom residency possibilities.
**Risks:** migration complexity, connection management, operational cost, cross-tenant analytics complexity.

### Hybrid Isolation

Shared deployment for smaller tenants, dedicated deployment for large or regulated tenants — a future option once Stage 5 (Enterprise Platform, per [enterprise-readiness-vision-principles-and-maturity-model.md, Section 3](enterprise-readiness-vision-principles-and-maturity-model.md#3-enterprise-maturity-model)) is reached.

## 2. Recommended Initial Direction

> Use one MySQL database with explicit tenant ownership and defense-in-depth logical isolation for initial multi-organization readiness, while preserving tenant extraction and future dedicated-deployment options.

This is consistent with, and does not override, [Phase 0.5 PD-01](../02-data/data-open-decisions.md#pd-01--tenant-column-timing)'s recommended direction (a nullable `organization_id` column from day one) and [Phase 0.2 DD-21](../01-architecture/domain-open-decisions.md#dd-21--tenant-boundaries)'s recommended direction (design organization-scoping in from the start, at low cost).

## 3. Defense-in-Depth Tenant Isolation

**Do not rely on one global ORM scope alone** — restated absolutely per working rule 41. Controls layer as follows:

| Layer | Control |
|---|---|
| Application | Tenant-aware use cases — every Application-layer command/query handler explicitly resolves and applies tenant scope, never assumes a global default scope silently filters correctly |
| Repository/query | Tenant-scoped repositories or queries — every query builder call includes an explicit tenant condition, defense-in-depth beyond any framework-level global scope |
| Database | Constraints where possible — composite uniqueness including tenant (e.g., a public identifier unique per tenant, not globally, where appropriate) |
| Cache | Tenant-aware cache keys, per [redis-cache-session-lock-and-rate-limit-scaling.md, Section 3](redis-cache-session-lock-and-rate-limit-scaling.md#4-cache-key-rules) |
| Object storage | Tenant-aware object keys and metadata, per [minio-object-storage-media-and-delivery-scaling.md, Section 3](minio-object-storage-media-and-delivery-scaling.md#3-tenant-aware-object-keys-and-metadata) |
| Queue | Tenant-aware queue payloads, re-validated at execution time |
| Events | Tenant-aware events, carrying tenant context in metadata |
| Broadcasts | Tenant-aware Reverb channels, never a shared channel exposing cross-tenant data |
| Search | Tenant-aware search indexes and queries |
| Exports | Tenant-aware exports — an export job is scoped to exactly one tenant's data, never a cross-tenant bulk pull without explicit platform-administration authority |
| Audit | Tenant-aware audit records |
| Testing | Cross-tenant isolation tests — a dedicated test category verifying tenant A's request can never observe tenant B's data, per [enterprise-testing-benchmarking-and-readiness-gates.md](enterprise-testing-benchmarking-and-readiness-gates.md) |
| Support | Support-access review — every support action touching tenant data is reviewed against Phase 0.6's support/impersonation controls |

## 4. Anti-Patterns (Explicitly Prohibited)

**Do not duplicate tenant ownership inconsistently across tables** — every tenant-owned table's tenant key follows the same naming and constraint convention.

**Do not place all tenant-specific configuration in unstructured JSON** — restated absolutely per working rule 43; configuration structure follows [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 1](tenant-configuration-branding-entitlement-and-quota-architecture.md#1-configuration-classification).

**Do not assume every record is tenant-owned** — restated per working rule 44; platform-owned shared reference data (Section 4 of [multi-tenant-product-and-organization-model.md](multi-tenant-product-and-organization-model.md)) carries no tenant key at all.

**Do not permit tenant-specific configuration to override core security or high-integrity rules** — restated absolutely per working rule 45; a tenant can configure its branding, quotas, and feature entitlements, never its separation-of-duties rules, audit requirements, or high-integrity state-machine invariants.

**Multi-tenancy must not weaken bounded contexts** — restated absolutely per working rule 46; tenant isolation is a cross-cutting concern layered onto Phase 0.2's existing context boundaries, never a reorganization of those boundaries.

## 5. Logical, Schema, Database, and Storage Isolation Levels

| Isolation Level | Current Direction | Future Option |
|---|---|---|
| Logical (tenant key + defense-in-depth) | **Recommended initial direction** (Section 2) | Baseline for all stages |
| Schema | Not adopted — operational complexity disproportionate to current evidence | Evaluated only if a specific driver emerges |
| Database-per-tenant | Not adopted initially | Candidate for Stage 5 large/regulated tenants (Section 1) |
| Storage (MinIO bucket/prefix) | Tenant-aware object-key convention (logical isolation within shared storage) | Dedicated bucket or storage account per tenant, evaluated at Stage 5 |
| Encryption-key isolation | Shared key-management approach initially, per [../03-security/cryptography-key-and-secret-management.md](../03-security/cryptography-key-and-secret-management.md) — no algorithm/product selected | Per-tenant key readiness evaluated for regulated/dedicated tenants at Stage 5 |

## 6. Tenant Migration, Sharding, and Extraction Readiness

| Concern | Direction |
|---|---|
| Tenant migration | Moving a tenant between deployment models (shared → dedicated) is a candidate future operation, requiring the extraction readiness below |
| Tenant sharding readiness | No sharding concept exists anywhere in PMMS's architecture yet — restated from [../02-data/indexing-performance-and-capacity.md](../02-data/indexing-performance-and-capacity.md), which defines only partitioning (time-based), not sharding; tenant-key-based sharding is a Stage 6 candidate only, evaluated per [enterprise-open-decisions.md](enterprise-open-decisions.md) |
| Tenant extraction readiness | For future dedicated deployment: tenant-ownership completeness (every table's tenant key populated and enforced) · cross-tenant reference handling · shared-reference duplication-or-linking decision · object migration · identifier continuity · audit continuity · workflow-state migration · event migration · integration migration · encryption · validation · cutover plan · rollback/reconciliation plan |
| Tenant portability | Full detail: [tenant-onboarding-suspension-offboarding-and-portability.md, Section 4](tenant-onboarding-suspension-offboarding-and-portability.md#4-tenant-portability) |

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-08 (whether database-per-tenant is ever adopted for regulated/large tenants) and ED-09 (per-tenant encryption-key readiness timing).
