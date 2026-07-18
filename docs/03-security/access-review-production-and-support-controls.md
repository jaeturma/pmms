# PMMS Access Review, Production, and Support Access Controls

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md) · [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md) · [security-metrics-monitoring-and-reporting.md](security-metrics-monitoring-and-reporting.md)

This document extends [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md) with production-access and support-access-specific controls. **No access-review tooling or support-ticketing configuration is created here.**

---

## 1. Access Review (Cross-Reference, Preserved Unchanged)

The full review-type table, review triggers, review stakeholders, and revocation model from [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md) are preserved without modification. This document does not restate them — it adds the production/support-specific controls below.

## 2. Production Access

| Control | Direction |
|---|---|
| Named access | Every production-environment access credential belongs to a specific named individual — never a shared "ops" login |
| MFA | Mandatory for any production access, restated from [authorization-and-privileged-access-assurance.md, Section 4](authorization-and-privileged-access-assurance.md#4-privileged-access-requirements) |
| Least privilege | Production access is scoped to exactly what the task requires |
| Approval | Production access is granted through an approval process, not self-service |
| Expiry | Time-bounded where the task allows it — standing indefinite production access is the exception, not the default |
| Environment-specific access | A Staging-environment credential does not grant Production access, and vice versa |
| Bastion or controlled path readiness | A future infrastructure-phase capability (a single, monitored access path into production) — anticipated, not built |
| No shared accounts | Restated as absolute |
| No direct routine database editing | Restated from [infrastructure-runtime-and-network-security.md, Section 2](infrastructure-runtime-and-network-security.md#2-mysql-security) — production data correction occurs only through the documented emergency-repair procedure, never as a routine support convenience |
| Command logging readiness | A candidate future control (recording commands executed during a privileged production session) — evaluated, not committed |
| Ticket reference | Production access tied to a specific operational task references that task/ticket |
| Post-use review | Production access, especially any emergency/time-bounded grant, is reviewed after use |
| Emergency access | Governed by the break-glass framework in [authorization-and-privileged-access-assurance.md, Section 6](authorization-and-privileged-access-assurance.md#6-break-glass-access-governance) — not a separate, looser production-specific exception |
| Offboarding | Production access is revoked immediately upon a person's departure or role change removing the need for it |
| Periodic review | Production access lists are periodically reviewed, per [../01-architecture/access-review-and-revocation.md, Section 1](../01-architecture/access-review-and-revocation.md#1-review-types) ("Platform-role review") |

## 3. Support Access

| Control | Direction |
|---|---|
| Support roles | A distinct role category (e.g., ROLE-04) with its own defined scope, never conflated with Platform/Security Administration |
| Read-only by default | Support access is read-only unless a specific, elevated, approved workflow requires write capability |
| Redacted views | A support role's view of user data is masked/redacted per [data-sharing-export-and-public-disclosure-controls.md, "Masking, Redaction, and De-Identification"](data-sharing-export-and-public-disclosure-controls.md#masking-redaction-and-de-identification), beyond what the specific support task requires |
| User consent or approved ticket where appropriate | Support access to an individual's data is tied to a specific, legitimate support request, not open-ended browsing |
| Impersonation restrictions | Per [authorization-and-privileged-access-assurance.md, Section 5](authorization-and-privileged-access-assurance.md#5-support-impersonation-governance) — impersonation, if implemented, never permits business-approval actions |
| Time limitation | A support session/access grant is bounded, not indefinite |
| Sensitive-domain restrictions | Support roles do not have standing access to medical, eligibility-evidence, or financial detail — those require the domain-specific elevated governance in [medical-eligibility-finance-and-sensitive-data-controls.md](medical-eligibility-finance-and-sensitive-data-controls.md) |
| No business approval | A support operator never performs an approval/certification/publication action, restated absolutely from SOD-11 |
| Full audit | Every support access is audit-relevant, per [audit-and-security-event-architecture.md, Section 4](audit-and-security-event-architecture.md#4-sensitive-data-access-auditing) |
| Post-session review | Support sessions touching sensitive data are candidates for periodic aggregate review, per [../01-architecture/access-review-and-revocation.md, Section 3](../01-architecture/access-review-and-revocation.md#3-review-stakeholders) |
| Escalation to privileged support | A support task requiring elevated access escalates through the privileged-access process (Section, [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md)) rather than the support role's own access being silently broadened |
| Data repair through controlled workflows | Any data correction a support interaction identifies as needed is executed through [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture), never a direct support-initiated database edit |

## 4. Relationship to Break-Glass and Impersonation

Production access (Section 2) and support access (Section 3) are both distinct from, and more routine than, the break-glass and impersonation categories governed in [authorization-and-privileged-access-assurance.md, Sections 5–6](authorization-and-privileged-access-assurance.md#5-support-impersonation-governance) — this document's controls apply regardless of whether break-glass/impersonation are ever implemented, since ordinary production and support access exist independent of those two still-open questions.

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably whether a bastion/controlled-access-path is adopted, production-access review cadence, and support-session command-logging scope.
