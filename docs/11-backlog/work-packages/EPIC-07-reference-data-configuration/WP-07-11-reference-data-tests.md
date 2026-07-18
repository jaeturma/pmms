# WP-07-11 — Reference Data Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-11 | Title | Reference Data Tests |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 65 (closes EPIC-07) |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | QA lead |

## 2. Purpose

Closes EPIC-07 with a consolidated regression pass across every reference-data table and the admin UI, confirming the epic's collective behavior (especially the `sports` exclusion from WP-07-10) holds together.

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/).

## 4. Scope

Run the full EPIC-07 test suite; confirm every reference type follows WP-07-01's pattern consistently; confirm WP-07-04's sports skeleton remains rule-free and admin-UI-inaccessible.

## 5. Explicit Exclusions

Does not add new production code beyond fixing any gap found.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-07-01 through WP-07-10 | Hard |

## 7. Current-State Inspection

All EPIC-07 work packages' individual test suites.

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

Confirms WP-07-10's allow-list holds under the full regression pass.

## 14. Security Requirements

Not Applicable beyond re-confirming WP-07-10's sports-exclusion test.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Confirms audit wiring for every reference-data mutation.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

This work package *is* the testing requirement.

## 21. Test Data Requirements

Reuses all EPIC-07 factories. Do not create new test data.

## 22. Documentation Updates

Record final EPIC-07 test-coverage summary in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Run full EPIC-07 test suite | WP-07-01..10 complete | Output captured |
| TASK-02 | Fix any gap found | TASK-01 | Fix verified |

## 24. Acceptance Criteria

- **AC-01:** Given the full EPIC-07 test suite, when run, then 100% passes.
- **AC-02:** Given `sports`, when re-tested against the admin UI, then it remains inaccessible.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-07-01 through WP-07-10 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full test-run output.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-11-01 | An individual work package's test passes in isolation but conflicts once the full suite runs together (e.g., a shared seeder collision) | Low | Full-suite run explicitly catches this class of issue | QA lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-11 — Reference Data Tests.

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
