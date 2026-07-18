# PMMS Vendor and Third-Party Risk

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/internal-integration-architecture.md, Section 4](../01-architecture/internal-integration-architecture.md#4-external-integration-status) · [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) · [data-sharing-export-and-public-disclosure-controls.md](data-sharing-export-and-public-disclosure-controls.md)

This document defines the vendor-risk assessment framework PMMS applies before approving any third-party service. **No vendor is selected or approved in this document** — restated from [../01-architecture/internal-integration-architecture.md, Section 4](../01-architecture/internal-integration-architecture.md#4-external-integration-status): no external integration is currently approved.

---

## 1. Vendor Assessment Areas

| Area | Assessment Question |
|---|---|
| Service purpose | What specific function does this vendor perform, and is it genuinely necessary? |
| Data accessed | What PMMS data categories (per [privacy-by-design-architecture.md, Section 3](privacy-by-design-architecture.md#3-personal-data-inventory)) would this vendor access? |
| Data classification | What is the highest classification tier of data the vendor would touch? |
| Data location | Where is the vendor's data processed/stored geographically? |
| Subprocessors | Does the vendor use its own subprocessors, and are they disclosed? |
| Authentication | How does PMMS authenticate to the vendor, and how does the vendor authenticate to PMMS (for inbound webhooks, etc.)? |
| Encryption | Does the vendor encrypt data in transit and at rest? |
| Retention | How long does the vendor retain PMMS data, and can this be limited/configured? |
| Deletion | Can PMMS request/verify deletion of its data from the vendor? |
| Incident notification | Does the vendor commit to notifying PMMS of a security incident affecting PMMS data? |
| Availability | What is the vendor's availability track record/commitment? |
| Exit plan | What happens to PMMS data and functionality if the vendor relationship ends? |
| Portability | Can PMMS retrieve its data from the vendor in a usable format? |
| Audit evidence | Can the vendor provide evidence of its own security controls (e.g., a compliance certification, security questionnaire response)? |
| Contract readiness | Is a data-processing agreement or equivalent contractual protection available? |
| AI training use | Does the vendor use PMMS data to train its own models, and can this be disabled/excluded? |
| Lock-in | How difficult would it be to migrate away from this vendor later? |
| Security history | Does the vendor have a known history of security incidents? |
| Support access | Does the vendor's own support staff have access to PMMS data, and under what controls? |

## 2. Assessment Process

1. **Purpose justification** — the requesting team documents why the vendor is needed and what alternatives (including "build vs. buy") were considered.
2. **Data-scope determination** — the specific data categories and classification tiers the vendor would touch are identified before engagement.
3. **Assessment execution** — Section 1's questions are answered, with evidence where available.
4. **Risk review** — Security owner + Privacy owner + Vendor manager review the assessment.
5. **Approval decision** — approved, approved with conditions, or declined — recorded per Section 3 (Decision Rights) of [security-architecture.md](security-architecture.md).
6. **Contractual protection** — where the vendor will process personal data, a data-processing agreement or equivalent is pursued before go-live.
7. **Ongoing oversight** — an approved vendor is periodically re-assessed, not approved once and forgotten.
8. **Offboarding** — when a vendor relationship ends, data deletion/portability is executed and verified per Section 1.

## 3. Currently Approved Vendors

**None.** Per [../01-architecture/internal-integration-architecture.md, Section 4](../01-architecture/internal-integration-architecture.md#4-external-integration-status), no external integration (email/SMS provider, push-notification service, AI provider, payment gateway, or other third-party service) is currently approved for PMMS. This document exists so that when a specific vendor is proposed, it is assessed against a consistent framework rather than approved ad hoc.

## 4. Candidate Future Vendor Categories

Anticipated, not committed:

- Email/SMS delivery provider (for notification delivery, per [../01-architecture/notification-architecture.md](../01-architecture/notification-architecture.md)).
- Push-notification service (for the Flutter mobile app).
- AI service provider (per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md)).
- Malware-scanning service (per [file-object-storage-and-malware-security.md, Section 3](file-object-storage-and-malware-security.md#3-malware-scanning-architecture)).
- Object-storage/CDN provider, if MinIO's underlying infrastructure is externally hosted.
- Monitoring/observability platform (per [../01-architecture/runtime-open-decisions.md, RD-14](../01-architecture/runtime-open-decisions.md#rd-14--monitoringobservability-stack-selection)).
- Secret-management platform (per [cryptography-key-and-secret-management.md, Section 3](cryptography-key-and-secret-management.md#3-secret-management)).

Each of the above, when actually proposed, goes through the Section 2 assessment process before approval — none is pre-approved by appearing in this list.

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably which vendor category is likely to be needed first (informing assessment prioritization), and whether a formal vendor-security-questionnaire template is adopted.
