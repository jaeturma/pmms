# PMMS Quality Governance and Ownership

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [quality-engineering-strategy.md](quality-engineering-strategy.md) · [../03-security/security-architecture.md, Section 3](../03-security/security-architecture.md#3-security-governance-model) · [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md)

This document defines quality-governance roles, ownership responsibilities, and the verification/validation distinction. **No governance tooling is created here.**

---

## 1. Quality Governance Roles (Candidates, Not Named Individuals)

| Role | Responsibility |
|---|---|
| Product owner | Overall product-quality priority and trade-off decisions |
| Quality owner | Accountable for the quality-engineering architecture itself and its ongoing effectiveness |
| QA lead | Day-to-day test-strategy execution and coordination |
| Domain owner | Per bounded context (per [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md)) — confirms domain-rule correctness |
| Technical lead | Engineering-quality standards, code review, static-analysis adherence |
| Security reviewer | Security-test coverage and threat-model alignment, per [../03-security/security-testing-and-assurance.md](../03-security/security-testing-and-assurance.md) |
| Privacy reviewer | Privacy-test coverage and classification-handling correctness |
| Data reviewer | Data-quality and data-migration test coverage |
| DevOps reviewer | Environment, CI, and operational-readiness quality (anticipated, not yet built) |
| Sports-rule validator | Confirms sport-specific expected outcomes against approved rule sources — never invented by engineering |
| Tournament-manager validator | Confirms tournament/bracket/schedule workflow correctness against real operational practice |
| Technical-official validator | Confirms scoring/results/protest workflow correctness against real officiating practice |
| Committee representative | Confirms their committee's specific workflow (per [pilot-operational-and-stakeholder-validation.md, Section 3](pilot-operational-and-stakeholder-validation.md#3-committee-workflow-validation)) |
| UAT coordinator | Facilitates user acceptance testing per [regression-smoke-exploratory-and-uat-strategy.md](regression-smoke-exploratory-and-uat-strategy.md) |
| Release approver | Final release sign-off authority, per [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md) |
| Pilot coordinator | Leads the controlled pilot per [pilot-operational-and-stakeholder-validation.md](pilot-operational-and-stakeholder-validation.md) |

No names are assigned — every role is "to be identified," consistent with every prior phase's governance treatment (see [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md)).

## 2. Quality Ownership Model

| Responsibility | Owner |
|---|---|
| Requirement quality | Product owner + Domain owner |
| Acceptance criteria | Domain owner + QA lead, per [requirements-traceability-model.md](requirements-traceability-model.md) |
| Unit tests | Implementing engineer |
| Feature tests | Implementing engineer, reviewed by technical lead |
| Integration tests | Implementing engineer + QA lead |
| Contract tests | QA lead + the API/integration owner |
| Security tests | Security reviewer + QA lead |
| Privacy tests | Privacy reviewer + QA lead |
| Accessibility tests | QA lead + a designated accessibility reviewer |
| Performance tests | QA lead + technical lead |
| Test data | QA lead, governed by [test-data-fixture-and-scenario-strategy.md](test-data-fixture-and-scenario-strategy.md) |
| Test environments | DevOps reviewer + QA lead |
| Defect triage | QA lead, per [defect-triage-root-cause-and-quality-debt.md](defect-triage-root-cause-and-quality-debt.md) |
| UAT | UAT coordinator |
| Pilot validation | Pilot coordinator |
| Release sign-off | Release approver, informed by every role above |
| Production monitoring | DevOps reviewer (a future operational capability) |
| Incident regression tests | Implementing engineer, mandated by [defect-triage-root-cause-and-quality-debt.md](defect-triage-root-cause-and-quality-debt.md) |

## 3. Verification Versus Validation

### Verification — "Was PMMS built according to specified requirements and architecture?"

Examples: automated tests, static analysis, contract checks, authorization tests, data-integrity tests, performance tests. Verification is largely automatable and answers a technical question against a known specification.

### Validation — "Does PMMS solve the real operational problem correctly?"

Examples: tournament-manager review, technical-official validation, committee workflow simulation, a pilot meet, user acceptance testing, public-portal usability review, field-device validation. Validation is largely human-facilitated and answers an operational question that no specification alone can fully capture.

**Both are required — neither substitutes for the other.** A feature can pass every automated verification test while still failing validation (e.g., a technically-correct bracket-generation algorithm that a Tournament Manager recognizes as producing an operationally awkward schedule). Release readiness (per [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md)) requires evidence of both.

## 4. Escalation

A quality disagreement (e.g., a domain owner and QA lead disagreeing on acceptance-criteria completeness) escalates to the Quality owner; a security/privacy-relevant disagreement escalates to the Security/Privacy reviewer per [../03-security/security-architecture.md, Section 3](../03-security/security-architecture.md#3-security-governance-model); a sports-rule disagreement escalates to the Sports-rule validator — never resolved by whichever engineer is under the most delivery pressure at the time.

## 5. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably whether a dedicated QA lead role exists from the start of implementation or is filled by rotating engineering responsibility initially.
