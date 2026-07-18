# PMMS Information Classification and Privacy

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 21](../01-architecture/phase-0.3-access-and-assignment-architecture.md#21-data-classification-model) · [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md) · [high-integrity-data-model.md](high-integrity-data-model.md)

This document carries forward the five-tier data classification model established in Phase 0.3 and applies it at the persistence layer, plus dedicated treatment for minor-athlete data protection. **All classifications remain proposed and require formal privacy and security validation** — restated explicitly, not weakened, from Phase 0.3.

---

## 1. Classification Tiers

| Tier | Examples | Authorized Users | Storage Expectations | Encryption | Logging | Export | Public Exposure | Offline Replication | Retention | Incident-Response Priority |
|---|---|---|---|---|---|---|---|---|---|---|
| **Public** | Published schedules, published results, approved medal tally, public announcements, privacy-approved athlete profile fields | Anyone (unauthenticated) | Standard | In-transit only | Freely loggable | Unrestricted (already public) | Full | N/A | Low (regenerable from source) | Low |
| **Internal** | Committee work plans, venue readiness, operational dashboards, unrestricted internal reports | Authenticated platform users within the relevant scope | Standard | In-transit; at-rest per general DB protection | Standard logging | Requires authentication | None | Low–Medium (cached reference) | Medium | Low–Medium |
| **Confidential** | Contact details, unpublished schedules, accreditation metadata, committee incidents | Role/scope-restricted | Standard, access-controlled | In-transit; at-rest per general DB protection | Redact identifying details from general logs | Requires authorization + reason for bulk export | None | Low–Medium | Medium | Medium |
| **Restricted** | Eligibility evidence, guardian data, access logs, protest evidence, financial attachments, internal security incidents | Narrowly role/scope-restricted, need-to-know | Elevated access control, candidate for field-level protection | In-transit + at-rest; field-level candidate | Never logged in plaintext; identifiers only | Authorized + reasoned, audited | None | Very limited — cached credential-validity sets only where specifically justified (BC-19→BC-20) | High | High |
| **Highly Restricted** | Detailed medical records, authentication secrets, security investigation records, privileged audit exports, encryption material, emergency access records | Extremely narrow, named-role only | Maximum access control, field-level encryption candidate for most items | In-transit + at-rest + field-level for most items | Never logged, ever | Exceptional-approval only, heavily audited | None, absolute | **None** — never replicated to any offline/mobile store | Highest | Highest |

## 2. Storage Expectations by Tier (Persistence-Layer Detail)

- **Public/Internal** data has no special storage requirement beyond the general database protection baseline.
- **Confidential** data is stored in the same MySQL instance as everything else but is subject to role/scope-filtered query paths — no table containing Confidential data is queryable through a general-purpose, unscoped endpoint.
- **Restricted** data (Eligibility evidence, access logs, protest evidence, financial attachments, internal security incidents) is a candidate for physically or logically separated schema treatment in Phase 0.6 (e.g., a distinct set of tables with stricter database-user grants), and field-level encryption is evaluated per specific field.
- **Highly Restricted** data (detailed medical records, authentication secrets, security investigation records, privileged audit exports, encryption material, emergency access records) receives the platform's strictest treatment: the narrowest possible set of application code paths ever reads it, field-level encryption is the default assumption pending Phase 0.6 confirmation, and it is the first category evaluated for any future dedicated-datastore separation.

## 3. Minor and Athlete Data Protection

Given that most PMMS athletes are minors (per [Phase 0.1 CON-09](../00-product/assumptions-constraints-risks.md#2-constraints)), the following architecture expectations apply specifically to Participant Registry (BC-07) and Athlete Registration (BC-08) data:

- **Data minimization** — the `Participant` table stores what registration, eligibility, and accreditation workflows actually require, not every conceivable biographical field.
- **Purpose limitation** — a field collected for eligibility verification is not repurposed for, e.g., marketing or unrelated analytics without a documented, approved basis.
- **Guardian relationships** — stored as an explicit, verified relationship record (per [../01-architecture/identity-model.md, Section 10](../01-architecture/identity-model.md#10-parent-or-guardian-relationship)), not inferred from shared surname or contact details.
- **Consent/authorization references** — where a guardian-consent requirement is confirmed (per [Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access)), the consent record itself is a first-class, retained artifact, not a checkbox whose evidence disappears after submission.
- **Restricted public profiles** — a minor athlete's public-facing profile (per [public-reporting-and-projection-data.md](public-reporting-and-projection-data.md)) exposes only the minimal approved fields (name, school, event, result) — never contact details, birth date precision beyond what's operationally needed, or any Confidential/Restricted field.
- **Age-sensitive visibility** — the architecture anticipates (without yet implementing) that visibility rules could differ by age band if DepEd policy requires it; the `Participant` table's age/birth-date field is structured to support this filtering logic being added later without a schema redesign.
- **Limited contact exposure** — contact details are Confidential-classified and never appear in any Public or Internal-tier view.
- **Controlled photo publication** — a profile photo (via Document and Records) requires the same publication-approval discipline as any other public projection — never auto-published on upload.
- **Protected medical and eligibility data** — per [high-integrity-data-model.md](high-integrity-data-model.md).
- **Export restrictions** — any bulk export touching minor-athlete Restricted/Confidential fields requires the reasoned, audited export path in [import-export-and-data-exchange.md](import-export-and-data-exchange.md), never an unrestricted CSV dump.
- **Verified relationship for parent access** — per [../01-architecture/access-open-decisions.md, AD-02](../01-architecture/access-open-decisions.md#ad-02--guardian-relationship-verification-mechanism), currently blocked pending Phase 0.1 OD-16.
- **Historical retention validation** — how long a minor's data is retained after they age out of eligibility or the platform stops tracking them is a policy question requiring legal input, not invented here (see [retention-archival-and-disposal.md](retention-archival-and-disposal.md)).
- **Safe test data** — per [test-seed-and-reference-data-strategy.md](test-seed-and-reference-data-strategy.md), no real minor-athlete data is ever used in non-production environments.
- **Redaction in logs and support tools** — restated from [audit-and-security-data-architecture.md, Section 4](audit-and-security-data-architecture.md#4-data-masking-and-redaction).

**No legal conclusion is stated here** — this document describes architectural expectations consistent with data-minimization good practice; actual legal compliance requirements (e.g., under applicable Philippine data privacy law) require Data Privacy and Legal Stakeholder validation, per [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md).

## 4. Classification-to-Persistence Mapping (Representative)

| Data Concept | Classification | Owning Context |
|---|---|---|
| Published result | Public | BC-16 → BC-29 |
| Committee task list | Internal | BC-05 |
| Delegation contact details | Confidential | BC-06 |
| Eligibility evidence document | Restricted | BC-09, BC-30 |
| Access scan log | Restricted | BC-20 |
| Protest evidence | Restricted | BC-17 |
| Financial supporting document | Restricted | BC-26, BC-30 |
| Detailed medical encounter | Highly Restricted | BC-21 |
| Authentication credential | Highly Restricted | BC-02 |
| Security investigation record | Highly Restricted | BC-25 |
| Audit export | Highly Restricted | BC-32 |

This table is illustrative, not exhaustive — every schema group in [conceptual-schema-catalog.md](conceptual-schema-catalog.md) is expected to carry an explicit classification assignment once physical schema design (Phase 0.6) begins.

## 5. Open Questions

- Formal privacy/legal validation of the classification tiers themselves.
- Age-band-specific visibility rules, if any, for minor athletes.
- Whether Restricted-tier tables warrant physical schema/database-user separation in Phase 0.6, or logical separation (row-level scoping) is sufficient.

Tracked in [data-open-decisions.md](data-open-decisions.md).
