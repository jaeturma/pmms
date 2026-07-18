# PMMS Quality Metrics, Reporting, and Evidence

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [defect-triage-root-cause-and-quality-debt.md](defect-triage-root-cause-and-quality-debt.md) · [../03-security/security-metrics-monitoring-and-reporting.md](../03-security/security-metrics-monitoring-and-reporting.md) · [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md)

This document defines candidate quality metrics, coverage strategy, static-analysis/mutation-testing readiness, test evidence, and quality-reporting structure. **No dashboard, reporting tool, or CI configuration is created here.**

---

## 1. Quality Metrics (Candidates)

Pass rate · failure rate · flaky-test rate · defect escape rate (defects found in production vs. pre-release) · reopened defects · Critical defect count · mean defect age · automation coverage by risk tier · requirement traceability (percentage of acceptance criteria with a linked test) · authorization coverage (percentage of permissions with positive+negative tests) · audit coverage (percentage of audit-event categories with a verifying test) · privacy coverage · test execution duration · queue reliability · sync success rate · restore success rate · public projection discrepancy rate · medal tally discrepancy rate · result publication error rate · UAT completion rate · pilot satisfaction · incident regression coverage (percentage of production incidents with a corresponding new test).

**All targets remain placeholders** — restated absolutely per the phase's working instructions; no numeric target is set for any metric above until real operational/pilot data justifies one.

## 2. Coverage Strategy

**Avoid using code coverage as the sole quality metric** — restated absolutely from working rule 28. PMMS uses multiple coverage dimensions together:

Code coverage · domain-rule coverage · workflow coverage · state-transition coverage · permission coverage · scope coverage · data-classification coverage · error-path coverage · concurrency coverage · rule-version coverage · browser and device coverage · requirement coverage.

A module with 100% code coverage but zero negative-path or authorization-denial tests is not well-tested by PMMS's standard — coverage is one input among several (per [quality-engineering-strategy.md, Section 3](quality-engineering-strategy.md#3-quality-principles), "coverage is evidence, not proof"), and **no single percentage is required as the definition of quality** for any module, restated per the phase's working instructions.

## 3. Static Analysis and Architecture Assurance

Future use of: PHP static analysis (Larastan, already present) · TypeScript checks (already present) · Flutter analysis (once `mobile/` exists) · formatting (Pint/Prettier, already present) · linting (ESLint, already present) · architecture tests (module dependency-direction rules) · dependency rules (forbidden cross-context imports) · forbidden-pattern checks (e.g., no direct cross-context ORM relationships, per [../01-architecture/internal-integration-architecture.md](../01-architecture/internal-integration-architecture.md)) · secret scanning · dependency scanning · dead-code detection · migration review (once physical schema exists).

Architecture tests deserve particular emphasis for PMMS given its modular-monolith structure — a test that confirms "no Infrastructure-layer class is referenced from a Domain-layer class" catches an entire category of boundary violations no functional test would ever surface.

## 4. Mutation-Testing Readiness

Evaluated (not mandated for every module) for the areas where subtle logic errors carry the highest consequence:

Domain rules · permission logic · result calculations · medal calculations · eligibility completeness · state transitions.

Mutation testing (deliberately introducing small code changes and confirming the test suite catches them) is a candidate technique for validating that Critical-tier test suites are actually effective, not merely present — **not required for every module initially**, restated per the phase's working instructions; adoption is evaluated once the Critical-tier domains have a mature baseline test suite to mutate against.

## 5. Test Evidence

Evidence types: test reports · coverage reports · screenshots where appropriate · logs · API traces · performance reports · security reports · accessibility reports · UAT forms · pilot reports · defect records · reconciliation reports · restore reports · sign-off records.

**Evidence must avoid exposing protected data** — restated absolutely; a screenshot, log excerpt, or API trace used as evidence is reviewed for Restricted/Highly Restricted content before being attached to any evidence record, consistent with [../03-security/audit-and-security-event-architecture.md, Section 9](../03-security/audit-and-security-event-architecture.md#9-logging-and-monitoring-boundaries)'s redaction discipline applied to quality evidence specifically.

## 6. Quality Reporting

| Report | Audience | Content |
|---|---|---|
| Daily engineering quality | Technical lead, QA lead | Fast-suite pass rate, new defects, flaky-test signals |
| Sprint or work-package quality | Product owner, Quality owner | Acceptance-criteria completion, defect trend |
| Release readiness | Release approver | Per [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md) |
| Security assurance | Security reviewer | Per [../03-security/security-metrics-monitoring-and-reporting.md, Section 3](../03-security/security-metrics-monitoring-and-reporting.md#3-compliance-reporting) |
| Privacy assurance | Privacy reviewer | Privacy-test coverage and findings |
| Performance | Technical lead, DevOps reviewer | Per [performance-load-concurrency-and-capacity-testing.md](performance-load-concurrency-and-capacity-testing.md) |
| Defects | QA lead, Quality owner | Open/closed/reopened trend by severity |
| UAT | UAT coordinator, Product owner | Completion and findings, per [regression-smoke-exploratory-and-uat-strategy.md, Section 5](regression-smoke-exploratory-and-uat-strategy.md#5-user-acceptance-testing) |
| Pilot | Pilot coordinator, DepEd Leadership | Per [pilot-operational-and-stakeholder-validation.md, Section 1](pilot-operational-and-stakeholder-validation.md#1-pilot-validation) |
| Operational readiness | DevOps reviewer, Quality owner | Per [pilot-operational-and-stakeholder-validation.md, Section 2](pilot-operational-and-stakeholder-validation.md#2-operational-readiness-testing) |
| Post-release quality | Quality owner | Defect-escape and incident trend after each release |
| Incident trends | Security reviewer, Quality owner | Cross-referencing [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md) |
| Quality debt | Quality owner | Per [defect-triage-root-cause-and-quality-debt.md, Section 6](defect-triage-root-cause-and-quality-debt.md#6-quality-debt) |

**No specific reporting cadence or distribution mechanism is fixed here** — candidate report types only, mirroring the equivalent discipline in [../03-security/security-metrics-monitoring-and-reporting.md, Section 3](../03-security/security-metrics-monitoring-and-reporting.md#3-compliance-reporting).

## 7. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably whether mutation testing is adopted for any module before Phase 0.8, and specific metric-target thresholds once pilot data is available.
