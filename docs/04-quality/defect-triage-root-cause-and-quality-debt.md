# PMMS Defect Management, Triage, Root-Cause Analysis, and Quality Debt

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [risk-based-testing-model.md](risk-based-testing-model.md) · [quality-governance-and-ownership.md](quality-governance-and-ownership.md) · [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md)

This document defines defect recording, severity/priority classification, triage process, root-cause analysis, flaky-test management, and quality-debt tracking. **No defect-tracking tool or ticket template is configured here.**

---

## 1. Defect Management

Every defect record captures:

Defect ID · title · environment · build · context (what was being done) · severity · priority · steps to reproduce · expected outcome · actual outcome · evidence (logs/screenshots, never exposing protected data) · data used (confirming synthetic, not real) · security or privacy classification (if applicable) · owner · status · root cause · fix version · verification · regression test (the test added/updated to catch a recurrence).

## 2. Severity Model

| Level | Illustrative Triggers |
|---|---|
| **Critical** | Safety impact · data breach · unauthorized access to Restricted/Highly Restricted data · a wrong official result or medal tally · a financial error · public misinformation · a meet-stopping failure · data loss with no available recovery |
| **High** | A significant functional failure with a difficult or unavailable workaround, in a High-tier area per [risk-based-testing-model.md](risk-based-testing-model.md) |
| **Moderate** | A real defect with an available, reasonable workaround, or confined to a Moderate-tier area |
| **Low** | A cosmetic or minor usability issue with negligible operational consequence |

Severity considers: safety, data breach, unauthorized access, wrong official result, wrong medal tally, financial error, public misinformation, meet stoppage, data loss, and workaround availability — restated directly from the phase's working instructions. **No final service-level timeline is defined here** — response/resolution-time expectations per severity are a future operational-policy decision, tracked in [quality-open-decisions.md](quality-open-decisions.md).

## 3. Defect Triage

| Element | Definition |
|---|---|
| Participants | QA lead, the relevant domain owner, and (for security/privacy-relevant defects) the Security/Privacy reviewer |
| Frequency placeholder | Triage cadence (e.g., daily during active development, per-build during pilot) is an operational decision, not fixed here |
| Severity validation | The reporter's initial severity is confirmed or adjusted during triage |
| Priority | Fix urgency is assigned, distinct from severity — a Low-severity defect can still be High-priority if it's trivially fixable and blocking |
| Ownership | Every triaged defect has a named owner (role, per [quality-governance-and-ownership.md](quality-governance-and-ownership.md)) |
| Duplicate handling | Duplicate reports are correctly linked, not tracked as independent defects |
| Security escalation | A defect with security implications escalates into [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md) if it meets that document's incident criteria |
| Privacy escalation | Same, for privacy implications |
| Production incident linkage | A defect discovered via a production incident is linked to that incident's record |
| Release impact | Triage assesses whether the defect blocks an in-progress release, per [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md) |
| Deferred defects | A defect deliberately deferred (not fixed now) records why, by whom, and when it will be revisited |
| Risk acceptance | Deferring a Critical/High-severity defect requires explicit risk acceptance by the Release approver, never a silent default |

## 4. Root-Cause Analysis

Categories:

Requirement gap · rule-source gap · design flaw · authorization flaw · data-model flaw · concurrency flaw · integration flaw · environment flaw · test gap · training gap · operational process gap · vendor issue.

Every Critical (and, at Quality-owner discretion, High) severity defect requires a documented root cause from the categories above, plus a **preventive action** — restated as required, not optional, per the phase's working instructions ("Require preventive actions"). A "requirement gap" or "rule-source gap" root cause specifically routes back to [requirements-traceability-model.md](requirements-traceability-model.md) or [high-integrity-sports-workflow-testing.md, Section 1](high-integrity-sports-workflow-testing.md#1-governing-principle) respectively, closing the loop on why the gap existed in the first place.

## 5. Flaky-Test Management

| Step | Rule |
|---|---|
| Detect | Intermittent test failures are tracked, not dismissed as "just flaky" |
| Quarantine only temporarily | A flaky test may be quarantined (excluded from the blocking gate) only as a short-term measure, never a permanent fix |
| Owner | Every quarantined test has a named owner responsible for resolving it |
| Root cause | The flakiness's actual cause (timing dependency, shared state, environment issue) is identified |
| Fix | The test (or the underlying code it's testing) is fixed to eliminate the flakiness |
| Re-enable | Once fixed and verified stable, the test returns to the blocking gate |
| Metrics | Flaky-test rate is tracked as a quality metric, per [quality-metrics-reporting-and-evidence.md](quality-metrics-reporting-and-evidence.md) |
| No permanent ignore | Restated absolutely — a test permanently excluded from the suite without resolution is quality debt (Section 6), not an acceptable steady state |
| No release trust in unstable critical suites | A release cannot rely on a Critical-tier test suite known to be flaky — restated as an absolute release-readiness gate, per [release-readiness-and-quality-signoff.md](release-readiness-and-quality-signoff.md) |

## 6. Quality Debt

Tracked categories: missing tests · fragile tests · slow tests · unverified rules (pending sports/policy source) · manual-only regression (not yet automated) · poor test data · environment gaps · missing observability · missing evidence · deferred accessibility · deferred performance work.

Every quality-debt entry records: **owner**, **impact** (what risk it represents while unresolved), **target resolution** (when it's expected to be addressed), and **acceptance** (an explicit decision that the debt is knowingly carried, by whom). Quality debt is not inherently forbidden — some is a reasonable, deliberate trade-off — but it must be visible and owned, never silently accumulated.

## 7. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably specific severity-to-response-time mapping and the defect-tracking tool selection (a Phase 0.8+ decision).
