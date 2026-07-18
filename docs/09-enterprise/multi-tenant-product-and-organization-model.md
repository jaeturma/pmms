# PMMS Multi-Tenant Product and Organization Model

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) (BC-03 Organization Directory) · [../01-architecture/domain-open-decisions.md, DD-21](../01-architecture/domain-open-decisions.md#dd-21--tenant-boundaries)

---

## 1. Terminology (Do Not Conflate)

| Term | Definition |
|---|---|
| Platform | The PMMS product and shared operating environment |
| Tenant | An organization or contractual customer whose data, users, configuration, features, and operations require isolation |
| Organization | A DepEd or other organizational unit represented in the domain hierarchy, owned by BC-03 (Organization Directory) |
| Meet | A tenant-owned or jointly governed sports event |
| Platform-owned shared data | Verified shared references controlled by the platform (e.g., approved sport definitions, status vocabularies) |
| Tenant-owned data | Records created, governed, or retained for a specific tenant |

**Do not assume every organization row is automatically a tenant** — restated as this section's governing rule. A region, division, district, or school within BC-03's hierarchy is an *organization*; a *tenant* is the isolation boundary a specific DepEd division/region or a future external customer represents. At PMMS's current single-organization scope (per [OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)), the entire DepEd organization hierarchy is one tenant.

## 2. Tenant Hierarchy (Candidate, Not Finalized)

```text
Platform
→ Tenant
→ Organization Hierarchy (BC-03: region → division → district → school)
→ Meet
→ Committee, Delegation, Sport, Venue, and Operational Scope
```

## 3. Open Structural Questions (Not Finalized Without Product Validation)

| Question | Candidate Answer | Status |
|---|---|---|
| May one tenant contain multiple organizations? | Yes — a tenant is expected to span an entire DepEd region/division's organization hierarchy, not a single school | Requires product validation |
| May one tenant run multiple meets? | Yes — restated from [OD-03](../00-product/open-decisions.md#od-03--single-meet-versus-multi-meet-launch), contingent on that decision | Open, contingent on OD-03 |
| May a meet involve organizations from multiple tenants? | A candidate future scenario (e.g., a regional meet spanning multiple divisions) — not assumed at initial scope | Requires product validation |
| May a user belong to multiple tenants? | A candidate scenario for cross-tenant users (per [tenant-identity-authorization-and-administration.md, Section 3](tenant-identity-authorization-and-administration.md#3-cross-tenant-users)) | Requires product validation |
| May platform services serve all tenants? | Yes for platform-owned shared reference data only (Section 4); never for tenant-owned data | Confirmed by architecture, not product-validated |
| Do shared events require controlled cross-tenant collaboration? | A candidate future capability — not designed in this phase beyond acknowledging the need | Requires product validation |

## 4. Data-Ownership Classification

Every major data concept is classified as one of: Platform-owned · Tenant-owned · Organization-owned · Meet-owned · Shared reference · Derived projection · Cross-tenant collaboration record.

This extends [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md) with a tenant dimension layered on top of its existing bounded-context ownership — **it does not change which bounded context owns a data concept**, only adds which tenant(s), if any, a specific record within that context belongs to.

| Classification | Example | Isolation Requirement |
|---|---|---|
| Platform-owned | Approved sport/event definitions (BC-34, pending [DD-22](../01-architecture/domain-open-decisions.md)'s scope resolution), platform configuration | None — visible to all tenants as a verified reference |
| Tenant-owned | Organization hierarchy (BC-03), meets, delegations, athletes, results | Full tenant isolation required |
| Organization-owned | A specific school/district's roster data within a tenant's hierarchy | Tenant isolation plus internal organization-scope authorization (Phase 0.3) |
| Meet-owned | Meet-specific configuration, schedules, entries | Tenant isolation plus meet-scope authorization |
| Shared reference | Sports Catalog defaults, terminology, status vocabularies | Platform-controlled, read-only to tenants |
| Derived projection | Public portal projections, reporting read models | Rebuildable, tenant-scoped where the source is tenant-owned |
| Cross-tenant collaboration record | A future joint-meet record spanning tenants (not designed in this phase) | Requires explicit, audited, time-limited access per [tenant-identity-authorization-and-administration.md, Section 3](tenant-identity-authorization-and-administration.md#3-cross-tenant-users) |

## 5. Relationship to BC-03 (Organization Directory)

BC-03 remains the sole authoritative owner of organization-hierarchy data (regions, divisions, districts, schools, partner orgs), unchanged from Phase 0.2 — restated absolutely per working rule 19. This document adds the tenant-isolation dimension on top of BC-03's existing ownership, never redefines it.

## 6. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-05 (whether one tenant may ever span multiple organizations' hierarchies versus one tenant per top-level organization) and ED-06 (cross-tenant meet collaboration model).
