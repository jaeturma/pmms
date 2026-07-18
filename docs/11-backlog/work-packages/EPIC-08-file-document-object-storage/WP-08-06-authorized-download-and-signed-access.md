# WP-08-06 — Authorized Download and Signed Access

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-08-06 | Title | Authorized Download and Signed Access |
| Epic | EPIC-08 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 71 |
| Target release group | Foundation Release C | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-30 Document and Records |
| Secondary affected contexts | BC-32 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the download path: every file access is authorized via WP-05-07's Authorization Decision Service before a short-lived signed URL is issued — no file is ever served from a public/predictable URL, and every download is a recordable, audited event per WP-06-05's sensitive-view convention.

## 3. Architecture Sources

ADR-0006, ADR-0012.

## 4. Scope

Implement `FileDownloadController` (proposed): checks `AuthorizationDecisionService::can()` for a file-scoped permission, then issues a short-lived signed URL (`Storage::temporaryUrl()`) rather than streaming directly or exposing a permanent link.

## 5. Explicit Exclusions

Does not implement bulk/zip download; does not implement public (unauthenticated) file access for any Phase 1 capability.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-08-05, WP-05-07, WP-06-05 | Hard |

## 7. Current-State Inspection

No download controller exists.

## 8. Proposed Implementation Direction

`FileDownloadController::show(FileMetadata $file)`, checking authorization, then generating a signed URL with a short expiry (proposed: 5 minutes), redirecting the client to it rather than proxying the download through the application server.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Controller with authorization check + signed-URL generation; recording a sensitive-view audit event (WP-06-05) on every access grant.

## 11. Web Frontend Requirements

Not Applicable in this work package (a download link/button belongs to whichever future context first needs file display).

## 12. Flutter Requirements

Not Applicable directly — mobile file access follows the same controller via the API.

## 13. Authorization and Access Control

Every download is gated through `AuthorizationDecisionService::can()` — the frontend never determines download eligibility.

## 14. Security Requirements

Signed URLs must be short-lived and single-purpose (scoped to one object); the controller must never expose `storage_key` directly to an unauthorized client, even in an error message.

## 15. Privacy and Data-Governance Requirements

Every download is a sensitive-view event per WP-06-05, recorded with actor and file classification.

## 16. Audit and Activity Events

Every successful download authorization is recorded as a `viewed.file` (or `exported.file`, per WP-06-05's convention) audit event.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature tests: authorized user receives a valid signed URL; unauthorized user is denied without receiving any URL or storage_key hint; signed URL expires after its window.

## 21. Test Data Requirements

Reuses `FileMetadataFactory`. Do not create new test data.

## 22. Documentation Updates

Record the download flow and its audit integration in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `FileDownloadController` with authorization check and signed-URL issuance | WP-08-05, WP-05-07 complete | Feature tests pass |
| TASK-02 | Wire sensitive-view audit recording into the download flow | WP-06-05 complete | Audit event confirmed on access |

## 24. Acceptance Criteria

- **AC-01:** Given an authorized user, when they request a file, then they receive a short-lived signed URL.
- **AC-02:** Given an unauthorized user, when they request a file, then they receive a permission-denied response with no `storage_key` or URL leaked.
- **AC-03:** Given a successful download authorization, when it occurs, then a sensitive-view audit event is recorded.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-08-05, WP-05-07, WP-06-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for all three criteria, example signed-URL format (redacted).

## 28. Rollback and Recovery Considerations

Not Applicable — new controller, no prior behavior.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-08-06-01 | An error response accidentally leaks the storage_key or a valid URL to an unauthorized requester | High | AC-02 explicitly tests the denial response content | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-08-06 — Authorized Download and Signed Access.

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
- Never expose storage_key or a valid signed URL to an unauthorized requester, including in error messages.
- Every download must be authorized through AuthorizationDecisionService, never the frontend alone.
```
