# PMMS Pilot, Operational Readiness, and Stakeholder Validation

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [quality-governance-and-ownership.md, Section 3](quality-governance-and-ownership.md#3-verification-versus-validation) · [../00-product/operating-model.md](../00-product/operating-model.md) · [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md)

This document defines the controlled pilot, operational-readiness validation, committee workflow validation, and sports-specialist validation — the human-facilitated Q3 activities (per [quality-engineering-strategy.md, Section 6](quality-engineering-strategy.md#6-test-quadrants)) that automated testing alone cannot provide.

---

## 1. Pilot Validation

| Element | Definition |
|---|---|
| Pilot goals | Confirm PMMS functions correctly and usably in a real (or realistically simulated) meet, surfacing gaps automated testing and UAT couldn't |
| Selected meet or simulation | A specific, scoped meet (real provincial meet or a controlled simulation) — not a full-scale rollout |
| Participating committees | The specific committees included in the pilot's scope |
| Selected sports | A deliberately limited sport selection, favoring representative complexity over exhaustive coverage |
| Selected venues | A deliberately limited venue selection |
| Selected devices | The specific device set used, representative of the real eventual device inventory |
| Entry criteria | What must be true (per [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md)) before the pilot begins |
| Success measures | Defined qualitatively where numeric targets aren't yet available (per [performance-load-concurrency-and-capacity-testing.md, Section 2](performance-load-concurrency-and-capacity-testing.md#2-load-model-inputs)) |
| Support model | Who supports pilot participants in real time, and how |
| Incident handling | How a pilot-time incident is triaged, per [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md) |
| Daily review | A structured daily check-in during the pilot to surface issues quickly |
| Data reconciliation | Pilot-generated data is reconciled against expected/manual-fallback records |
| User feedback | Structured feedback collection from pilot participants |
| Exit criteria | What must be true to consider the pilot successfully concluded |
| Rollback | A defined path to fall back to the prior (manual or previous-system) process if the pilot reveals a blocking issue |
| Post-pilot report | A structured report summarizing outcomes, issues, and go/no-go recommendation for broader rollout |

The pilot is the first point at which real load-model inputs (Section, [performance-load-concurrency-and-capacity-testing.md, Section 2](performance-load-concurrency-and-capacity-testing.md#2-load-model-inputs)) and real golden-dataset values (Section, [test-data-fixture-and-scenario-strategy.md, Section 5](test-data-fixture-and-scenario-strategy.md#5-golden-datasets)) become available — much of PMMS's non-functional validation depends on the pilot happening, not merely on more pre-pilot engineering effort.

## 2. Operational Readiness Testing

Validates that PMMS is ready to actually run a meet, distinct from whether the software itself is functionally correct (restated from working rule 38):

User provisioning · role assignments · device registration · venue readiness · network · MySQL · Redis · Reverb · Horizon · MinIO · backups · monitoring · alerts · support · incident contacts · manual fallback · training · documentation · public communication.

Each item above is a go/no-go checklist entry before a meet (pilot or otherwise) begins — a technically-passing test suite does not by itself confirm operational readiness; every item requires its own confirmation.

## 3. Committee Workflow Validation

Every committee validates its own real workflow, reports, permissions, and handoffs — a software engineer cannot substitute for this validation, since committee operational practice is domain knowledge outside engineering's own expertise:

| Committee | Validation Focus |
|---|---|
| Secretariat | Meet-administration and documentation workflows |
| Tournament managers | Bracket/schedule/entry management workflows |
| Technical officials | Scoring/result/protest workflows |
| Tally team | Medal-tally workflows |
| Medical team | Medical-encounter and emergency-access workflows |
| Food committee | Meal-entitlement and food-service workflows |
| Transportation | Transport-assignment workflows |
| Billeting | Accommodation-assignment workflows |
| Finance | Budget/expense/approval workflows |
| Security | Access-validation and incident workflows |
| ICT | Device-provisioning and support workflows |
| Media | Publication and communication workflows |

Each committee confirms: the workflow matches real operational practice, the generated reports meet their actual needs, their role's permissions are correctly scoped (neither too broad nor too narrow), and handoffs to/from other committees function smoothly.

## 4. Sports-Specialist Validation

Required review, by role, never bypassed by engineering judgment (restated absolutely: "Software engineers must not approve sports rules independently"):

Competition formats · draws · brackets · heats · lanes · seeding · scoring · advancement · placements · medals · protests · disqualifications · official forms · rule versions.

The Sports-rule validator role (per [quality-governance-and-ownership.md](quality-governance-and-ownership.md)) confirms every sport-specific expected outcome used in [high-integrity-sports-workflow-testing.md](high-integrity-sports-workflow-testing.md) and any golden dataset (per [test-data-fixture-and-scenario-strategy.md, Section 5](test-data-fixture-and-scenario-strategy.md#5-golden-datasets)) against an approved rule source before that expected outcome is trusted for testing or release decisions.

## 5. Security and Privacy Assurance (Cross-Reference)

Pilot and operational-readiness activities incorporate, and do not duplicate, the security/privacy assurance testing defined in [security-privacy-audit-and-compliance-assurance.md](security-privacy-audit-and-compliance-assurance.md) — a pilot's operational-readiness checklist includes confirming security controls are actually active in the pilot environment, not re-deriving what those controls should be.

## 6. Audit Evidence (Cross-Reference)

Pilot and UAT activities produce audit-relevant evidence per [quality-metrics-reporting-and-evidence.md](quality-metrics-reporting-and-evidence.md) and are themselves subject to the audit-completeness testing in [security-privacy-audit-and-compliance-assurance.md, Section 5](security-privacy-audit-and-compliance-assurance.md#5-compliance-assurance-testing) — a pilot that generates real (if synthetic-data-based) certified results should produce a fully traceable audit trail, confirming the audit architecture works under real operational conditions, not just isolated test scenarios.

## 7. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably pilot meet selection/timing (dependent on DepEd scheduling, outside this documentation's control) and the specific success-measure thresholds once pilot planning data becomes available.
