# WP-04-04 — User Meet Access Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-04 | Title | User Meet Access Foundation |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 28 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-04 Meet Administration |
| Secondary affected contexts | BC-02, BC-03 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Links a user's organization membership (WP-04-03) to specific meets (WP-04-02) they may access, the record EPIC-05's scope model and WP-11-05's context switcher both query.

## 3. Architecture Sources

[../../../../01-architecture/scope-model.md](../../../../01-architecture/scope-model.md).

## 4. Scope

Implement `user_meet_access` (user_id, meet_id, granted_at) recording which meets a user may operate within, distinct from role/permission (EPIC-05 answers "what can they do," this answers "where").

## 5. Explicit Exclusions

Does not implement the authorization decision itself (EPIC-05); does not implement any UI.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-02, WP-04-03 | Hard |

## 7. Current-State Inspection

No meet-access table exists.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Meet\UserMeetAccess` entity, ID-reference only to `User`/`Meet`.

## 9. Database Changes

Proposed `user_meet_access` table: `id`, `user_id` (FK), `meet_id` (FK), `granted_at`, `revoked_at` nullable, timestamps. Unique constraint: (`user_id`, `meet_id`) where `revoked_at IS NULL` (partial-uniqueness, documented as a proposed application-level check if the database engine doesn't support partial unique indexes). Index: both FK columns.

## 10. Backend Requirements

`GrantMeetAccessCommand`, `RevokeMeetAccessCommand` (proposed).

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This table is a scope-resolution input for EPIC-05, not itself an authorization decision.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Grant/revoke is audit-worthy once WP-06-06 exists.

## 17. Event, Queue, Notification, and Real-Time Requirements

`MeetAccessGranted`/`MeetAccessRevoked` domain events (proposed).

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1 (relevant to future mobile revocation-priority sync, EPIC-12 pilot phase).

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit/integration tests for grant/revoke and duplicate-active-access prevention.

## 21. Test Data Requirements

New `UserMeetAccessFactory` (proposed).

## 22. Documentation Updates

Record model in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `user_meet_access` migration | WP-04-02, WP-04-03 complete | Migration reversible |
| TASK-02 | Implement grant/revoke commands | TASK-01 | Tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a user with organization membership, when granted meet access, then it persists.
- **AC-02:** Given revoked meet access, when checked, then it is distinguishable from never-granted access (audit trail preserved, not deleted).

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-02, WP-04-03 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output.

## 28. Rollback and Recovery Considerations

Migration reversible; revocation is non-destructive (row retained with `revoked_at` set).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-04-01 | Revocation implemented as a hard delete instead of `revoked_at`, losing audit trail | Medium | AC-02 explicitly requires non-destructive revocation | Data owner |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-04 — User Meet Access Foundation.

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
- Revocation must be non-destructive (revoked_at column), never a hard delete.
```
