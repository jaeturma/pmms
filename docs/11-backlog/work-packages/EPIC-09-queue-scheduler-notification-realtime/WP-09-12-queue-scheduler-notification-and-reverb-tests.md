# WP-09-12 — Queue, Scheduler, Notification, and Reverb Tests

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-12 | Title | Queue, Scheduler, Notification, and Reverb Tests |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P2 | Implementation sequence | 88 (closes EPIC-09) |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | QA lead, DevOps lead |

## 2. Purpose

Closes EPIC-09 with a comprehensive integration pass across Redis/Queue/Horizon, Scheduler, Notifications, and Reverb together — confirming the full asynchronous/real-time stack works end-to-end (e.g., a role grant triggers a queued notification which is also broadcastable), not just each piece in isolation.

## 3. Architecture Sources

[../../../../04-quality/](../../../../04-quality/).

## 4. Scope

Run the full EPIC-09 test suite; build one end-to-end integration test spanning WP-05-05's grant → WP-09-07's notification → WP-09-02's queue processing; confirm WP-09-06's scheduler and WP-09-09/10's Reverb both remain healthy under this combined scenario.

## 5. Explicit Exclusions

Does not add new production code beyond fixing any gap found.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-01 through WP-09-11 | Hard |

## 7. Current-State Inspection

All EPIC-09 work packages' individual test suites.

## 8. Proposed Implementation Direction

Test-only work package.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable beyond test code and any gap-closing fix.

## 11. Web Frontend Requirements

Not Applicable beyond test code (WP-09-11's hook test, re-run).

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Confirms WP-09-10's private-channel authorization holds under the combined scenario.

## 14. Security Requirements

Confirms no queue/broadcast payload leaks sensitive data beyond what earlier work packages already established.

## 15. Privacy and Data-Governance Requirements

Not Applicable beyond what earlier work packages established.

## 16. Audit and Activity Events

Confirms the end-to-end scenario produces correctly correlated audit events (WP-06-04's correlation ID traced across the queue boundary).

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package *is* the closing verification of EPIC-09's entire surface.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Confirms WP-13-05/13-06's health signals (once EPIC-13 exists — if EPIC-13 has not yet completed at this point in the sequence, this check is deferred to WP-15-06).

## 20. Testing Requirements

This work package *is* the testing requirement.

## 21. Test Data Requirements

Reuses all EPIC-09 factories. Do not create new production-like test data.

## 22. Documentation Updates

Record final EPIC-09 test-coverage summary in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Run full EPIC-09 test suite | WP-09-01..11 complete | Output captured |
| TASK-02 | Build and run the grant-to-notification-to-queue end-to-end integration test | TASK-01 | Test passes |
| TASK-03 | Fix any gap found | TASK-01..02 | Fix verified |

## 24. Acceptance Criteria

- **AC-01:** Given the full EPIC-09 test suite, when run, then 100% passes.
- **AC-02:** Given a role grant, when it triggers a queued notification end-to-end, then the correlation ID is preserved from the original request through to the processed job.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-01 through WP-09-11 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full test-run output, end-to-end trace example (redacted).

## 28. Rollback and Recovery Considerations

Not Applicable — test-only work package.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-12-01 | Correlation ID is lost across the queue boundary, breaking end-to-end traceability | Medium | AC-02 explicitly tests this | DevOps lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-12 — Queue, Scheduler, Notification, and Reverb Tests.

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
