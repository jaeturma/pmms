# PMMS UX Research, Validation, and Quality Gates

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md) · [../04-quality/pilot-operational-and-stakeholder-validation.md](../04-quality/pilot-operational-and-stakeholder-validation.md) · [user-groups-personas-and-contexts.md](user-groups-personas-and-contexts.md)

This document defines how PMMS's experience architecture is validated against real users and how design quality gates connect to Phase 0.7's release-quality model. **No test script or research protocol is executed here.**

---

## 1. Accessibility Testing Expectations (Cross-Reference)

Automated accessibility checks, manual screen-reader testing, and keyboard-only testing are defined in full in [../04-quality/accessibility-usability-and-compatibility-testing.md](../04-quality/accessibility-usability-and-compatibility-testing.md) — this document does not redefine that testing strategy, it confirms every requirement in [accessibility-architecture.md](accessibility-architecture.md) is a testable input to it.

## 2. UX Validation and Usability Testing

Every proto-persona in [user-groups-personas-and-contexts.md](user-groups-personas-and-contexts.md) is validated against real representative users during the pilot, per [../04-quality/pilot-operational-and-stakeholder-validation.md](../04-quality/pilot-operational-and-stakeholder-validation.md) — usability testing specifically targets: task completion (can a scanner operator complete a gate-scan flow without training beyond onboarding?) · error recovery (does a user understand what went wrong and what to do?) · terminology comprehension (do committee staff understand "certify" vs. "publish" without confusion?) · field-condition validity (does the interface actually work in bright sunlight, one-handed, on the actual devices procured?).

## 3. Design Review

Every new component or pattern passes through the design-review quality gate in [design-system-governance-documentation-and-versioning.md, Section 7](design-system-governance-documentation-and-versioning.md#7-design-quality-gates) before reaching Approved status — this document adds the *validation evidence* requirement: a design review is not considered complete without at minimum an accessibility self-check and a terminology-consistency check against [../01-architecture/domain-glossary.md](../01-architecture/domain-glossary.md).

## 4. Cross-Platform Consistency Validation

React and Flutter implementations of the same semantic pattern (a status badge, an error message, a certification flow) are periodically audited against each other for terminology, color-semantic, and behavioral consistency, per [cross-platform-design-system-architecture.md, Section 2](cross-platform-design-system-architecture.md#2-shared-foundations) — a drift between platforms (e.g., React showing "Certified" while Flutter shows "Confirmed" for the same state) is treated as a defect, not a stylistic choice.

## 5. Design Quality Gates in the Release Pipeline

Extends [../04-quality/automation-ci-and-quality-gates.md](../04-quality/automation-ci-and-quality-gates.md) and [../05-devops/ci-cd-and-release-pipeline-architecture.md, Section 2](../05-devops/ci-cd-and-release-pipeline-architecture.md#2-ci-quality-gates) with design-specific gates: no new component ships without passing [design-system-governance-documentation-and-versioning.md, Section 7](design-system-governance-documentation-and-versioning.md#7-design-quality-gates)'s review · no page ships with a known WCAG violation at the agreed conformance target (once set, per [accessibility-architecture.md, "Open Questions"](accessibility-architecture.md#7-open-questions)) · no high-integrity workflow ships without the explicit state/audit visibility required by working rule 33.

## 6. Relationship to Phase 0.7's Verification/Validation Distinction

Restated from [../04-quality/phase-0.7-quality-engineering-testing-validation-assurance.md, Section 8](../04-quality/phase-0.7-quality-engineering-testing-validation-assurance.md#8-verification-versus-validation) — automated accessibility checks and design-token compliance checks are **verification** (was it built to spec?); usability testing with real committee staff, technical officials, and athletes/guardians during the pilot is **validation** (does it actually solve the real operational problem?). This document requires both, never substituting one for the other.

## 7. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably which user groups receive dedicated pilot-time usability sessions given limited pilot resources, and the specific cross-platform consistency audit cadence.
