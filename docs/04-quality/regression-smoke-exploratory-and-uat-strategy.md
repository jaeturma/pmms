# PMMS Regression, Smoke, Sanity, Exploratory, and UAT Strategy

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [quality-governance-and-ownership.md, Section 3](quality-governance-and-ownership.md#3-verification-versus-validation) · [pilot-operational-and-stakeholder-validation.md](pilot-operational-and-stakeholder-validation.md)

This document defines regression grouping, smoke/sanity checks, exploratory-testing charters, and user acceptance testing. **No test script is created here.**

---

## 1. Regression Strategy

### Regression Groups

Core smoke · authentication · authorization · registration · eligibility · tournament · scoring · results · protest · medal tally · accreditation · public portal · mobile · offline · files · notifications · reports · security · privacy · audit.

Each group corresponds to a coherent slice of functionality that can be run independently, allowing change-impact analysis (below) to select a targeted subset rather than always running everything.

### Change-Impact Analysis

Before selecting which regression groups to run for a given change, ask:

- Which bounded context(s) does this change touch, per [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md)?
- Does this change touch a Critical-tier area, per [risk-based-testing-model.md](risk-based-testing-model.md)?
- Does this change touch a shared/cross-cutting concern (authorization, audit, classification) that could ripple beyond its immediate module?
- Does this change touch a rule source (sports rule, eligibility rule, policy)? If so, per working rule 16 in the main document ("Rule-source changes require regression impact analysis"), the full set of workflows depending on that rule source is identified and retested, not assumed unaffected.

A change confined to a Low-tier, well-isolated area may reasonably run only its own regression group plus core smoke; a change touching a Critical-tier or cross-cutting area runs its group plus every group with a plausible dependency.

## 2. Smoke Testing

Critical availability and golden-path journey checks, run before deeper testing proceeds on any build:

Login · meet selection · main dashboard · athlete lookup · registration · score entry · result view · medal tally view · public portal · QR validation · queue processing · file storage · Reverb · health checks.

A smoke-test failure blocks further testing of that build — restated implicitly by the nature of a smoke suite: if the basics don't work, deeper testing wastes effort investigating symptoms of a foundational break.

## 3. Sanity Testing

A narrow, quick check that a specific fix or small change behaves as intended, without re-running the full regression suite. Used after a targeted bug fix to confirm the fix works and hasn't obviously broken its immediate neighborhood, before committing to a fuller regression pass.

## 4. Exploratory Testing

Skilled, unscripted investigation using defined charters (a goal and a time-box, not a step-by-step script):

| Charter | Focus |
|---|---|
| Time-pressure score entry | Explore scoring UX under simulated real-competition time pressure |
| Conflicting assignments | Explore behavior when a user holds overlapping/conflicting assignments |
| Offline venue | Explore the full offline experience end-to-end, not just individual offline unit scenarios |
| Network interruption | Explore behavior under intermittent, not merely fully-down, connectivity |
| Public-result correction | Explore the full experience of correcting a published result, from the internal action through the public-facing effect |
| Shared-device use | Explore multi-operator handoff on one physical device |
| Malicious user behavior | Explore what a deliberately adversarial user might attempt (input manipulation, workflow-order abuse) |
| Low digital literacy | Explore the experience through the lens of a user with limited digital literacy |
| Large data | Explore behavior with unusually large registration/entry/result volumes |
| Unexpected workflow order | Explore what happens when a user attempts steps out of the expected sequence |
| Committee handoff | Explore continuity when one committee member's work is picked up by another |
| Meet closure | Explore the full meet-closure and historical-preservation experience |

Exploratory testing findings feed directly into [defect-triage-root-cause-and-quality-debt.md](defect-triage-root-cause-and-quality-debt.md) and, where they reveal a systemic gap, into new automated regression coverage — restated from [quality-engineering-strategy.md, Section 3](quality-engineering-strategy.md#3-quality-principles) ("production incidents must improve the test suite," applied equally to exploratory-testing findings before production).

## 5. User Acceptance Testing

| Element | Definition |
|---|---|
| UAT scope | The specific features/workflows under acceptance review for a given release/milestone |
| Representative users | Real (or realistically representative) committee staff, tournament managers, technical officials — not solely engineering/QA staff role-playing |
| Scenarios | Business-realistic scenarios drawn from [requirements-traceability-model.md](requirements-traceability-model.md)'s acceptance criteria |
| Data | Synthetic, per [test-data-fixture-and-scenario-strategy.md](test-data-fixture-and-scenario-strategy.md) — never real protected data |
| Environment | A stable, production-like environment (Staging or a dedicated UAT environment) |
| Facilitator | The UAT coordinator role, per [quality-governance-and-ownership.md](quality-governance-and-ownership.md) |
| Evidence | UAT session records/forms, per [quality-metrics-reporting-and-evidence.md](quality-metrics-reporting-and-evidence.md) |
| Defect handling | UAT-discovered defects enter the standard defect-management process, per [defect-triage-root-cause-and-quality-debt.md](defect-triage-root-cause-and-quality-debt.md) |
| Retest | Fixed defects are re-verified by the same or an equivalent UAT participant before re-acceptance |
| Sign-off | UAT completion produces a formal sign-off record, feeding [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md) |
| Conditional acceptance | UAT may conclude "accepted with known limitations" rather than a binary pass/fail — restated from the Release Sign-Off decision categories |
| Known limitations | Explicitly documented, not silently absorbed into "accepted" |
| Training feedback | UAT often surfaces training-material gaps as a byproduct — captured and routed to whoever owns onboarding/training content |

## 6. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably how UAT participants are recruited/scheduled given they are real DepEd/committee stakeholders with their own operational duties, and the specific regression-group automation coverage target per group.
