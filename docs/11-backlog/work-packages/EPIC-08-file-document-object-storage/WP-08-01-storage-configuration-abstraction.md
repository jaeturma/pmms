# WP-08-01 — Storage Configuration Abstraction

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-01 | Title | Storage Configuration Abstraction |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 66 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | DevOps lead |

## 2. Purpose

Configures and verifies the existing `filesystems.php` `s3` disk (already present in the starter kit, supporting `AWS_ENDPOINT`/`AWS_USE_PATH_STYLE_ENDPOINT` for MinIO compatibility) against a real or local MinIO-compatible endpoint, confirming PMMS can actually read/write object storage before any domain file logic is built on top.

## 3. Architecture Sources

[../../../../02-data/](../../../../02-data/), approved technology direction (MinIO S3-compatible storage).

## 4. Scope

Verify/configure `AWS_*` environment variables for a MinIO-compatible endpoint; confirm `Storage::disk('s3')->put()/get()/delete()` work against it; document the local-vs-production storage configuration difference.

## 5. Explicit Exclusions

Does not provision a MinIO server/instance (an infrastructure/environment action, referenced in WP-01-06); does not implement any domain file model (WP-08-02).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-06 | Hard |

## 7. Current-State Inspection

`config/filesystems.php`'s `s3` disk already supports MinIO-compatible configuration (confirmed in WP-01-06's inspection); `.env.example`'s `AWS_*` variables are currently empty.

## 8. Proposed Implementation Direction

No code change to `filesystems.php` needed (already MinIO-ready); this work package is primarily configuration verification against a real endpoint, documented, not a new abstraction layer (the existing Laravel Storage facade is already the correct abstraction).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Confirm `Storage::disk('s3')` round-trips a test file correctly against the configured endpoint.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Confirm bucket access credentials are never logged or exposed in error messages.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly at this stage (no domain file content yet).

## 16. Audit and Activity Events

Not Applicable in this work package.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Storage connectivity check feeds WP-13-07's storage health endpoint.

## 20. Testing Requirements

Integration test confirming put/get/delete round-trip against the configured `s3` disk.

## 21. Test Data Requirements

A synthetic test file only, deleted after the round-trip test — not retained.

## 22. Documentation Updates

Record the verified storage configuration in `.ai/engineering-baseline.md`.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Configure `AWS_*` environment variables against a MinIO-compatible endpoint | WP-01-06 complete | Configuration documented |
| TASK-02 | Run put/get/delete round-trip test | TASK-01 | Test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a configured MinIO-compatible endpoint, when a test file is put/get/deleted via `Storage::disk('s3')`, then all three operations succeed.
- **AC-02:** Given a storage error (e.g., wrong credentials), when it occurs, then the credentials themselves are never included in the logged error message.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-06 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): round-trip test output.

## 28. Rollback and Recovery Considerations

Not Applicable — configuration verification only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-01-01 | No MinIO-compatible endpoint is available in the execution environment, blocking this and all downstream EPIC-08 work packages | High | Flagged explicitly as an environment precondition, same discipline as WP-01-05's Flutter SDK check | DevOps lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-01 — Storage Configuration Abstraction.

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
- Never log or expose storage credentials.
- Do not provision a MinIO server — configuration only, against an already-available endpoint.
```
