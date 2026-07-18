# WP-08-02 — File Metadata Model Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-02 | Title | File Metadata Model Foundation |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 67 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Data owner |

## 2. Purpose

Implements the `files` metadata table — MySQL remains authoritative for *metadata* (filename, size, owner, classification), while MinIO/object storage remains authoritative only for *content*, per ADR-0005's explicit ownership split, the second-most-repeated data rule in the corpus after the audit/activity/security distinction.

## 3. Architecture Sources

[../../../../02-data/](../../../../02-data/), ADR-0005.

## 4. Scope

Implement `files` table (id, original_filename, storage_key, mime_type, size_bytes, uploaded_by_user_id, classification nullable, organization_id/meet_id per WP-04-08's tenant convention).

## 5. Explicit Exclusions

Does not implement upload handling itself (WP-08-03); does not implement classification enforcement logic (WP-08-04); does not store file content in MySQL (content lives only in object storage).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-01, WP-02-05 | Hard |

## 7. Current-State Inspection

No file-metadata table exists.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Document\FileMetadata` entity; `files` table with `storage_key` as the pointer into WP-08-01's `s3` disk (never the content itself).

## 9. Database Changes

Proposed `files` table: `id`, `original_filename`, `storage_key` (unique), `mime_type`, `size_bytes`, `uploaded_by_user_id` (FK), `organization_id` nullable FK, `meet_id` nullable FK, `classification` nullable, `created_at` (no `updated_at` for the core record — replacement is versioned per WP-08-07, not updated in place).

## 10. Backend Requirements

Domain: `FileMetadata` entity; Application: none yet (WP-08-03 provides the upload command).

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — access control is WP-08-06's scope.

## 14. Security Requirements

`storage_key` must never be predictable/guessable from the `original_filename` alone (avoid enumeration).

## 15. Privacy and Data-Governance Requirements

`classification` field exists from the start, even though full classification enforcement is WP-14-01's later scope — this table is classification-ready.

## 16. Audit and Activity Events

File metadata creation is audit-worthy once WP-08-10 wires it.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit test confirming `storage_key` generation is non-predictable (e.g., UUID-based, not derived from filename).

## 21. Test Data Requirements

New `FileMetadataFactory` (proposed).

## 22. Documentation Updates

Record the `files` table and the MySQL-metadata/MinIO-content ownership split in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `files` migration | WP-08-01, WP-02-05 complete | Migration reversible |
| TASK-02 | Implement `FileMetadata` entity with non-predictable key generation | TASK-01 | Unit test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a new file metadata record, when created, then `storage_key` is not derivable from `original_filename`.
- **AC-02:** Given the `files` table, when inspected, then no content/binary column exists — only metadata.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-01, WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output.

## 28. Rollback and Recovery Considerations

Migration reversible; no existing data.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-02-01 | A future work package stores file content directly in a database column, violating the ownership split | High | AC-02 is a standing regression guard; WP-15-02 database review re-checks | Data owner |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-02 — File Metadata Model Foundation.

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
- Never store file content in MySQL — metadata only, content lives in object storage.
- storage_key must not be derivable from the original filename.
```
