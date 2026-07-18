# WP-05-12 — Authorization Test Matrix

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-12 | Title | Authorization Test Matrix |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P2 | Implementation sequence | 45 (closes EPIC-05) |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, QA lead |

## 2. Purpose

Builds a comprehensive, systematic test matrix covering every combination of role/permission/scope/assignment-state/hold-state relevant to Phase 1's authorization surface — going beyond WP-05-01 through WP-05-11's individual unit tests to confirm the *combined* system behaves correctly, since authorization bugs often emerge only from interaction between otherwise-correct individual pieces.

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/), [../../../10-review/quality-testing-and-acceptance-readiness-review.md](../../../10-review/quality-testing-and-acceptance-readiness-review.md).

## 4. Scope

Build a data-driven test matrix (proposed: a Pest dataset) covering: valid/invalid role, valid/invalid permission, matching/mismatched scope, current/expired/revoked assignment, active/inactive/lifted hold — systematically, not just the handful of scenarios each individual work package already tested.

## 5. Explicit Exclusions

Does not add new production code beyond fixing any gap the matrix reveals; does not test sport/eligibility-specific authorization (out of Phase 1 scope).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-01 through WP-05-11 | Hard |

## 7. Current-State Inspection

All EPIC-05 work packages' individual test suites, as the baseline this matrix extends rather than duplicates.

## 8. Proposed Implementation Direction

A single comprehensive Pest test file (proposed `tests/Feature/Authorization/AuthorizationMatrixTest.php`) using a dataset provider covering the full combinatorial space at a representative sampling level (not exhaustive combinatorial explosion, but every meaningfully distinct category).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable beyond test code and any gap-closing fix.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This work package is the closing verification of EPIC-05's entire authorization model.

## 14. Security Requirements

Confirm fail-closed behavior holds across every tested combination, not just the individual scenarios each earlier work package tested in isolation.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Confirm every denial/allow decision tested is consistent with what WP-06-XX would record, if wired (cross-check, not a new integration).

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

This work package *is* the testing requirement — the full combinatorial matrix itself.

## 21. Test Data Requirements

Reuses all EPIC-05 factories. Do not create new production-like test data.

## 22. Documentation Updates

Record the matrix's coverage summary in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design the representative combinatorial coverage matrix | WP-05-01..11 complete | Matrix design documented |
| TASK-02 | Implement the matrix as a data-driven test suite | TASK-01 | All matrix cases pass |
| TASK-03 | Fix any gap the matrix reveals | TASK-02 | Fix verified, matrix re-run passes |

## 24. Acceptance Criteria

- **AC-01:** Given the full combinatorial matrix, when run, then every case resolves to the architecturally-correct allow/deny outcome.
- **AC-02:** Given any gap discovered during matrix construction, when found, then it is fixed and the fix is verified before this work package is marked done.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-01 through WP-05-11 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified; matrix coverage documented and reviewed.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full matrix test output, coverage summary.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package; any production fix follows its own owning work package's rollback plan.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-12-01 | Matrix design misses a meaningful category (e.g., multi-organization user), leaving a real gap uncovered | Medium | WP-15-03 Foundation Authorization Review is a second, independent check | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-12 — Authorization Test Matrix.

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
- Any gap discovered must be fixed and re-verified, not merely reported.
```
