# PMMS Minor-Athlete and Guardian Data Governance

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [privacy-by-design-architecture.md](privacy-by-design-architecture.md) · [../02-data/information-classification-and-privacy.md, Section 3](../02-data/information-classification-and-privacy.md#3-minor-and-athlete-data-protection) · [data-sharing-export-and-public-disclosure-controls.md](data-sharing-export-and-public-disclosure-controls.md)

Most PMMS athletes are minors. This document defines enhanced privacy controls for minor-athlete and guardian data. **No legal conclusion about child-data-protection law is stated here** — every control is a design commitment pending legal and policy validation, per working rule 21 ("Treat minors' data as requiring enhanced privacy controls").

---

## 1. Minor-Athlete Privacy

| Control | Direction |
|---|---|
| Minimum necessary data | Restated absolutely — a minor athlete's record holds only what eligibility, registration, competition, medical-emergency-readiness, and accreditation genuinely require |
| Verified guardian relationship | Restated from Section 2 below — access on a guardian's behalf requires a verified relationship, never a self-declared or inferred one |
| Restricted public profile | A minor athlete's public profile (per [../02-data/public-reporting-and-projection-data.md, Section 1](../02-data/public-reporting-and-projection-data.md#1-public-projections)) excludes exact birthdate, full contact information, and any unnecessary identifying detail |
| Controlled photo publication | Any published photo of a minor athlete requires an approval step (Section 3 below) — never automatic publication on upload |
| Contact-data protection | Guardian and, where applicable, athlete contact information is never exposed publicly and is restricted to the specific roles whose workflow requires it |
| No unnecessary exact birth-date exposure | Age-band or eligibility-category information may be shown where operationally needed; exact birthdate is Restricted-tier, not casually displayed |
| Medical-data restriction | Per [medical-eligibility-finance-and-sensitive-data-controls.md, "Medical Data Governance"](medical-eligibility-finance-and-sensitive-data-controls.md#medical-data-governance) — a minor's medical data receives the same Highly Restricted treatment as any other medical record, with no reduced protection |
| Eligibility-document restriction | Per [medical-eligibility-finance-and-sensitive-data-controls.md, "Eligibility Data Governance"](medical-eligibility-finance-and-sensitive-data-controls.md#eligibility-data-governance) |
| Safe exports | Bulk exports containing minor-athlete data require elevated authorization and reason capture, per [data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](data-sharing-export-and-public-disclosure-controls.md#export-controls) |
| Restricted media use | Photo/video use beyond the originally-approved publication context requires a new approval, not an assumed standing consent |
| Privacy-safe analytics | Analytics/reporting involving minors favors aggregation and de-identification wherever the specific report does not require individual-level detail |
| Lower-environment protection | Restated from [../02-data/test-seed-and-reference-data-strategy.md, Section 3](../02-data/test-seed-and-reference-data-strategy.md#3-test-data-strategy) — minor-athlete test data is obviously synthetic, never real production data, in any environment below Production |
| AI restrictions | An AI-assisted feature never receives more minor-athlete data than its specific approved use case requires, per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) |
| Consent or authority record readiness | Per Section 4 below |
| Incident escalation | Any security or privacy incident involving minor-athlete data is escalated with elevated priority, per [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |

## 2. Guardian Data

| Control | Direction |
|---|---|
| Verified relationship | A guardian's access to a minor's data requires the relationship to be verified through the registration/delegation process — never inferred from a shared surname or self-declaration alone, restated from [../02-data/information-classification-and-privacy.md, Section 3](../02-data/information-classification-and-privacy.md#3-minor-and-athlete-data-protection) |
| Minimum data | Only the guardian data genuinely needed (identity, relationship, emergency contact) is collected |
| Contact access | Guardian contact information is visible only to the specific roles whose workflow requires it (registration, medical emergency, delegation coordination) |
| Self-service limits | If guardians are ever given direct portal access (not yet decided — see [../00-product/open-decisions.md](../00-product/open-decisions.md)), their self-service scope is limited to their own verified relationships only |
| Delegated access | A guardian acting through a delegation representative (common for school-based provincial meets) has that delegated relationship itself recorded and auditable |
| Revocation | A guardian relationship can be corrected/revoked if established in error, following the same correction discipline as any other high-integrity-adjacent record |
| Multiple guardians | The data model accommodates more than one recognized guardian per athlete without assuming a single fixed relationship |
| Disputes | A disputed guardian relationship (e.g., custody conflict) is a candidate escalation to a named authority — not resolved automatically by the platform |
| Historical relationship | A guardian relationship's history is preserved even if later corrected or ended, consistent with [../02-data/temporal-history-and-versioning-model.md](../02-data/temporal-history-and-versioning-model.md) |
| Public non-disclosure | Guardian identity/contact is never part of any public projection |
| Export restriction | Guardian data exports follow the same elevated authorization as minor-athlete data exports |
| Notification preferences | Where notifications are guardian-directed, delivery respects the guardian's registered contact channel only |
| Audit | Every access to guardian data beyond the minimal registration-workflow exposure is audit-relevant, per [audit-and-security-event-architecture.md, Section 4](audit-and-security-event-architecture.md#4-sensitive-data-access-auditing) |

## 3. Photo and Media Publication Governance

- Publication of any identifiable photo/video of a minor athlete requires a documented approval step, distinct from ordinary content upload — restated from Section 1.
- The approval records who approved, when, and the specific publication context (e.g., "official meet gallery," not an unbounded "any future use").
- An approved publication can be withdrawn (unpublish/correction flow, per [data-sharing-export-and-public-disclosure-controls.md, "Public Disclosure Controls"](data-sharing-export-and-public-disclosure-controls.md#public-disclosure-controls)) if a concern is later raised.
- Whether an opt-out or affirmative-consent model governs photo publication is an open question requiring legal/policy input (Section 5).

## 4. Consent and Authority Records (Cross-Reference)

Candidate consent/authority record types relevant to minors specifically — guardian authorization for participation, photo/media authorization, medical-handling authorization, emergency-contact authorization — are defined in full in [data-sharing-export-and-public-disclosure-controls.md, "Consent and Authority Records"](data-sharing-export-and-public-disclosure-controls.md#consent-and-authority-records), not duplicated here. **This documentation does not assume any specific record is legally required** — each is marked for legal and policy validation.

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably whether a formal, DepEd-sourced child-data-protection policy already exists that this documentation should cite (tracked in [policy-source-registry.md](policy-source-registry.md) as a placeholder pending verification), and whether photo publication uses an opt-out or affirmative-consent model.
