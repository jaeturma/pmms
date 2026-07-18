# WP-15-08 — Foundation Performance Baseline

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-08 | Title | Foundation Performance Baseline |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 151 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Records a measured performance baseline for the integrated foundation — representative request latencies, query counts per page, job throughput, memory footprint — so later modules and eventual load testing have a factual "before" reference. This is measurement, not optimization, and explicitly not a claim of meeting the Phase 0.12 performance budgets (which remain unvalidated targets pending real load testing).

## 3. Architecture Sources

[../../../09-enterprise/performance-budget-and-service-level-architecture.md](../../../09-enterprise/performance-budget-and-service-level-architecture.md), ADR-0012 (as reference targets only); [../../../10-review/architecture-review-methodology-and-evidence-model.md](../../../10-review/architecture-review-methodology-and-evidence-model.md) (evidence discipline).

## 4. Scope

Define and document the measurement protocol (environment spec, dataset size, warm/cold distinction); measure: latency percentiles for representative foundation endpoints (auth, dashboard/home, admin lists, context switch, file upload/download reference paths); per-page query counts (N+1 sweep on foundation pages, using the documented dataset); queue job processing baseline (dispatch-to-complete for representative jobs); memory/startup characteristics; compare informally against Phase 0.12 budget targets, recording deltas as observations (not pass/fail); N+1 or egregious findings filed as defects; baseline document for WP-15-12.

## 5. Explicit Exclusions

Does not load-test, stress-test, or simulate concurrency at scale (deferred enterprise scope, ED-decisions pending); does not optimize (findings route to owning work packages); does not validate or certify the Phase 0.12 performance budgets; does not measure production infrastructure (none exists).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-15-01 through WP-15-07 | Hard (baseline measured on the reviewed, defect-triaged foundation) |

## 7. Current-State Inspection

Measures the integrated foundation as it stands after the preceding reviews' defect triage.

## 8. Proposed Implementation Direction

Scripted measurement runs (documented invocations, repeatable); Laravel query-log/telescope-equivalent instrumentation in the measurement environment only; results table with environment disclosure.

## 9. Database Changes

Database Changes: None (measurement datasets in disposable databases).

## 10. Backend Requirements

Measurement execution; temporary instrumentation must not ship enabled.

## 11. Web Frontend Requirements

Page-level metrics (bundle size, initial-load timing) captured for the foundation shell as part of the baseline.

## 12. Flutter Requirements

App startup time and API round-trip baseline captured on one documented device class.

## 13. Authorization and Access Control

Measured flows run as realistic authorized users — authorization overhead is part of the baseline, not bypassed.

## 14. Security Requirements

No security control disabled for measurement; measurements with controls off would be fiction.

## 15. Privacy and Data-Governance Requirements

Synthetic datasets only.

## 16. Audit and Activity Events

Audit-write overhead included in measured flows (it is part of real cost).

## 17. Event, Queue, Notification, and Real-Time Requirements

Job throughput baseline included; Reverb connection-establishment timing optionally recorded.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

The baseline document itself becomes observability's reference point.

## 20. Testing Requirements

Repeatability check: protocol re-run produces results within documented variance.

## 21. Test Data Requirements

Documented synthetic dataset (sizes stated in the protocol) in disposable databases.

## 22. Documentation Updates

Baseline document (protocol, environment, results, observations, variance) to the sign-off evidence set.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Define measurement protocol and dataset | WP-15-01..07 complete | Protocol documented |
| TASK-02 | Execute latency/query-count/job/memory measurements | TASK-01 | Results recorded |
| TASK-03 | N+1 sweep; file findings | TASK-02 | Findings register complete |
| TASK-04 | Repeatability check; produce baseline document | TASK-02 | Variance documented |

## 24. Acceptance Criteria

- **AC-01:** Given the documented protocol, when measurements run, then every representative flow has recorded results with environment and dataset disclosed.
- **AC-02:** Given the N+1 sweep, when complete, then every foundation page has a recorded query count and egregious findings are filed as defects.
- **AC-03:** Given a protocol re-run, when compared, then results fall within the documented variance, or the variance is investigated and explained.
- **AC-04:** Given the baseline document, when reviewed, then Phase 0.12 budget comparisons are labeled observations — no pass/fail or compliance claim.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-15-01..07 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): the baseline document with raw result data.

## 28. Rollback and Recovery Considerations

Instrumentation removed after measurement; verified not shipped enabled.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-08-01 | Development-machine baseline mistaken for capacity evidence | Medium | Environment disclosure mandatory; document states explicitly what the baseline is not | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-08 — Foundation Performance Baseline.

Read the complete work-package document first.

Inspect the current repository before making changes.

Implement only the approved scope.

Do not implement excluded or deferred features.

Follow all linked architecture, security, privacy, testing, design, workflow, and operational rules.

Run the required tests and quality checks.

Update the required documentation and AI workspace files.

Do not commit unless explicitly instructed.

At completion, provide:
1. Repository findings
2. Files created
3. Files modified
4. Implementation summary
5. Database changes
6. Backend changes
7. Frontend changes
8. Flutter changes
9. Authorization and audit changes
10. Tests and quality checks
11. Risks and limitations
12. Git status

Additional restrictions specific to this work package:
- Measure, do not optimize — findings route to owning work packages.
- No load or concurrency testing — explicitly deferred.
- Never disable security or audit controls for measurement.
- Budget comparisons are observations, never compliance claims.
```
