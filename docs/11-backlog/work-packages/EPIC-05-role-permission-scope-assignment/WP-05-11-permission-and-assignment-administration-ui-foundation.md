# WP-05-11 — Permission and Assignment Administration UI Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-11 | Title | Permission and Assignment Administration UI Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P2 | Implementation sequence | 44 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | EPIC-10, EPIC-11 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer, Security reviewer |

## 2. Purpose

Builds the first real administrative UI in PMMS — a page to view roles/permissions and grant/revoke assignments — exercising EPIC-10's API/Inertia contract and EPIC-11's design-system components together for the first time against a genuinely sensitive, high-privilege workflow.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/), ADR-0009.

## 4. Scope

Implement a role/assignment listing page (React/Inertia), a grant-assignment form, and a revoke action — this is the first work package to actually expose WP-05-05's `AssignRoleCommand`/`RevokeAssignmentCommand` via a route, now that WP-05-07/08/09/10 provide the authorization gate that was missing when those commands were built.

## 5. Explicit Exclusions

Does not implement role/permission catalog editing (catalogs are seeded, not user-editable in Phase 1); does not implement bulk assignment.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-07, WP-10-01 through WP-10-09, WP-11-01 through WP-11-10 | Hard |

## 7. Current-State Inspection

No admin UI exists; depends on EPIC-10's contract and EPIC-11's components both being complete.

## 8. Proposed Implementation Direction

Proposed Inertia page `resources/js/pages/admin/assignments/index.tsx`; controller `App\Http\Controllers\Admin\AssignmentController` (proposed) authorized via WP-05-08's Policy convention.

## 9. Database Changes

Database Changes: None (uses existing EPIC-05 tables).

## 10. Backend Requirements

`AssignmentController` (proposed) wrapping `AssignRoleCommand`/`RevokeAssignmentCommand`, authorized via `AssignmentPolicy` (proposed, using WP-05-08's convention).

## 11. Web Frontend Requirements

List page (table, per WP-11-09), grant form (per WP-11-07), revoke confirmation (per WP-11-10); permission-denied state (per WP-10-09) if the current user lacks the required permission to view this page at all.

## 12. Flutter Requirements

Not Applicable — admin UI is web-only in Phase 1.

## 13. Authorization and Access Control

This page itself requires a specific permission (proposed: `identity.assignments.manage`) to view — the first real end-to-end exercise of the full authorization stack (WP-05-01 through WP-05-10).

## 14. Security Requirements

Grant/revoke actions must be CSRF-protected (Inertia default) and re-validate authorization server-side even though the UI already hides the option from unauthorized users (never rely on frontend hiding alone).

## 15. Privacy and Data-Governance Requirements

The listing page must not expose more user detail than necessary for the assignment task (name/email, not full profile).

## 16. Audit and Activity Events

Every grant/revoke performed through this UI is recorded via WP-06-06 (now that EPIC-06 exists as a hard dependency by this point in the sequence) with the acting admin as actor.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly — no notification triggered by assignment changes in Phase 1.

## 18. Offline and Synchronization Requirements

Not Applicable — admin UI is not offline-capable.

## 19. Observability Requirements

Standard request logging via WP-13-01 once it exists.

## 20. Testing Requirements

Feature tests: authorized admin can grant/revoke; unauthorized user gets a permission-denied response, not a 500 error or a silently-empty page; frontend integration test confirms the page renders correctly for both states.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the admin UI's routes and permission requirement in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `AssignmentController` and `AssignmentPolicy` | WP-05-07, WP-05-08 complete | Backend tests pass |
| TASK-02 | Implement listing/grant/revoke Inertia pages | WP-10-01..09, WP-11-01..10 complete | Frontend tests pass |
| TASK-03 | Feature-test authorized and unauthorized access paths | TASK-01..02 | Both paths tested |

## 24. Acceptance Criteria

- **AC-01:** Given a user with the required permission, when they visit the assignment admin page, then they see the list and can grant/revoke.
- **AC-02:** Given a user without the required permission, when they attempt to visit the page, then they receive a permission-denied response, not a server error or an empty-looking success page.
- **AC-03:** Given a grant/revoke action performed through the UI, when it completes, then it is recorded as an audit event with the acting admin as actor.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-07, WP-10-01 through WP-10-09, WP-11-01 through WP-11-10 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three acceptance criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): screenshots (both light/dark theme), test output, example API response.

## 28. Rollback and Recovery Considerations

Feature can be disabled by removing the route without affecting underlying data (per ADR-0008's feature-disablement discipline).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-11-01 | Frontend hides the grant/revoke option for unauthorized users but the backend route remains unprotected | Critical | AC-02 explicitly tests direct backend access, not just UI visibility | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-11 — Permission and Assignment Administration UI Foundation.

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
- Backend authorization must be enforced independently of frontend visibility — test direct backend access, not just UI state.
- Do not implement role/permission catalog editing.
```
