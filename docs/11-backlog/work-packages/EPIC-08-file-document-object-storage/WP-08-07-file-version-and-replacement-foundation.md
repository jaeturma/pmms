# WP-08-07 — File Version and Replacement Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-07 | Title | File Version and Replacement Foundation |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 72 |
| Target release group | Foundation Release C | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Data owner |

## 2. Purpose

Implements file replacement as a new version rather than an in-place overwrite, per ADR-0005's append-only/version-chain discipline extended to files — replacing a document creates a new `files` row linked to the previous one, never destroys the old object.

## 3. Architecture Sources

ADR-0005.

## 4. Scope

Add `supersedes_file_id` nullable self-referencing FK to `files`; implement `ReplaceFileCommand` (proposed) creating a new version linked to the old, never deleting the prior object from storage.

## 5. Explicit Exclusions

Does not implement a UI for version history browsing; does not implement automatic old-version purging (retention-dependent, deferred).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-02, WP-08-03 | Hard |

## 7. Current-State Inspection

`files` table exists per WP-08-02, without version-chain support.

## 8. Proposed Implementation Direction

`supersedes_file_id` column added via follow-on migration; `ReplaceFileCommand` runs WP-08-03's upload flow, then links the new record to the prior one.

## 9. Database Changes

Proposed migration adding `supersedes_file_id` (nullable, self-referencing FK) to `files`.

## 10. Backend Requirements

`ReplaceFileCommand`/`ReplaceFileHandler`.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Replacement requires the same authorization as the original upload (reuses WP-08-06's pattern).

## 14. Security Requirements

Not Applicable beyond WP-08-04's validation, re-applied to the replacement.

## 15. Privacy and Data-Governance Requirements

Superseded versions remain subject to the same classification as their successor unless explicitly reclassified.

## 16. Audit and Activity Events

File replacement is audit-worthy once wired to WP-08-10.

## 17. Event, Queue, Notification, and Real-Time Requirements

`FileReplaced` domain event (proposed).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit tests: replacement creates a new record linked via `supersedes_file_id`; the old object remains in storage and its metadata record is not deleted.

## 21. Test Data Requirements

Reuses `FileMetadataFactory`. Do not create new test data.

## 22. Documentation Updates

Record the version-chain pattern in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Add `supersedes_file_id` migration | WP-08-02 complete | Migration reversible |
| TASK-02 | Implement `ReplaceFileCommand` | WP-08-03 complete | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a file replacement, when it occurs, then the new record links to the old via `supersedes_file_id`, and the old record/object are not deleted.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-02, WP-08-03 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output.

## 28. Rollback and Recovery Considerations

Migration reversible (drops the new column); no data loss risk since replacement is additive, not destructive.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-07-01 | A future implementation accidentally deletes the superseded object to save storage space | Medium | AC-01 is a standing regression guard | Data owner |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-07 — File Version and Replacement Foundation.

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
- Replacement must never delete the superseded file or its object.
```
