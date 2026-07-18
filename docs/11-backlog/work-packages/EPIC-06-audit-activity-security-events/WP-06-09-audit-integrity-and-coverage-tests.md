# WP-06-09 — Audit Integrity and Coverage Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-06-09 | Title | Audit Integrity and Coverage Tests |
| Epic | EPIC-06 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 54 (closes EPIC-06) |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-32 Audit and Compliance |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, QA lead |

## 2. Purpose

Closes EPIC-06 with a comprehensive integrity check: confirms every domain event dispatched so far in the backlog (WP-03-04's status transitions, WP-04-01/02/03/04's create/grant events, WP-05-05/09's assignment/hold events) actually produces the expected audit trail, catching any work package that forgot to wire its events into WP-06-06's recording service.

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/).

## 4. Scope

Build a coverage checklist cross-referencing every domain event proposed across WP-02-04 through WP-06-06 against actual audit-recording wiring; write integration tests confirming each fires correctly; confirm append-only immutability holds under concurrent access (two simultaneous writes never corrupt or lose an event).

## 5. Explicit Exclusions

Does not add coverage for events belonging to out-of-Phase-1-scope modules; does not implement any new recording logic beyond fixing gaps found.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-06-01 through WP-06-08 | Hard |

## 7. Current-State Inspection

Every domain event proposed by WP-02-04, WP-03-04, WP-04-01/02/03/04/07/08, WP-05-05/09 — cross-referenced against actual `AuditRecorder` usage.

## 8. Proposed Implementation Direction

Test-only work package; fixes any gap found (a missing recording call in an earlier work package's listener).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable beyond test code and gap fixes.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Confirms the append-only guarantee holds under concurrency, a genuine integrity property, not just a structural one.

## 15. Privacy and Data-Governance Requirements

Not Applicable beyond what earlier work packages established.

## 16. Audit and Activity Events

This work package is the closing verification of EPIC-06's entire event-recording surface.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

This work package *is* the testing requirement — the full coverage cross-reference and concurrency test.

## 21. Test Data Requirements

Reuses all existing factories. Do not create new production-like test data.

## 22. Documentation Updates

Record the coverage checklist and results in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Build the domain-event-to-audit-recording coverage checklist | WP-06-01..08 complete | Checklist documented |
| TASK-02 | Write integration tests confirming each event fires correctly | TASK-01 | All tests pass |
| TASK-03 | Fix any gap found and re-verify | TASK-02 | Fix verified |
| TASK-04 | Test append-only immutability under concurrent writes | TASK-01 | Concurrency test passes |

## 24. Acceptance Criteria

- **AC-01:** Given every domain event proposed across EPIC-02 through EPIC-06, when cross-referenced, then each has confirmed audit-recording wiring or is explicitly documented as out of Phase 1 scope.
- **AC-02:** Given two simultaneous writes to any of the three event tables, when they occur, then both are recorded without corruption or loss.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-06-01 through WP-06-08 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified; zero known coverage gap remains open.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the coverage checklist with results, concurrency-test output.

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package; any production fix follows its own owning work package's rollback plan.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-06-09-01 | A gap is found in a much earlier work package (e.g., WP-03-04), requiring a retroactive fix late in the sequence | Medium | Explicitly expected and planned for — this work package exists precisely to catch this | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-06-09 — Audit Integrity and Coverage Tests.

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
- Any coverage gap found must be fixed and re-verified, not merely reported.
```
