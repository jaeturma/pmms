# PMMS Tenant Onboarding, Offboarding, and Data Portability

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../00-product/open-decisions.md, OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization) · [../02-data/logical-data-architecture.md, Section 4](../02-data/logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles) · [../02-data/retention-archival-and-disposal.md, Section 3](../02-data/retention-archival-and-disposal.md#3-meet-closure-and-historical-preservation)

This document defines operational readiness for multi-organization operation, tenant onboarding/offboarding, data-portability operations, archival operations, and meet-closure operations. **PMMS currently operates at single-organization scope** — this document establishes operational readiness, not a commitment to multi-organization operation, per [../00-product/open-decisions.md, OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization).

---

## 1. Multi-Organization Operations Readiness

Restated from [../02-data/logical-data-architecture.md, Section 4](../02-data/logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles) — multi-organization readiness is a **logical property**, not a launch requirement. This document's onboarding/offboarding procedures below are written to be ready if/when OD-02 resolves toward multi-organization support, not because that support currently exists.

## 2. Tenant Onboarding (Readiness)

| Element | Direction |
|---|---|
| Organization onboarding | A formal, approved process — never self-service without governance |
| Branding | Organization-specific visual identity, where the product design supports it |
| Domains | A dedicated subdomain or domain per organization, per [network-reverse-proxy-tls-and-domain-architecture.md, Section 2](network-reverse-proxy-tls-and-domain-architecture.md#2-domain-and-tls-architecture) |
| Configuration | Organization-specific configuration values, per [configuration-feature-flag-and-secret-management.md](configuration-feature-flag-and-secret-management.md) |
| Initial administrators | Provisioned per [device-service-account-and-credential-operations.md, Section 4](device-service-account-and-credential-operations.md#4-user-provisioning-operations) |
| Reference data | Organization-specific reference data (per [../02-data/test-seed-and-reference-data-strategy.md](../02-data/test-seed-and-reference-data-strategy.md)) established at onboarding |
| Feature enablement | Per [configuration-feature-flag-and-secret-management.md, Section 4](configuration-feature-flag-and-secret-management.md#4-feature-flag-architecture) |
| Quotas | Capacity/usage limits, informed by [service-level-capacity-and-performance-management.md](service-level-capacity-and-performance-management.md) |
| Monitoring tags | Organization-scoped observability labeling, per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md) |
| Backup scope | Confirmed the organization's data is included in the backup coverage, per [backup-restore-disaster-recovery-and-continuity.md](backup-restore-disaster-recovery-and-continuity.md) |
| Support ownership | A named support-tier owner for the new organization, per [production-support-access-and-data-repair-operations.md, Section 3](production-support-access-and-data-repair-operations.md#3-support-model) |

## 3. Tenant Offboarding (Readiness)

| Element | Direction |
|---|---|
| Access revocation | Immediate, per [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md) |
| Export | Per Section 4 below |
| Archive | Per Section 5 below |
| Retention | Governed by [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) — placeholders, not invented |
| Deletion review | A deliberate, approved decision — never automatic deletion upon offboarding |
| Credential revocation | Every credential (user, device, service, API) associated with the organization |
| Domain removal | The organization's dedicated domain/subdomain is decommissioned per [network-reverse-proxy-tls-and-domain-architecture.md](network-reverse-proxy-tls-and-domain-architecture.md) |
| Backup handling | Backups containing the offboarded organization's data follow their own retention cycle — offboarding doesn't retroactively purge existing backups |
| Evidence | The offboarding action itself is documented and audit-relevant |

## 4. Data Portability Operations

A candidate future capability (an organization's data exported in a usable, complete format upon request or offboarding) — extends the general export architecture in [../02-data/import-export-and-data-exchange.md, Section 2](../02-data/import-export-and-data-exchange.md#2-export-architecture) and [../03-security/data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](../03-security/data-sharing-export-and-public-disclosure-controls.md#export-controls) with an organization-scoped completeness guarantee. Not yet implemented; readiness only.

## 5. Archival Operations

Operationalizes [../02-data/retention-archival-and-disposal.md, Section 2](../02-data/retention-archival-and-disposal.md#2-archiving) — a meet, organization, or record reaching its archival trigger moves to a lower-activity, still-recoverable store, distinct from disposal. Archival operations are scheduled, monitored (per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md)), and produce evidence of what was archived and when.

## 6. Meet Closure Operations

Operationalizes [../02-data/retention-archival-and-disposal.md, Section 3](../02-data/retention-archival-and-disposal.md#3-meet-closure-and-historical-preservation) into a DevOps-executed checklist:

Final result confirmation · medal tally confirmation · protest closure · assignment expiration · credential expiration · data reconciliation · audit completeness (confirming no gaps in the meet's audit trail) · committee reports (generated and archived) · financial summaries · publication archive (the public-facing record, preserved) · file archive (documents/media, per [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md)) · backup (a dedicated meet-closure backup, distinct from routine operational backup) · historical read-only mode (the closed meet's data becomes read-only, preventing accidental post-closure modification) · export package (a complete, reproducible historical record) · operational retrospective (feeding [../03-security/incident-response-and-breach-readiness.md, Section 7](../03-security/incident-response-and-breach-readiness.md#7-post-incident-review)-style review even absent an incident, since a meet closure is a natural point for operational lessons-learned).

## 7. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably whether multi-organization support is ever adopted (mirrors [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization), entirely outside this phase's authority to resolve) and the specific meet-closure checklist owner/cadence.
