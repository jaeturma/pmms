# WP-05-02 — Permission Catalog Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-02 | Title | Permission Catalog Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 35 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the `permissions` table and seed data from [permission-catalog.md](../../../../01-architecture/permission-catalog.md), resolving TD-08 (the "~115 permissions" approximation vs. the actual 104-row count Phase 0.13 found) the same way WP-05-01 resolved TD-07 — by direct row enumeration, not the source document's approximate figure.

## 3. Architecture Sources

[../../../../01-architecture/permission-catalog.md](../../../../01-architecture/permission-catalog.md), [../../../10-review/architecture-consistency-and-contradiction-analysis.md](../../../10-review/architecture-consistency-and-contradiction-analysis.md) (Section 9).

## 4. Scope

Implement `permissions` table (id, name, context, description); seed via direct row-count enumeration of permission-catalog.md.

## 5. Explicit Exclusions

Does not implement role-permission assignment (WP-05-03); does not edit `docs/01-architecture/permission-catalog.md` itself.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-05 | Hard |

## 7. Current-State Inspection

`docs/01-architecture/permission-catalog.md` — must be re-read and its rows counted directly.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Access\Permission` entity, `permissions` table, `PermissionSeeder`, documented count derivation identical in methodology to WP-05-01.

## 9. Database Changes

Proposed `permissions` table: `id`, `name` (unique, e.g. `context.action` naming convention proposed), `context` (bounded-context label), `description`, timestamps. No soft-delete.

## 10. Backend Requirements

Domain: `Permission` entity; no management UI in Phase 1 beyond WP-05-11's admin foundation.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Direct input to WP-05-07's Authorization Decision Service.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Not personal data.

## 16. Audit and Activity Events

One-time system-initialization event, not per-user audit.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Seeder test confirming seeded count matches direct enumeration; unique-name constraint test; naming-convention format test.

## 21. Test Data Requirements

The seeder is production reference data, not test fixtures (same exception as WP-05-01).

## 22. Documentation Updates

Record corrected permission count and derivation in `.ai/architecture.md` addendum; note TD-08 resolution.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Directly enumerate permission-catalog.md's rows | WP-02-05 complete | Count documented |
| TASK-02 | Implement `permissions` migration, entity, and seeder | TASK-01 | Migration reversible, seed count matches |

## 24. Acceptance Criteria

- **AC-01:** Given `permission-catalog.md`, when its rows are directly counted, then the resulting figure (documented at authoring time as 104, to be re-verified at execution time) is used for seeding, not the "~115" approximation.
- **AC-02:** Given the `permissions` table, when seeded, then every permission name is unique and follows the documented naming convention.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified; TD-08 resolution documented.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): direct-count methodology, migration status, seeder test output.

## 28. Rollback and Recovery Considerations

Migration reversible; seeder idempotent.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-02-01 | Direct count at execution time differs from the 104 figure recorded at Phase 0.13 authoring time (source document may have changed) | Low | Re-count at execution time, do not trust the cached figure blindly | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-02 — Permission Catalog Foundation.

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
- Do not edit docs/01-architecture/permission-catalog.md — count its rows directly at execution time.
```
