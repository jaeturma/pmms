# WP-10-10 — Frontend Integration Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-10 | Title | Frontend Integration Tests |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 98 (closes EPIC-10) |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | QA lead |

## 2. Purpose

Closes EPIC-10 with a consolidated pass confirming WP-10-01 through WP-10-09's contracts work together consistently — a request that fails validation, is denied by authorization, or succeeds, each produces the correctly-shaped response the frontend conventions expect.

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/).

## 4. Scope

Run the full EPIC-10 test suite; build one integration test exercising the full request lifecycle (validation failure → fix → authorization check → success → conflict on resubmit) against a representative endpoint (reusing WP-05-11's assignment admin page as the concrete example).

## 5. Explicit Exclusions

Does not add new production code beyond fixing any gap found.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-01 through WP-10-09 | Hard |

## 7. Current-State Inspection

All EPIC-10 work packages' individual test suites, plus WP-05-11 as the first real consumer.

## 8. Proposed Implementation Direction

Test-only work package.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable beyond test code.

## 11. Web Frontend Requirements

Not Applicable beyond test code.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Confirms WP-10-05's advisory-only capability contract holds under the full lifecycle test.

## 14. Security Requirements

Confirms WP-10-01's no-stack-trace-in-production rule holds under the full lifecycle test.

## 15. Privacy and Data-Governance Requirements

Confirms WP-10-04's minimization rule holds under the full lifecycle test.

## 16. Audit and Activity Events

Not Applicable beyond what earlier work packages established.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

This work package *is* the testing requirement.

## 21. Test Data Requirements

Reuses all EPIC-10 and WP-05-11 factories. Do not create new production-like test data.

## 22. Documentation Updates

Record final EPIC-10 test-coverage summary in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Run full EPIC-10 test suite | WP-10-01..09 complete | Output captured |
| TASK-02 | Build and run the full-lifecycle integration test | TASK-01 | Test passes |
| TASK-03 | Fix any gap found | TASK-01..02 | Fix verified |

## 24. Acceptance Criteria

- **AC-01:** Given the full EPIC-10 test suite, when run, then 100% passes.
- **AC-02:** Given the full-lifecycle integration test (validation → authorization → success → conflict), when run against WP-05-11's admin page, then every stage produces the contractually correct response shape.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-01 through WP-10-09 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full test-run output.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-10-01 | Individually-passing contracts conflict once exercised together (e.g., pagination + filter + conflict-check interaction) | Medium | The full-lifecycle test explicitly exercises this combination | QA lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-10 — Frontend Integration Tests.

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
- Any gap found must be fixed and re-verified, not merely reported.
```
