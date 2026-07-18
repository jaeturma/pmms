# WP-04-01 — Organization Hierarchy Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-01 | Title | Organization Hierarchy Foundation |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 25 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-03 Organization Directory |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Implements the first real bounded-context aggregate (Organization) using the WP-02-01/02/05 conventions, giving PMMS its first genuine domain table and testing whether those conventions actually work against real requirements — the first real exercise of GAP-01's decomposed resolution approach.

## 3. Architecture Sources

[../../../../01-architecture/domain-open-decisions.md](../../../../01-architecture/domain-open-decisions.md) (DD-09), [../../../10-review/domain-bounded-context-and-ownership-review.md](../../../10-review/domain-bounded-context-and-ownership-review.md).

## 4. Scope

Implement `Organization` aggregate (name, type, status, parent-organization reference for hierarchy) per DD-09; implement `OrganizationRepositoryInterface`/`EloquentOrganizationRepository` per WP-02-05.

## 5. Explicit Exclusions

Does not implement organization-type reference data content (WP-07-02's scope — this work package only adds the FK/enum slot); does not implement multi-tenancy activation (ED-05/06 remain deferred).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-05 | Hard |
| DD-09 (organization data source) | Soft — non-blocking per Phase 0.13 |

## 7. Current-State Inspection

No `organizations` table or model exists in the repository.

## 8. Proposed Implementation Direction

Proposed: `App\Domain\Organization\Organization` aggregate, `App\Infrastructure\Organization\EloquentOrganizationRepository`, Eloquent model `App\Models\Organization` (proposed, may live under the Infrastructure namespace per WP-02-01).

## 9. Database Changes

Proposed `organizations` table: `id` (ULID/UUID, proposed per WP-02-02's ID convention), `name`, `type` (FK to future reference data, nullable in Phase 1), `parent_organization_id` (nullable, self-referencing FK for hierarchy), `status`, `created_at`/`updated_at`. Ownership: Organization Directory context. Unique constraint: none beyond primary key in Phase 1 (name uniqueness policy is a DD-09-adjacent open question). Index: `parent_organization_id`. No soft-delete column (per WP-02-05's standing constraint) — deactivation via `status`.

## 10. Backend Requirements

Domain: `Organization` aggregate with hierarchy-depth guard (prevent circular parent references); Application: `CreateOrganizationCommand`, `UpdateOrganizationCommand` (proposed); Validation: name required, parent must not create a cycle; Transactions: per WP-02-05.

## 11. Web Frontend Requirements

Not Applicable — no UI in this work package (WP-07-10/WP-11-XX build admin UI later).

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable — authorization model does not exist yet at this point in the sequence (EPIC-05 depends on this work package, not the reverse).

## 14. Security Requirements

Not Applicable beyond standard input validation.

## 15. Privacy and Data-Governance Requirements

Organization records are not personal data; no special classification.

## 16. Audit and Activity Events

Organization create/update is audit-worthy once WP-06-06 exists; this work package defines the trigger point (domain events per WP-02-04), not the recording itself.

## 17. Event, Queue, Notification, and Real-Time Requirements

`OrganizationCreated`/`OrganizationUpdated` domain events (proposed) per WP-02-04's convention.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit tests: hierarchy-cycle prevention; Feature/integration tests: create/update via repository; migration test.

## 21. Test Data Requirements

New `OrganizationFactory` (proposed) for test fixtures — created as part of this work package's own test scaffolding, consistent with "do not create test data" meaning no production/demo data, not "no factories."

## 22. Documentation Updates

Record `Organization` aggregate design in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `organizations` migration | WP-02-05 complete | Migration reversible, applies cleanly |
| TASK-02 | Implement `Organization` aggregate and repository | TASK-01 | Unit tests pass |
| TASK-03 | Implement hierarchy-cycle-prevention guard | TASK-02 | Unit test confirms cycle rejected |

## 24. Acceptance Criteria

- **AC-01:** Given a new organization with no parent, when created, then it persists successfully.
- **AC-02:** Given an attempt to set an organization's parent to itself or to one of its own descendants, when attempted, then the operation is rejected with a domain-level error.
- **AC-03:** Given the `organizations` migration, when rolled back, then the table is cleanly removed with no orphaned data elsewhere.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three acceptance criteria verified; migration reviewed against WP-02-05's three standing schema constraints.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output.

## 28. Rollback and Recovery Considerations

Migration is reversible (`down()` drops the table); no data-repair concern since this is a new table with no prior data.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-01-01 | Hierarchy-cycle guard has an edge case (e.g., deep chains) not caught by initial tests | Medium | WP-15-02 database review re-examines this table specifically | Data owner |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-04-01-01 | Should organization `name` be unique globally, per-type, or not enforced at the database level? | Non-blocking — deferred to DD-09 resolution; Phase 1 does not enforce uniqueness beyond primary key |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-01 — Organization Hierarchy Foundation.

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
- Do not add a name-uniqueness constraint — DD-09 remains open.
- Do not implement any admin UI.
```
