# PMMS Tenant Configuration, Branding, Entitlement, and Quota Architecture

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../01-architecture/environment-and-configuration-model.md, Section 2](../01-architecture/environment-and-configuration-model.md#2-configuration-categories) · [tenant-data-ownership-and-isolation-architecture.md](tenant-data-ownership-and-isolation-architecture.md)

---

## 1. Configuration Classification

Configuration is classified as: platform fixed · platform default · tenant configurable · organization configurable · meet configurable · user preference · feature flag · commercial entitlement.

Each configuration item requires: owner · data type · validation · allowed scope · default · classification · audit · versioning · inheritance · override rules.

**Do not place all tenant-specific configuration in unstructured JSON** — restated absolutely per working rule 43; each configuration category above has its own structured representation, not a single opaque blob. **Core domain rules are never placed in freely editable configuration** — restated from [../08-workflows/workflow-versioning-migration-and-active-instance-compatibility.md, Section 4](../08-workflows/workflow-versioning-migration-and-active-instance-compatibility.md#4-workflow-configuration).

## 2. Configuration Inheritance

`Platform Fixed → Platform Default → Tenant Configurable → Organization Configurable → Meet Configurable → User Preference` — a lower level may override a higher level's *default* only where the higher level explicitly permits an override; a Platform Fixed item is never overridable at any level.

## 3. Tenant Branding

Candidate tenant-branding elements: display name · logo · color accents (layered on the confirmed PMMS Arena token foundation, per [../06-design/color-theme-and-surface-system.md](../06-design/color-theme-and-surface-system.md) — never replacing it) · public-portal header/footer content · email/notification branding.

**Governance:** branding changes require tenant-administrator authority (Section, [tenant-identity-authorization-and-administration.md, Section 8](tenant-identity-authorization-and-administration.md#8-tenant-administration)) and must never override PMMS's high-integrity state-visibility tokens (provisional/certified/published/held/offline/conflict) or accessibility-contrast requirements established in Phase 0.9.

## 4. White-Label and Custom-Domain Readiness

A candidate future capability (Stage 5, per [enterprise-readiness-vision-principles-and-maturity-model.md, Section 3](enterprise-readiness-vision-principles-and-maturity-model.md#3-enterprise-maturity-model)) — a tenant-specific custom domain requires trusted domain-to-tenant mapping (feeding tenant context resolution, per [tenant-context-identification-and-propagation.md, Section 1](tenant-context-identification-and-propagation.md#1-tenant-identification-sources)), TLS-certificate management, and DNS governance, none of which is provisioned or implemented in this phase.

## 5. Feature Entitlements

Candidate entitlements: number of active meets · number of users · number of athletes · file storage · AI capabilities (none active, per Phase 0.10) · mobile access · public portal · custom domain · custom branding · integrations · advanced analytics · support tier · dedicated deployment.

**Entitlements must not replace authorization** — restated absolutely as this section's governing rule; an entitlement determines whether a *capability is available to a tenant at all*, while Phase 0.3's authorization model determines whether a *specific user* may use it. A user without the relevant permission cannot use a feature even if their tenant is entitled to it.

## 6. Tenant Quotas

Candidate quotas: users · active meets · participants · storage · reports · exports · API requests · notifications · Reverb connections · AI usage (none active) · devices.

| Field | Definition |
|---|---|
| Soft limit | A warning threshold, action still permitted |
| Hard limit | An enforced ceiling, action blocked |
| Warning | Notification to the tenant administrator as a soft limit approaches |
| Enforcement | The specific blocking behavior at hard limit (per [noisy-neighbor-fair-use-and-resource-governance.md](noisy-neighbor-fair-use-and-resource-governance.md)) |
| Override | Requires platform-administration approval, always audited |
| Grace period | A candidate transitional allowance before hard enforcement, particularly at meet-day-critical moments |
| Audit | Every quota-limit event (warning, block, override) is audited |
| Support process | An escalation path for a tenant needing a legitimate quota increase |

**No specific quota numeric value is invented** — every quota's numeric value is a placeholder pending pilot evidence, consistent with working rule 17.

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-12 (specific quota numeric values per tier, deferred pending evidence) and ED-13 (custom-domain/white-label adoption timing).
