# WP-07-10 — Reference Data Administration UI Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-10 | Title | Reference Data Administration UI Foundation |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 64 |
| Target release group | Foundation Release C | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | EPIC-10, EPIC-11 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Builds a generic reference-data admin UI (one page pattern reused across `organization_types`, `venues`, `committees`, etc. via WP-07-01's shared structure) — the third administrative UI in the backlog, reusing WP-05-11's authorization/UI patterns.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/).

## 4. Scope

Implement one generic Inertia page/controller pattern parameterized by reference type, listing/create/edit/activate-deactivate (per WP-07-07) for each of WP-07-02, WP-07-05, WP-07-06 (organization types, venues, committees — sports catalog WP-07-04 excluded per its own stricter names-only discipline, to avoid the admin UI becoming a backdoor for adding sport rules).

## 5. Explicit Exclusions

Does not expose the `sports` (WP-07-04) reference table for editing via this UI — sport additions remain a deliberate, reviewed action outside Phase 1's generic admin tooling, avoiding accidental rule-content creep.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-07-01 through WP-07-07, WP-10-01 through WP-10-09, WP-11-01 through WP-11-10 | Hard |

## 7. Current-State Inspection

WP-05-11's assignment admin page as the established pattern to reuse.

## 8. Proposed Implementation Direction

Generic `ReferenceDataController` (proposed) parameterized by an allow-list of reference types (explicitly excluding `sports`), reusing WP-11-09's table and WP-11-07's form components.

## 9. Database Changes

Database Changes: None (uses existing tables).

## 10. Backend Requirements

One generic controller with an explicit allow-list, not a fully dynamic/generic "any table" admin (which would be a security risk).

## 11. Web Frontend Requirements

One generic listing/edit page pattern, parameterized by reference type.

## 12. Flutter Requirements

Not Applicable — admin UI is web-only.

## 13. Authorization and Access Control

Gated behind a dedicated `reference_data.manage` permission (proposed).

## 14. Security Requirements

The allow-list must be a hard-coded, explicit list — never dynamically derived from all existing tables (which would risk exposing an unintended table).

## 15. Privacy and Data-Governance Requirements

Not Applicable — reference data is not personal data.

## 16. Audit and Activity Events

Every create/edit/activate/deactivate through this UI recorded via WP-06-06.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Standard request logging.

## 20. Testing Requirements

Feature tests: authorized admin can manage allow-listed types; `sports` is confirmed inaccessible via this controller even with the right permission (an explicit negative test).

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the admin UI and its explicit allow-list in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `ReferenceDataController` with explicit allow-list | WP-07-01..07 complete | `sports` confirmed excluded |
| TASK-02 | Implement generic listing/edit Inertia pages | WP-10-01..09, WP-11-01..10 complete | Frontend tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given an authorized admin, when managing an allow-listed reference type, then create/edit/activate/deactivate all work correctly.
- **AC-02:** Given any request attempting to manage `sports` through this controller, when made, then it is rejected regardless of the requester's permissions.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-07-01 through WP-07-07, WP-10-01 through WP-10-09, WP-11-01 through WP-11-10 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, screenshots.

## 28. Rollback and Recovery Considerations

Feature can be disabled by removing the route without affecting underlying data.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-10-01 | The allow-list is accidentally implemented as dynamic (all tables), exposing an unintended table | High | AC-02 explicitly tests the exclusion of `sports` | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-10 — Reference Data Administration UI Foundation.

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
- The allow-list must be explicit and hard-coded, never dynamically derived.
- sports must remain inaccessible via this controller.
```
