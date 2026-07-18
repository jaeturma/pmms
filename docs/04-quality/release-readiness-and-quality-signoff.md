# PMMS Release Readiness and Quality Sign-Off

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [quality-governance-and-ownership.md](quality-governance-and-ownership.md) · [automation-ci-and-quality-gates.md](automation-ci-and-quality-gates.md) · [../03-security/security-metrics-monitoring-and-reporting.md, Section 4](../03-security/security-metrics-monitoring-and-reporting.md#4-release-security-gates)

This document defines release-quality gates, the release sign-off process, and exception management. **No release-automation tooling is created here.**

---

## 1. Release Quality Gates

A release requires:

- Acceptance criteria met, per [requirements-traceability-model.md, Section 4](requirements-traceability-model.md#4-acceptance-criteria-standard).
- Critical tests pass — every Critical-tier area's test suite is green, with no known flakiness (per [defect-triage-root-cause-and-quality-debt.md, Section 5](defect-triage-root-cause-and-quality-debt.md#5-flaky-test-management)).
- No unresolved Critical defects.
- High-severity defects have an accepted disposition (fixed, or explicitly deferred with risk acceptance).
- Authorization reviewed — new/changed permissions and SoD implications confirmed.
- Privacy reviewed — new/changed personal-data handling confirmed against [../03-security/privacy-by-design-architecture.md](../03-security/privacy-by-design-architecture.md).
- Audit evidence complete — every new consequential action has a verified corresponding audit event.
- Performance risks reviewed — known performance-sensitive changes have been assessed, even where full load testing isn't yet possible.
- Backup readiness confirmed — new data categories are incorporated into backup coverage, per [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md).
- Rollback plan confirmed — a defined path exists to revert the release if needed.
- Monitoring ready — relevant metrics/alerts (per [quality-metrics-reporting-and-evidence.md](quality-metrics-reporting-and-evidence.md) and [../03-security/security-metrics-monitoring-and-reporting.md](../03-security/security-metrics-monitoring-and-reporting.md)) are in place for the changed area.
- Documentation current — architecture/data/security documentation reflects the release's changes, consistent with every prior phase's update-rules discipline.
- UAT complete, per [regression-smoke-exploratory-and-uat-strategy.md, Section 5](regression-smoke-exploratory-and-uat-strategy.md#5-user-acceptance-testing).
- Pilot sign-off where required, per [pilot-operational-and-stakeholder-validation.md](pilot-operational-and-stakeholder-validation.md).
- Known limitations documented — anything knowingly incomplete or deferred is explicit, never silently omitted.

This extends, and does not replace, the fifteen release-security gates already defined in [../03-security/security-metrics-monitoring-and-reporting.md, Section 4](../03-security/security-metrics-monitoring-and-reporting.md#4-release-security-gates) — both gate sets apply jointly to every release.

## 2. Release Sign-Off

### Roles

The Release approver holds final sign-off authority, informed by every Quality Governance role in [quality-governance-and-ownership.md, Section 1](quality-governance-and-ownership.md#1-quality-governance-roles-candidates-not-named-individuals) — Security/Privacy reviewers confirm their respective gates; Domain owners confirm their business-rule correctness; the QA lead confirms test-evidence completeness; the UAT/Pilot coordinators confirm their respective validations.

### Decision Categories

| Decision | Meaning |
|---|---|
| **Approved** | Every gate is satisfied; the release proceeds without qualification |
| **Approved with accepted risk** | The release proceeds, with one or more specifically named, deliberately-accepted risks documented and owned |
| **Conditionally approved** | The release proceeds only once specific, named conditions are met (e.g., a follow-up fix within a defined window) |
| **Rejected** | The release does not proceed; blocking gaps are documented and returned to the team |
| **Deferred** | The release decision itself is postponed pending more information (e.g., awaiting pilot results) |

**No single person should unilaterally waive all critical quality controls** — restated absolutely per the phase's working instructions; an "Approved with accepted risk" or "Conditionally approved" decision touching a Critical-tier gate requires the concurrence of the relevant Security/Privacy/Domain reviewer, not the Release approver's judgment alone.

## 3. Exception Management

A quality-gate exception (e.g., shipping with a known, accepted High-severity defect) is:

1. **Explicitly requested**, naming the specific gate and the specific gap.
2. **Risk-assessed**, using [risk-based-testing-model.md](risk-based-testing-model.md)'s dimensions.
3. **Time-bounded**, with a named target-resolution date — never an indefinite, open-ended exception.
4. **Owned**, by a specific named role accountable for eventual resolution.
5. **Documented** as quality debt (per [defect-triage-root-cause-and-quality-debt.md, Section 6](defect-triage-root-cause-and-quality-debt.md#6-quality-debt)) if it persists beyond the immediate release.
6. **Visible** in the release sign-off record — never a private, undocumented understanding between two individuals.

An exception touching a Critical-tier high-integrity domain (eligibility, scoring, results, protests, medal tally, accreditation, medical, finance, audit, authorization) requires the same elevated concurrence as any Critical-tier gate waiver in Section 2.

## 4. Distinguishing Compliance Evidence from Compliance Claims (Restated)

Restated absolutely from working rule 39 and [../03-security/compliance-control-framework.md](../03-security/compliance-control-framework.md): a release sign-off record documents that specific controls were verified and specific evidence was produced — it never states or implies that PMMS is thereby "compliant" with any law, regulation, or standard. The sign-off record uses the same compliance-language discipline established in [../03-security/security-architecture.md, Section 6](../03-security/security-architecture.md#6-compliance-language-discipline).

## 5. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably the specific release cadence (continuous delivery vs. scheduled releases) once implementation begins, which shapes how frequently this sign-off process actually executes.
