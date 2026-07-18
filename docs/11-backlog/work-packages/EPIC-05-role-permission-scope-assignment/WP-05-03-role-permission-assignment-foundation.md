# WP-05-03 — Role-Permission Assignment Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-03 | Title | Role-Permission Assignment Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 36 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the many-to-many `role_permissions` mapping and its seed data, wiring WP-05-01's role catalog to WP-05-02's permission catalog per the source catalogs' documented mappings.

## 3. Architecture Sources

[../../../../01-architecture/role-catalog.md](../../../../01-architecture/role-catalog.md), [../../../../01-architecture/permission-catalog.md](../../../../01-architecture/permission-catalog.md).

## 4. Scope

Implement `role_permissions` join table; seed it from the mapping implied by both source catalogs (cross-referenced explicitly, since neither document is a single canonical mapping table).

## 5. Explicit Exclusions

Does not implement per-user permission overrides; does not implement the Authorization Decision Service itself (WP-05-07).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-01, WP-05-02 | Hard |

## 7. Current-State Inspection

`role-catalog.md` and `permission-catalog.md`'s per-role permission notes (both documents reference each other; neither is a single flattened mapping table — this work package produces the first flattened mapping).

## 8. Proposed Implementation Direction

Proposed `role_permissions` table (`role_id`, `permission_id`, composite primary key); a `RolePermissionSeeder` cross-referencing both catalogs.

## 9. Database Changes

Proposed `role_permissions` table: `role_id` (FK), `permission_id` (FK), composite PK (`role_id`, `permission_id`). No timestamps needed (pure mapping table).

## 10. Backend Requirements

Domain: none new — this is a pure mapping table consumed by WP-05-07.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This mapping, together with WP-05-04's scope and WP-05-05's assignment, forms the complete authority formula WP-05-07 evaluates.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Not personal data.

## 16. Audit and Activity Events

One-time system-initialization event.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Seeder test confirming every role has at least one permission mapped (no orphan role); confirming no permission is mapped to a nonexistent role.

## 21. Test Data Requirements

The seeder is production reference data.

## 22. Documentation Updates

Record the flattened role-permission mapping in `.ai/architecture.md` addendum as the first canonical cross-reference of the two source catalogs.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Cross-reference role-catalog.md and permission-catalog.md to build the flattened mapping | WP-05-01, WP-05-02 complete | Mapping documented |
| TASK-02 | Implement `role_permissions` migration and seeder | TASK-01 | Migration reversible, seed test passes |

## 24. Acceptance Criteria

- **AC-01:** Given the seeded `role_permissions` table, when checked, then every role has at least one mapped permission.
- **AC-02:** Given the seeded `role_permissions` table, when checked, then no row references a nonexistent role or permission.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-01, WP-05-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, seeder test output.

## 28. Rollback and Recovery Considerations

Migration reversible; seeder idempotent.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-03-01 | Cross-referencing two non-canonical source documents produces an incorrect mapping | Medium | Security reviewer reviews the flattened mapping explicitly before merge | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-03 — Role-Permission Assignment Foundation.

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
- Do not implement per-user permission overrides.
```
