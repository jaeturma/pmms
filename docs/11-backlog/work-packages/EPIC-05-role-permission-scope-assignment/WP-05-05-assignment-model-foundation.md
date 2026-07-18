# WP-05-05 — Assignment Model Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-05 | Title | Assignment Model Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P1 | Implementation sequence | 38 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | BC-03, BC-04 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the `assignments` table binding a user + role + scope together — the fourth and final element of the authority formula (role + permission + scope + assignment), and per [assignment-model.md](../../../../01-architecture/assignment-model.md), the record that actually grants authority (a role alone grants nothing without an assignment).

## 3. Architecture Sources

[../../../../01-architecture/assignment-model.md](../../../../01-architecture/assignment-model.md), ADR-0003.

## 4. Scope

Implement `assignments` table (user_id, role_id, scope_type, scope_target_id, granted_at, revoked_at nullable); implement `AssignRoleCommand`, `RevokeAssignmentCommand` (proposed).

## 5. Explicit Exclusions

Does not implement time-bounded validity windows beyond simple granted/revoked (WP-05-06's scope); does not implement the Authorization Decision Service evaluation logic itself (WP-05-07).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-01, WP-05-04, WP-04-03 | Hard |

## 7. Current-State Inspection

No assignment table exists; depends on `roles` (WP-05-01), `scope_types` (WP-05-04), and `organization_memberships` (WP-04-03) as prerequisite context for a valid assignment.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Access\Assignment` aggregate; `assignments` table with polymorphic-style `scope_type` + `scope_target_id` columns (not a true polymorphic Eloquent relation, to keep querying explicit per WP-02-06's boundary discipline).

## 9. Database Changes

Proposed `assignments` table: `id`, `user_id` (FK), `role_id` (FK), `scope_type` (FK to `scope_types.code`), `scope_target_id`, `granted_at`, `granted_by_user_id` (FK), `revoked_at` nullable, `revoked_by_user_id` nullable FK, timestamps. Index: `user_id`, composite (`scope_type`, `scope_target_id`). No soft-delete — revocation via `revoked_at`, preserving full assignment history per ADR-0005's append-only discipline.

## 10. Backend Requirements

Domain: `Assignment` aggregate validating the assignee has organization membership consistent with the assignment's scope target (an assignment scoped to a meet requires the user already have access to that meet's organization, cross-checked against WP-04-03/04-04); Application: `AssignRoleCommand`/`RevokeAssignmentCommand`.

## 11. Web Frontend Requirements

Not Applicable in this work package (WP-05-11 builds the admin UI).

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This table, together with roles/permissions/scope, is the complete authority record WP-05-07 queries; an assignment with `revoked_at` set must never grant authority.

## 14. Security Requirements

Assignment creation must validate the granting actor themselves holds sufficient authority (self-escalation prevention) — enforced once WP-05-07 exists; this work package's commands remain unexposed until then, same discipline as WP-03-04.

## 15. Privacy and Data-Governance Requirements

Not personal data beyond the user/granting-actor references.

## 16. Audit and Activity Events

Assignment grant/revoke is one of the most consequential audit events in the system — every one must be recorded once WP-06-06 exists.

## 17. Event, Queue, Notification, and Real-Time Requirements

`RoleAssigned`/`AssignmentRevoked` domain events (proposed).

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit tests: assignment creation validates scope-target consistency with membership; revoked assignment does not grant authority; duplicate active assignment (same user/role/scope) is prevented or explicitly allowed (documented decision).

## 21. Test Data Requirements

New `AssignmentFactory` (proposed) for test fixtures.

## 22. Documentation Updates

Record `Assignment` aggregate design in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `assignments` migration | WP-05-01, WP-05-04, WP-04-03 complete | Migration reversible |
| TASK-02 | Implement `Assignment` aggregate with scope-target/membership consistency validation | TASK-01 | Unit tests pass |
| TASK-03 | Implement `AssignRoleCommand`/`RevokeAssignmentCommand` (unexposed to any route) | TASK-02 | Commands implemented, tested |

## 24. Acceptance Criteria

- **AC-01:** Given a user with organization membership, when assigned a role scoped to that organization, then the assignment persists.
- **AC-02:** Given a revoked assignment, when the authority it once granted is checked, then it no longer grants that authority.
- **AC-03:** Given an attempted assignment scoped to a meet the user has no access to, when attempted, then it is rejected.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-01, WP-05-04, WP-04-03 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three acceptance criteria verified; commands not exposed via any route.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output for all three criteria.

## 28. Rollback and Recovery Considerations

Migration reversible; revocation is non-destructive (`revoked_at` set, row retained).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-05-01 | Assignment commands exposed prematurely, before WP-05-07's authorization check exists, allowing self-escalation | Critical | Section 14 explicitly prohibits route exposure until WP-05-07 completes | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-05 — Assignment Model Foundation.

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
- Do not expose assignment commands via any route — authorization enforcement (WP-05-07) does not exist yet.
- Revocation must be non-destructive.
```
