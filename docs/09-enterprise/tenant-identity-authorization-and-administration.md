# PMMS Tenant Identity, Authorization, and Administration

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/phase-0.3-access-and-assignment-architecture.md](../01-architecture/phase-0.3-access-and-assignment-architecture.md) · [../03-security/authorization-and-privileged-access-assurance.md](../03-security/authorization-and-privileged-access-assurance.md)

---

## 1. Tenant-Aware Authentication and Authorization

Tenant context (per [tenant-context-identification-and-propagation.md](tenant-context-identification-and-propagation.md)) is an additional input to the existing 16-step authorization decision sequence — restated absolutely, it never substitutes for role/permission/scope/assignment authorization already established in Phase 0.3. The authority formula extends to: `Authenticated Identity + Active Account + Tenant Membership + Role + Permission + Scope + Valid Assignment + Resource State + Separation-of-Duties Rules + Data Classification + Time Validity + Device Trust + Explicit Restrictions`.

## 2. Tenant-Aware Assignments

A role assignment (per [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md)) is scoped within exactly one tenant unless explicitly designated cross-tenant (Section 3) — an assignment never implicitly grants authority across tenant boundaries.

## 3. Cross-Tenant Users

Candidate cross-tenant user categories: platform administrators · regional or central administrators · service providers · auditors · shared technical officials · users serving multiple organizations.

Requirements: explicit tenant memberships (never implicit) · explicit tenant selection (the user actively chooses which tenant context they operate in) · clear UI context (restated from Phase 0.9's "make authority visible" principle) · least privilege · time-valid assignments · cross-tenant action warnings · audit · **no accidental combined results** — a query or report must never silently merge data across a user's multiple tenant memberships without an explicit, deliberate cross-tenant action.

## 4. Platform Administration

Platform administration manages: tenant lifecycle · platform configuration · verified shared references · feature entitlements · service health · commercial plans · security administration where approved.

**Platform administrators must not automatically receive unrestricted access to protected tenant data** — restated absolutely per working rule 38. A platform administrator's *platform-level* authority (managing tenant lifecycle, entitlements, configuration) is distinct from, and does not imply, authority to view a specific tenant's medical, eligibility, finance, or audit records — that access, if ever needed, follows Section 5's support-access model.

## 5. Tenant Support Access

**Tenant support access must follow the support and impersonation controls from Phase 0.6** — restated absolutely per working rule 40, extending [../03-security/authorization-and-privileged-access-assurance.md, Sections 5–6](../03-security/authorization-and-privileged-access-assurance.md#5-support-impersonation-governance) unchanged into the multi-tenant context. **Cross-tenant access must be explicit, time-limited where appropriate, justified, and audited** — restated absolutely per working rule 39.

## 6. Tenant Impersonation Restrictions

Restated absolutely, unchanged from [SOD-11](../01-architecture/separation-of-duties-matrix.md): an active impersonation session — cross-tenant or within a tenant — may never execute an approval/certification/publication action. This applies identically whether the impersonating actor is a tenant administrator or a platform administrator.

## 7. Shared-Service Accounts

A service identity (automation, AI, integration) that legitimately operates across all tenants (e.g., a platform-level scheduled maintenance task) is explicitly distinguished from a tenant-scoped service identity — restated from [../08-workflows/workflow-identity-authorization-scope-and-separation-of-duties.md, Section 7](../08-workflows/workflow-identity-authorization-scope-and-separation-of-duties.md#7-service-identities). A shared-service account never holds standing write access to tenant-owned business data; it operates only on platform-owned shared references or through the ordinary Command Architecture with tenant context resolved per request.

## 8. Tenant Administration

Tenant administrators may manage only approved tenant-level functions: organization configuration · tenant users · branding · meets · permitted integrations · tenant reports · quotas (view, not necessarily raise) · data exports · support requests.

**They must not override platform security or system invariants** — restated absolutely, consistent with working rule 45's configuration-override prohibition.

## 9. Delegated Administration

A tenant administrator may delegate a subset of tenant-level administration (e.g., organization-level configuration to a school/division administrator) within the existing role/assignment model — restated as an application of Phase 0.3's existing scope hierarchy, not a new authorization mechanism.

## 10. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-10 (whether a dedicated cross-tenant auditor role is formally cataloged in a future Phase 0.3 revision).
