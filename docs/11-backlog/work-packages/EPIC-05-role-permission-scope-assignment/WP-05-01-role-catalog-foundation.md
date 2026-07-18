# WP-05-01 — Role Catalog Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-01 | Title | Role Catalog Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 34 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the `roles` table and seed data from [role-catalog.md](../../../../01-architecture/role-catalog.md), **first resolving the documentation inconsistency Phase 0.13 flagged** (the source document states "53 roles across 12 categories" but names 13 category labels, while the file's own H2 structure shows 9 headings) by counting the actual role entries directly from the source document before seeding, rather than trusting any of the three conflicting numbers.

## 3. Architecture Sources

[../../../../01-architecture/role-catalog.md](../../../../01-architecture/role-catalog.md), [../../../10-review/architecture-consistency-and-contradiction-analysis.md](../../../10-review/architecture-consistency-and-contradiction-analysis.md) (Section 9).

## 4. Scope

Implement `roles` table (id, name, category, description); count actual role entries from role-catalog.md's content directly (not from its summary sentence); seed via a `RoleSeeder` (proposed) with the corrected count, documented explicitly.

## 5. Explicit Exclusions

Does not implement permission assignment (WP-05-03); does not edit `docs/01-architecture/role-catalog.md` itself — the correction is applied only to this work package's seed data and its own documentation note (per working rule 39, no silent rewrite of Phase 0 history).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-05 | Hard |

## 7. Current-State Inspection

`docs/01-architecture/role-catalog.md` — must be re-read in full and its actual role entries counted directly (not the summary sentence) before seeding.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Access\Role` entity, `roles` table, `RoleSeeder`. The seeder's role count is documented in its own docblock/comment as "derived from direct enumeration of role-catalog.md's entries as of [date], resolving TD-07's documentation inconsistency — see [10-review/technical-debt-and-documentation-debt-register.md, TD-07]."

## 9. Database Changes

Proposed `roles` table: `id`, `name` (unique), `category`, `description`, timestamps. No soft-delete (deactivation via a future `active` flag if needed, not built in Phase 1 since no role deactivation workflow exists yet).

## 10. Backend Requirements

Domain: `Role` entity; Application: none yet (read-only catalog in Phase 1, no role-management UI beyond WP-05-11's admin foundation).

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This table is a direct input to the Authorization Decision Service (WP-05-07); it is not itself an authorization mechanism.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Not personal data.

## 16. Audit and Activity Events

Role catalog seeding is a one-time system-initialization event, not a per-user audit event.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Seeder test confirming the seeded role count matches the documented, directly-enumerated count; unique-name constraint test.

## 21. Test Data Requirements

The seeder itself is the reference data source (catalog data, not test data) — this is a legitimate exception to "do not create test data," since the role catalog is production reference data, not throwaway fixtures.

## 22. Documentation Updates

Record the corrected role count and its derivation in `.ai/architecture.md` addendum; note the resolution of TD-07 in this work package's completion evidence.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Directly enumerate role-catalog.md's actual role entries (not its summary sentence) | WP-02-05 complete | Count documented with methodology |
| TASK-02 | Implement `roles` migration and `Role` entity | TASK-01 | Migration reversible |
| TASK-03 | Implement `RoleSeeder` with the directly-enumerated catalog | TASK-01..02 | Seeded count matches direct enumeration |

## 24. Acceptance Criteria

- **AC-01:** Given `role-catalog.md`, when its entries are directly enumerated, then the resulting count is documented and used for seeding — not the source document's summary sentence, which Phase 0.13 flagged as inconsistent (TD-07).
- **AC-02:** Given the `roles` table, when seeded, then every role name is unique.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified; TD-07 resolution documented in completion evidence.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the direct-enumeration methodology and resulting count, migration status, seeder test output.

## 28. Rollback and Recovery Considerations

Migration reversible; seeder is idempotent (re-running does not duplicate roles).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-01-01 | Direct enumeration produces a fourth, still-different count from the three already conflicting numbers (53/12/13/9) | Medium | Methodology is documented transparently so a reviewer can re-verify | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-01 — Role Catalog Foundation.

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
- Do not edit docs/01-architecture/role-catalog.md — count its entries directly, do not trust its summary sentence, and document your methodology.
- Do not implement role-permission assignment — that is WP-05-03.
```
