# PMMS Privacy-by-Design Architecture

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md) · [../02-data/logical-data-architecture.md](../02-data/logical-data-architecture.md) · [minor-athlete-and-guardian-data-governance.md](minor-athlete-and-guardian-data-governance.md)

This document defines PMMS's privacy principles, privacy-by-design controls, and the personal-data inventory. **No legal conclusion about applicable privacy law is stated anywhere in this document** — every principle is a design commitment requiring legal and policy validation before being represented as a compliance claim.

---

## 1. Privacy Principles

| Principle | PMMS Application |
|---|---|
| Transparency | Users and guardians can understand what data is collected and why, through eventual privacy notices (Section, [../02-data/data-open-decisions.md, PD-08](../02-data/data-open-decisions.md#pd-08--formal-classification-tier-validation) and this package's Section 52) |
| Purpose limitation | Data collected for one workflow (eligibility review) is not repurposed for another without a new, explicit basis, restated from [../02-data/logical-data-architecture.md](../02-data/logical-data-architecture.md) |
| Data minimization | Only what a specific workflow requires is collected, replicated, logged, or exported |
| Accuracy | Data-quality controls (per [../02-data/high-integrity-data-model.md, "Data Quality Controls"](../02-data/high-integrity-data-model.md#data-quality-controls)) support accurate personal data, with a correction path for the data subject (Section 51) |
| Storage limitation | Retention is governed, not indefinite, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) |
| Security | Confidentiality/integrity controls throughout this package protect personal data proportionate to its classification |
| Accountability | Every access to and disclosure of personal data is traceable, per [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md) |
| Controlled sharing | Per [data-sharing-export-and-public-disclosure-controls.md](data-sharing-export-and-public-disclosure-controls.md) |
| Limited public disclosure | Public projections expose only Public-tier data, filtered at build time, per [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) |
| Human oversight | AI-assisted processing of personal data remains advisory-only for consequential decisions, per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) |
| Minor protection | Enhanced controls throughout, per [minor-athlete-and-guardian-data-governance.md](minor-athlete-and-guardian-data-governance.md) |
| Privacy-respecting defaults | New features start with the narrowest data collection/exposure and expand only deliberately |
| Data lifecycle governance | Personal data follows the full lifecycle (collection → use → retention → disposal) under governance, per [data-governance-operating-model.md](data-governance-operating-model.md) |

## 2. Privacy-by-Design Controls

1. **Collect only necessary data** — every field on every form is justified by a specific workflow need.
2. **Separate optional from required fields** — a field not strictly necessary for the workflow is marked optional and never silently required.
3. **Classify every sensitive dataset** — per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md), before it is collected, not retroactively.
4. **Limit defaults** — a new field/feature defaults to the least exposure (least visible, least replicated, least retained) until deliberately configured otherwise.
5. **Use privacy-filtered public projections** — public data is never a direct read of an operational table, per [../02-data/public-reporting-and-projection-data.md, Section 1](../02-data/public-reporting-and-projection-data.md#1-public-projections).
6. **Restrict exports** — per [data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](data-sharing-export-and-public-disclosure-controls.md#export-controls).
7. **Mask data in UI** — per [../02-data/audit-and-security-data-architecture.md, Section 4](../02-data/audit-and-security-data-architecture.md#4-data-masking-and-redaction).
8. **Redact logs** — restated from [audit-and-security-event-architecture.md, Section 9](audit-and-security-event-architecture.md#9-logging-and-monitoring-boundaries).
9. **Minimize offline replication** — restated from [mobile-device-and-offline-security.md, Section 3](mobile-device-and-offline-security.md#3-offline-security).
10. **Minimize AI data exposure** — per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md).
11. **Preserve consent or authorization references where required** — per Section 52 below; the existence of such records does not itself imply a specific legal requirement is satisfied.
12. **Support correction requests** — per Section 51 below.
13. **Support access-review workflows** — per [access-review-production-and-support-controls.md](access-review-production-and-support-controls.md).
14. **Support retention and disposal** — per [retention-disposal-and-legal-hold-governance.md](retention-disposal-and-legal-hold-governance.md).
15. **Support incident response** — per [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md).
16. **Document data flows** — Section 3's personal-data inventory is the foundation for a future formal data-flow mapping exercise.

## 3. Personal-Data Inventory

| Category | Purpose | Owning Context | Classification (Typical) | Public Exposure | Offline Replication | External Sharing | Retention Authority | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Identity | Name, birthdate, sex, identifiers | Participant Registry (BC-07) | Confidential–Restricted | Restricted (masked public profile) | Minimal (per role) | None currently approved | Pending DepEd/legal (PD-04) | Unvalidated |
| Contact | Phone, email, address | Participant Registry / Identity and Access (BC-02/07) | Confidential | Never public | Minimal | None currently approved | Pending | Unvalidated |
| School / organization | School, division, region affiliation | Organization Directory (BC-03) | Internal–Confidential | Delegation-level public (aggregate) | Yes | None currently approved | Pending | Unvalidated |
| Participation | Registrations, entries, assignments | Athlete Registration / Competition Entries (BC-08/11) | Internal–Confidential | Public (aggregate, non-identifying where feasible) | Yes | None currently approved | Pending | Unvalidated |
| Guardian | Guardian identity, relationship, contact | Participant Registry (BC-07) | Restricted | Never public | No | None currently approved | Pending | Unvalidated |
| Eligibility | Evidence documents, decisions | Eligibility and Clearance (BC-09) | Restricted | Status-only, never evidence | No | None currently approved | Pending | Unvalidated, blocked on [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) |
| Medical | Encounters, conditions, treatment | Medical Operations (BC-21) | Highly Restricted | Never | Minimal (emergency-relevant flag only) | None currently approved | Pending | Unvalidated, blocked on [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling) |
| Accreditation | Credential data, photo | Accreditation (BC-19) | Confidential–Restricted | Credential-holder-visible only | Yes (validation-relevant) | None currently approved | Pending | Unvalidated |
| Access control | Scan records | Access Validation (BC-20) | Restricted | Never | Yes (transient) | None currently approved | Pending | Unvalidated |
| Location / venue activity | Venue/schedule presence inferred from scans/assignments | Access Validation / Venue and Schedule (BC-20/14) | Restricted | Never individually | Yes | None currently approved | Pending | Unvalidated |
| Financial | Expense/budget records tied to a person (e.g., reimbursement) | Finance Operations (BC-26) | Restricted | Approved summaries only | No | None currently approved | Pending | Unvalidated |
| Security | Incident records, investigation detail | Security Operations (BC-25) | Highly Restricted | Never | No | None currently approved | Pending | Unvalidated |
| Technical device | Device/operator association | Device and Service Identity | Internal–Confidential | Never | Yes (own device only) | None currently approved | Pending | Unvalidated |
| Audit | Actor-attributed action history | Audit and Compliance (BC-32) | Highly Restricted (as a whole) | Never | No | None currently approved | Pending | Unvalidated |
| Media and photo | Athlete/event photos | Media and Communications (BC-28) | Confidential–Public (upon approval) | Approved-only, per Section, [minor-athlete-and-guardian-data-governance.md](minor-athlete-and-guardian-data-governance.md) | No | None currently approved | Pending | Unvalidated |
| Public-profile data | The approved subset shown on public athlete/delegation profiles | Public Information (BC-29, projection only) | Public (by definition, filtered) | Yes, by design | No | Public by design | Derived from source retention | Unvalidated |

Every row's classification, retention, and public-exposure treatment restates — never overrides — the corresponding entries in [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md) and [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md).

## 4. Data Minimization and Purpose Limitation (Applied)

- A workflow's data-collection form includes only fields that workflow's approved use case requires — a field added "for future use" without a current workflow need is a documented anti-pattern.
- Analytics and reporting (BC-33) consume de-identified or aggregated data wherever the specific report does not require individual-level detail, per [data-sharing-export-and-public-disclosure-controls.md](data-sharing-export-and-public-disclosure-controls.md).
- AI-assisted features receive the minimum data needed for the specific request, never a broad standing dataset, per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md).

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably formal privacy-notice content and delivery mechanism, and whether a Data Protection Impact Assessment (or equivalent) is warranted before Phase 0.7, both requiring Data Privacy and Legal Stakeholder input.
