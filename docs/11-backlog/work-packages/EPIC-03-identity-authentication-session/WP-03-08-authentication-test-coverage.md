# WP-03-08 — Authentication Test Coverage

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-08 | Title | Authentication Test Coverage |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 24 (closes EPIC-03) |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | QA lead, Security reviewer |

## 2. Purpose

Consolidates and closes gaps in EPIC-03's test coverage across WP-03-01 through WP-03-07, ensuring the full authentication foundation is regression-safe before EPIC-04/05 build on top of it.

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/).

## 4. Scope

Run the full `tests/Feature/Auth/*` and `tests/Feature/Settings/*` suite plus every new test added by WP-03-01 through WP-03-07; identify and close any coverage gap (e.g., concurrent status-transition race, 2FA + suspended-account interaction).

## 5. Explicit Exclusions

Does not add authorization tests (EPIC-05's WP-05-12 scope); does not add audit-integrity tests beyond WP-06-09's scope.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-01 through WP-03-07 | Hard |

## 7. Current-State Inspection

All tests added across WP-03-01 through WP-03-07, plus the original starter-kit suite.

## 8. Proposed Implementation Direction

Test-only work package; no new production code beyond gap-closing tests identified during review.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable beyond test code.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable — deferred to EPIC-05.

## 14. Security Requirements

Confirm no test leaves a suspended/revoked test user able to authenticate (regression guard).

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Confirm every WP-03-07 listener has at least one passing test.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Full EPIC-03 regression pass; gap analysis for 2FA + status-transition interaction; gap analysis for concurrent status-transition handling.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data beyond factory states already established.

## 22. Documentation Updates

Record final EPIC-03 test-coverage summary in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Run full EPIC-03 test suite | WP-03-01..07 complete | Output captured |
| TASK-02 | Identify and close any coverage gap | TASK-01 | Gap list closed or explicitly deferred with reason |
| TASK-03 | Record summary in `.ai/engineering-baseline.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the full EPIC-03 test suite, when run, then 100% of tests pass.
- **AC-02:** Given a 2FA-enabled, suspended account, when a login is attempted, then it is denied before the 2FA challenge is reached.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-01 through WP-03-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Full suite passes; gap analysis documented.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full test-run output.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-08-01 | A 2FA + suspended-account edge case allows partial authentication | High | AC-02 explicitly tests this ordering | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-08 — Authentication Test Coverage.

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
- Do not add authorization-model tests — that is EPIC-05's scope.
```
