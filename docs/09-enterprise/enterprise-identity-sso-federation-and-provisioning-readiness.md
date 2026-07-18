# PMMS Enterprise Identity, SSO, Federation, and Provisioning Readiness

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../03-security/identity-authentication-and-session-security.md](../03-security/identity-authentication-and-session-security.md) · [tenant-identity-authorization-and-administration.md](tenant-identity-authorization-and-administration.md)

**No SSO integration, identity-provider connection, or provisioning code is created here.**

---

## 1. Single Sign-On Readiness

A future tenant may require federated authentication via its own identity provider (e.g., a DepEd division's existing directory, or a future commercial customer's corporate IdP) — evaluated as a Stage 5 (Enterprise Platform) candidate. No SSO protocol implementation (SAML, OAuth2/OIDC) is selected or integrated; the existing Laravel Fortify-based authentication (confirmed in the repository) remains the current, sole authentication mechanism.

## 2. Identity-Provider Federation

Readiness requires: a trusted mapping between an external IdP's asserted identity and a PMMS User Account (never auto-provisioning based on an unverified claim) · tenant-scoped federation (an IdP connection belongs to exactly one tenant, never shared across tenants) · fallback authentication for users not covered by federation · federation-failure behavior (never silently locking out users when an IdP is unreachable).

## 3. Directory Synchronization Readiness

A candidate future capability for syncing user/organization data from an external directory (e.g., a DepEd HR system) — evaluated only alongside [OD-25](../00-product/open-decisions.md#od-25--integration-requirements) (Integration Requirements), which currently recommends no integrations at launch. Directory sync would feed BC-03 (Organization Directory) and BC-02 (Identity and Access) as an external source, never bypassing their existing authoritative-ownership model.

## 4. MFA Readiness

Multi-factor authentication readiness (already partially scaffolded via Laravel Fortify in the confirmed repository baseline) extends unchanged into the enterprise context — an enterprise tenant may require mandatory MFA as a contractual/entitlement condition (per [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 5](tenant-configuration-branding-entitlement-and-quota-architecture.md#5-feature-entitlements)), evaluated, not implemented.

## 5. Enterprise Provisioning and SCIM Evaluation

Enterprise user provisioning (bulk account creation/deprovisioning driven by an external system) is evaluated against the SCIM (System for Cross-domain Identity Management) standard as a candidate future protocol — no SCIM endpoint is built. Provisioning must respect the same tenant-isolation and authorization rules as manual account creation; an external provisioning system never bypasses Phase 0.3's role/scope/assignment model.

## 6. Enterprise Access Reviews

Extends [../03-security/access-review-production-and-support-controls.md](../03-security/access-review-production-and-support-controls.md) unchanged into the multi-tenant context — a periodic access review, where a tenant is entitled to request one, is scoped to exactly that tenant's users and never exposes another tenant's access data.

## 7. Enterprise Policy Configuration

A tenant may configure enterprise-specific policy parameters (e.g., password complexity within platform-approved bounds, session-timeout duration within platform-approved bounds) — restated from [tenant-configuration-branding-entitlement-and-quota-architecture.md, Section 1](tenant-configuration-branding-entitlement-and-quota-architecture.md#1-configuration-classification)'s inheritance model: a tenant may tighten a security policy within platform bounds, never loosen it below the platform's minimum, consistent with working rule 45.

## 8. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-38 (SSO protocol selection, contingent on Stage 5 adoption) and ED-39 (SCIM adoption evaluation timing).
