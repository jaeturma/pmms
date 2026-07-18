# WP-08-11 — File Security and Integration Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-11 | Title | File Security and Integration Tests |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 76 (closes EPIC-08) |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, QA lead |

## 2. Purpose

Closes EPIC-08 with a comprehensive security-focused regression pass: confirms tenant isolation (WP-08-05), authorization (WP-08-06), and validation (WP-08-04) hold together under combined attack-style scenarios, not just their individual unit tests.

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/).

## 4. Scope

Build integration tests combining: a malicious filename plus an unauthorized cross-organization download attempt; a validation-bypass attempt combined with a direct storage_key guess; run the full EPIC-08 test suite together.

## 5. Explicit Exclusions

Does not add new production code beyond fixing any gap found.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-01 through WP-08-10 | Hard |

## 7. Current-State Inspection

All EPIC-08 work packages' individual test suites.

## 8. Proposed Implementation Direction

Test-only work package.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable beyond test code and any gap-closing fix.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Confirms EPIC-08's authorization holds under combined-attack scenarios.

## 14. Security Requirements

This work package's entire purpose is security verification.

## 15. Privacy and Data-Governance Requirements

Confirms classification and sensitive-view auditing hold together end-to-end.

## 16. Audit and Activity Events

Confirms every EPIC-08 audit wiring point (WP-08-10) fires correctly under the combined test scenarios.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

This work package *is* the testing requirement.

## 21. Test Data Requirements

Reuses all EPIC-08 factories and synthetic test files. Do not create new production-like test data.

## 22. Documentation Updates

Record final EPIC-08 test-coverage summary in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Build combined attack-style integration tests | WP-08-01..10 complete | Tests implemented |
| TASK-02 | Run full EPIC-08 suite together | TASK-01 | 100% pass |
| TASK-03 | Fix any gap found | TASK-02 | Fix verified |

## 24. Acceptance Criteria

- **AC-01:** Given a combined malicious-filename-plus-cross-organization-download attempt, when tested, then it is fully denied at every layer.
- **AC-02:** Given the full EPIC-08 test suite, when run together, then 100% passes.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-01 through WP-08-10 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full test-run output.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-11-01 | A combined-attack scenario reveals a gap that individual unit tests missed | High | This is exactly why this work package exists — any finding is fixed and re-verified before closure | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-11 — File Security and Integration Tests.

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
