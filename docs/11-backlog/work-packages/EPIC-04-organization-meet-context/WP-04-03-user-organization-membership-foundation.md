# WP-04-03 — User Organization Membership Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-03 | Title | User Organization Membership Foundation |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 27 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-03 Organization Directory |
| Secondary affected contexts | BC-02 Identity | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Links `User` (WP-03-01) to `Organization` (WP-04-01) via a membership record, the first cross-context relationship in the backlog and the concrete input EPIC-05's scope model binds against.

## 3. Architecture Sources

[../../../../01-architecture/domain-open-decisions.md](../../../../01-architecture/domain-open-decisions.md) (DD-21).

## 4. Scope

Implement `organization_memberships` (user_id, organization_id, status, joined_at) as a many-to-many join with attributes, owned by the Organization Directory context (references, not writes into, the Identity context's `users` table).

## 5. Explicit Exclusions

Does not implement role/permission assignment (EPIC-05); does not implement membership-invitation workflow UI.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-01, WP-04-01 | Hard |

## 7. Current-State Inspection

No membership table exists.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Organization\OrganizationMembership` entity, referencing `User` and `Organization` IDs only (no cross-context Eloquent relationship traversal, per WP-02-06's dependency rule).

## 9. Database Changes

Proposed `organization_memberships` table: `id`, `user_id` (FK), `organization_id` (FK), `status`, `joined_at`, timestamps. Unique constraint: (`user_id`, `organization_id`). Index: both FK columns individually.

## 10. Backend Requirements

`AddUserToOrganizationCommand`, `RemoveUserFromOrganizationCommand` (proposed); reads via a dedicated query, not a cross-context Eloquent relationship.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable at this point — this table is what EPIC-05's scope model will later query.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Membership status is not itself sensitive.

## 16. Audit and Activity Events

Membership add/remove is audit-worthy once WP-06-06 exists.

## 17. Event, Queue, Notification, and Real-Time Requirements

`UserAddedToOrganization` domain event (proposed).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit/integration tests for add/remove and the unique-constraint guard (no duplicate active membership).

## 21. Test Data Requirements

New `OrganizationMembershipFactory` (proposed).

## 22. Documentation Updates

Record membership model in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `organization_memberships` migration | WP-03-01, WP-04-01 complete | Migration reversible |
| TASK-02 | Implement add/remove commands and unique-constraint guard | TASK-01 | Tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a user not yet a member, when added to an organization, then the membership persists.
- **AC-02:** Given a user already an active member, when added again, then the duplicate is rejected.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-01, WP-04-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output.

## 28. Rollback and Recovery Considerations

Migration reversible; no existing data.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-03-01 | Cross-context reference implemented as a direct Eloquent relationship instead of an ID reference, violating WP-02-06's boundary rule | Medium | Code review against WP-02-06's convention | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-03 — User Organization Membership Foundation.

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
- Reference User/Organization by ID only — no direct cross-context Eloquent relationship.
```
