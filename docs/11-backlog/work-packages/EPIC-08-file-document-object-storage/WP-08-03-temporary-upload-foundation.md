# WP-08-03 — Temporary Upload Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-03 | Title | Temporary Upload Foundation |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 68 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the upload flow: a file is written to object storage under a temporary key, validated (WP-08-04), and only then promoted to a permanent `files` metadata record — avoiding orphaned or unvalidated content ever being treated as a permanent, trusted file.

## 3. Architecture Sources

[../../../../02-data/](../../../../02-data/).

## 4. Scope

Implement `UploadFileCommand` (proposed): accepts a file, writes to a temporary object-storage location, validates (delegating to WP-08-04), then either promotes to a permanent `files` record or discards.

## 5. Explicit Exclusions

Does not implement classification/validation logic itself (WP-08-04); does not implement authorized download (WP-08-06).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-02 | Hard |

## 7. Current-State Inspection

No upload handling exists.

## 8. Proposed Implementation Direction

Proposed `UploadFileCommand`/`UploadFileHandler`, writing to a `tmp/` prefix in object storage before promotion, per a two-phase commit pattern (write-then-promote, never write-directly-to-permanent-location).

## 9. Database Changes

Database Changes: None beyond WP-08-02's `files` table (this work package populates it, doesn't add columns).

## 10. Backend Requirements

`UploadFileCommand`/`UploadFileHandler`; a scheduled cleanup for orphaned temporary uploads (proposed, wired once WP-09-06's scheduler exists) that never got promoted.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly in this work package — who may upload is a future context-specific concern.

## 14. Security Requirements

Temporary uploads must expire/be cleaned up if never promoted (avoiding unbounded temporary-storage growth and reducing the window an unvalidated file exists).

## 15. Privacy and Data-Governance Requirements

Not Applicable directly at this stage.

## 16. Audit and Activity Events

Upload completion (promotion) is audit-worthy once WP-08-10 wires it.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package — synchronous in Phase 1 for simplicity; a queued upload-processing pipeline is a future enhancement.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit/integration tests: successful upload promotes correctly; a failed validation discards the temporary upload without creating a `files` record; orphaned temporary uploads are identified for cleanup.

## 21. Test Data Requirements

Synthetic test files only, not retained.

## 22. Documentation Updates

Record the two-phase upload pattern in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `UploadFileCommand`/`UploadFileHandler` with two-phase write-then-promote pattern | WP-08-02 complete | Unit tests pass |
| TASK-02 | Implement orphaned-temporary-upload identification logic | TASK-01 | Test confirms identification |

## 24. Acceptance Criteria

- **AC-01:** Given a valid upload, when processed, then it is promoted to a permanent `files` record.
- **AC-02:** Given a failed validation, when it occurs, then no `files` record is created and the temporary object is not treated as permanent.
- **AC-03:** Given an orphaned temporary upload past a reasonable age threshold, when checked, then it is identifiable for cleanup.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for all three criteria.

## 28. Rollback and Recovery Considerations

Failed uploads are self-cleaning (never promoted); a promoted file can be handled by WP-08-07's replacement mechanism if needed.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-03-01 | Unbounded temporary-storage growth if cleanup is never actually scheduled | Medium | Explicitly flagged as dependent on WP-09-06's scheduler; tracked until wired | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-03 — Temporary Upload Foundation.

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
- Files must never be treated as permanent until explicitly promoted after validation.
```
