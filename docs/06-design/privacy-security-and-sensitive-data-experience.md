# PMMS Privacy, Security, and Sensitive-Data Experience

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../03-security/privacy-by-design-architecture.md](../03-security/privacy-by-design-architecture.md) · [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md) · [../02-data/audit-and-security-data-architecture.md, Section 4](../02-data/audit-and-security-data-architecture.md#4-data-masking-and-redaction)

This document defines how classification, privacy, and security controls established in Phases 0.5 and 0.6 are expressed at the interface layer. **No masking implementation or access-control code is created here.**

---

## 1. Privacy-Aware Experience

Masked fields (a Restricted/Highly Restricted field shows a masked representation by default, per [../02-data/audit-and-security-data-architecture.md, Section 4](../02-data/audit-and-security-data-architecture.md#4-data-masking-and-redaction), full value shown only where the specific authorized workflow requires it) · restricted sections (a page section requiring elevated authorization is visually distinguished, not silently absent, so an authorized user isn't confused about whether it exists) · access-reason prompts (where an action requires reason capture) · sensitive-view audit notice (a user viewing Highly Restricted data is informed the view itself is audited — transparency about accountability, not merely a backend log) · public-view preview (before publishing, a user can preview exactly what the public will see, per [high-integrity-approval-certification-and-publication-ux.md, Section 5](high-integrity-approval-certification-and-publication-ux.md#5-publication-ux)) · privacy warnings before export (per [search-filter-import-export-and-file-experience.md, Section 4](search-filter-import-export-and-file-experience.md#4-export-experience)) · shared-device privacy (per [flutter-mobile-experience-architecture.md, Section 3](flutter-mobile-experience-architecture.md#3-shared-device-experience)) · automatic clearing (sensitive view state clears on session/handoff) · protected clipboard behavior where justified (preventing a Highly Restricted value from being casually copied) · screenshot cautions where justified (a candidate OS-level control, per [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security)) · **no sensitive data in notification previews** — restated absolutely.

## 2. Security-Aware Design

Every interface element that reflects an authorization boundary (a hidden menu item, a disabled button, a masked field) is understood as a **usability convenience layered on top of independent server-side enforcement** — restated absolutely per working rule 25; frontend hiding is never treated as authorization. This principle governs every component in this design system, not merely the ones explicitly named "privacy-aware" above.

## 3. Sensitive-Data Masking and Public-Data Filtering

Restated from [../02-data/audit-and-security-data-architecture.md, Section 4](../02-data/audit-and-security-data-architecture.md#4-data-masking-and-redaction) and applied to every surface named in this Phase 0.9 package: UI masking, report masking, log redaction (not user-facing but architecturally relevant), export redaction, support-view redaction, staging-data masking, public-projection filtering, partial-identifier display, medical-summary substitution, guardian-contact masking, audit-export restriction. **No component in this design system displays a field without first confirming its classification-appropriate treatment.**

## 4. Minor-Athlete Presentation Rules

Restated absolutely from [../03-security/minor-athlete-and-guardian-data-governance.md, Section 1](../03-security/minor-athlete-and-guardian-data-governance.md#1-minor-athlete-privacy): restricted public profile content (no exact birthdate, no full contact info) · controlled photo publication (never automatic) · age-appropriate language throughout (per [content-design-terminology-help-and-onboarding.md](content-design-terminology-help-and-onboarding.md)) · privacy-aware search (a minor's record is never more discoverable through search than the classification model permits) · safe error and notification content (an error message about a minor's record never leaks more detail than the viewer's authorization already permits).

## 5. Medical, Eligibility, Finance, and Audit-Data Interface Restrictions (Cross-Reference)

Detailed interface treatment for each of these four domains is defined in their respective owning documents rather than duplicated here:

| Domain | Interface Restriction Detail |
|---|---|
| Medical | [committee-logistics-medical-finance-and-support-experience.md, Section 2](committee-logistics-medical-finance-and-support-experience.md#2-medical-experience) |
| Eligibility | [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md) (approval/certification interfaces) and [sports-tournament-scoring-and-results-components.md, Section 9](sports-tournament-scoring-and-results-components.md#9-athlete-profile-experience) (public-status-only display) |
| Finance | [committee-logistics-medical-finance-and-support-experience.md, Section 3](committee-logistics-medical-finance-and-support-experience.md#3-finance-experience) |
| Audit | [high-integrity-approval-certification-and-publication-ux.md, Section 3](high-integrity-approval-certification-and-publication-ux.md#3-audit-history-and-change-history-interfaces) |

This document is the index and the shared masking/privacy-pattern source every one of those four builds on.

## 6. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably screenshot-prevention adoption timing for Highly Restricted mobile screens and the specific sensitive-view audit-notice wording/prominence.
