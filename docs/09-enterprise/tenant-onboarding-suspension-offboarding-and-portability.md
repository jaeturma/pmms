# PMMS Tenant Onboarding, Suspension, Offboarding, and Portability

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../05-devops/tenant-onboarding-offboarding-and-data-portability.md](../05-devops/tenant-onboarding-offboarding-and-data-portability.md) (Phase 0.8 — operational readiness) · [tenant-data-ownership-and-isolation-architecture.md](tenant-data-ownership-and-isolation-architecture.md) (Phase 0.12 — technical architecture)

**This document adds tenant-suspension architecture (not covered by the existing Phase 0.8 document) and adds the technical-architecture layer beneath the existing operational onboarding/offboarding/portability checklists — it does not redefine or duplicate them, per working rule 4.**

---

## 1. Tenant Onboarding (Architecture Layer)

The existing operational checklist ([../05-devops/tenant-onboarding-offboarding-and-data-portability.md, Section 2](../05-devops/tenant-onboarding-offboarding-and-data-portability.md#2-tenant-onboarding-readiness): organization onboarding, branding, domains, configuration, initial administrators, reference data, feature enablement, quotas, monitoring tags, backup scope, support ownership) is unchanged. This document adds the technical sequence beneath it:

```text
Commercial or Governance Approval → Tenant Record Creation (Platform-Owned) →
Primary Organization Creation (BC-03) → Tenant Identifier Assignment →
Tenant Context Resolution Enabled → Initial Administrator Provisioning →
Configuration/Branding/Entitlement/Quota Assignment (per tenant-configuration-branding-entitlement-and-quota-architecture.md) →
Reference-Data Seeding (Platform-Owned Shared Data Only) → Security Review →
Training → Acceptance → Activation
```

Every step is an authorized, audited platform-administration action — restated from [tenant-identity-authorization-and-administration.md, Section 4](tenant-identity-authorization-and-administration.md#4-platform-administration).

## 2. Tenant Suspension (New in Phase 0.12)

| Element | Direction |
|---|---|
| Reason | Non-payment (once billing exists), security concern, contractual breach, or governance decision |
| Authority | Platform administration, always with a documented reason |
| Impact on login | Tenant users cannot authenticate into an active session; existing sessions are terminated |
| Public content | Remains published (a suspension is not a data-deletion event) unless a specific security concern requires immediate unpublication |
| Data access | Blocked for ordinary tenant users; platform administration retains access for resolution purposes only, per the same restrictions as Section 4, [tenant-identity-authorization-and-administration.md](tenant-identity-authorization-and-administration.md#4-platform-administration) |
| Scheduled workflows | Paused — no automation entry (per [../08-workflows/responsible-automation-and-authority-boundaries.md](../08-workflows/responsible-automation-and-authority-boundaries.md)) executes against a suspended tenant's data |
| Notifications | Suspended, except the suspension notice itself and any mandatory security notice |
| Integrations | Suspended |
| Billing | Suspension does not itself resolve a billing dispute — a separate commercial process, not defined in this phase |
| Retention | Unaffected — suspension is reversible and never triggers deletion |
| Support | A defined escalation path for a tenant to resolve the underlying suspension cause |
| Restoration | Requires the same authority that imposed suspension, or an escalated approval, always audited |
| Audit | Every suspension and restoration event is fully audited |

**Suspension must not delete tenant data** — restated absolutely as this section's governing rule.

## 3. Tenant Offboarding (Architecture Layer)

The existing operational checklist ([../05-devops/tenant-onboarding-offboarding-and-data-portability.md, Section 3](../05-devops/tenant-onboarding-offboarding-and-data-portability.md#3-tenant-offboarding-readiness): access revocation, export, archive, retention, deletion review, credential revocation, domain removal, backup handling, evidence) is unchanged. This document adds: the technical isolation verification step (confirming no residual cross-tenant reference exists before archival) and the deletion-review gate (Section 5).

## 4. Tenant Portability

Export categories: master data · people and participant records · meet data · results · documents · audit records where permitted · configuration · reports · public content · integration metadata.

**Portability must respect privacy, security, records, third-party rights, and file integrity** — restated absolutely. This extends [../05-devops/tenant-onboarding-offboarding-and-data-portability.md, Section 4](../05-devops/tenant-onboarding-offboarding-and-data-portability.md#4-data-portability-operations) ("a candidate future capability... not yet implemented; readiness only") with the specific export-category list above; the operational *process* for triggering a portability export remains owned by that Phase 0.8 document.

## 5. Tenant Deletion Review

**Data deletion, where a tenant offboarding process ultimately requires it, follows the same correction-supersedes-never-destructively-overwrites discipline as any other high-integrity domain** for records that fall within a high-integrity classification (per [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)) — restated absolutely; a tenant offboarding request does not bypass Phase 0.5's soft-deletion prohibition for high-integrity records. A deletion-review step requires: retention-obligation check (per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md)) · legal-hold check · security sign-off · final export confirmation · irreversibility warning · elevated authorization.

## 6. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-14 (tenant-suspension grace-period duration) and the existing [DV-20](../05-devops/devops-open-decisions.md#dv-20--multi-organization-support-adoption-cross-reference) / [DV-22](../05-devops/devops-open-decisions.md#dv-22--meet-closure-checklist-ownership-and-cadence) cross-references, both still open.
