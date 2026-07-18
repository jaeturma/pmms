# WP-08-10 — File Audit Events

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-10 | Title | File Audit Events |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 75 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | BC-32 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Wires WP-08-03's upload, WP-08-06's download, and WP-08-07's replacement into WP-06-06's `AuditRecorder`, closing the loop between EPIC-08's file operations and EPIC-06's audit foundation — the concrete cross-epic integration WP-06-05's convention anticipated.

## 3. Architecture Sources

ADR-0006.

## 4. Scope

Add `AuditRecorder` calls to `UploadFileHandler`, `FileDownloadController`, and `ReplaceFileHandler`, using WP-06-05's `viewed.*`/`exported.*` naming convention for downloads and standard audit events for upload/replace.

## 5. Explicit Exclusions

Does not implement any new event type beyond what WP-06-01/06-03 already support; does not change WP-06-06's recording service itself.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-02, WP-08-03, WP-08-06, WP-06-06 | Hard |

## 7. Current-State Inspection

WP-08-03/06/07's handlers exist without audit wiring by this point in the sequence (built before WP-06-06 existed, following the same "build first, retrofit once dependency exists" pattern as WP-03-07/WP-06-06).

## 8. Proposed Implementation Direction

Retrofit each handler to call `AuditRecorder::recordAuditEvent()` at the appropriate point.

## 9. Database Changes

Database Changes: None (uses existing `audit_events` table).

## 10. Backend Requirements

Retrofit of three existing handlers.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Not Applicable beyond what WP-06-06 already establishes.

## 15. Privacy and Data-Governance Requirements

Every file operation is now traceable to an actor, satisfying WP-06-05's sensitive-view/export convention concretely for the first time.

## 16. Audit and Activity Events

This work package **is** the concrete wiring of file-operation audit events.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature tests confirming upload/download/replace each produce a correctly-typed audit event; existing WP-08-03/06/07 tests re-run to confirm no regression.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the audit wiring in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Wire `AuditRecorder` into `UploadFileHandler`, `FileDownloadController`, `ReplaceFileHandler` | WP-08-03, WP-08-06, WP-06-06 complete | Feature tests pass |
| TASK-02 | Re-run existing WP-08-03/06/07 tests to confirm no regression | TASK-01 | All pass |

## 24. Acceptance Criteria

- **AC-01:** Given a file upload, download, or replacement, when each occurs, then a correctly-typed audit event is recorded.
- **AC-02:** Given the pre-existing WP-08-03/06/07 test suites, when re-run after this retrofit, then they still pass.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-02, WP-08-03, WP-08-06, WP-06-06 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, example audit event (redacted).

## 28. Rollback and Recovery Considerations

Retrofit is additive; reversible by removing the recording calls if a regression is found.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-10-01 | Retrofit introduces a regression in the existing upload/download flow | Medium | AC-02 explicitly re-runs prior tests | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-10 — File Audit Events.

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
- Re-run all pre-existing EPIC-08 tests to confirm no regression from this retrofit.
```
