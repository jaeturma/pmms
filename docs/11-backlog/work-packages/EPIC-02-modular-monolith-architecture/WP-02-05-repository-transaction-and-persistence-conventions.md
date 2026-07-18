# WP-02-05 — Repository, Transaction, and Persistence Conventions

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-02-05 | Title | Repository, Transaction, and Persistence Conventions |
| Epic | EPIC-02 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 13 |
| Target release group | Foundation Release A | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All (every context with persistence) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect, Data owner |

## 2. Purpose

Defines the repository-interface pattern (domain layer depends on an interface, infrastructure layer provides an Eloquent-backed implementation) and transaction-boundary convention every persistence-touching work package in EPIC-04 through EPIC-09 follows — the single most consequential EPIC-02 work package for resolving GAP-01, since every later work package's Section 9 (Database Changes) is reviewed against this convention.

## 3. Architecture Sources

[../../../../02-data/](../../../../02-data/), ADR-0005, [../../../10-review/data-database-history-and-persistence-review.md](../../../10-review/data-database-history-and-persistence-review.md).

## 4. Scope

Document repository-interface-per-aggregate pattern; document transaction-boundary rule (one transaction per command, never spanning multiple bounded contexts); document the three qualitative schema risks from Phase 0.13's data review (tenant-key/composite-uniqueness convention, version-chain design for high-integrity tables without soft-delete columns, index/partitioning-readiness for high-volume tables) as standing constraints every later Section 9 must address.

## 5. Explicit Exclusions

Does not create any migration or Eloquent model; does not resolve DD-01 (participant identity) — not needed, since Registration is out of Phase 1 scope.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-02 | Hard |

## 7. Current-State Inspection

Existing `database/migrations/` contains only starter-kit tables (users, cache, jobs, passkeys, two-factor columns); `database/database.sqlite` is the local dev database; `DB_CONNECTION=sqlite` in `.env.example`.

## 8. Proposed Implementation Direction

Proposed: `App\Domain\<Context>\<Aggregate>RepositoryInterface`, `App\Infrastructure\<Context>\Eloquent<Aggregate>Repository`; transactions wrapped via `DB::transaction()` at the command-handler level (WP-02-03 integration point), never inside the repository itself.

## 9. Database Changes

Database Changes: None (this work package defines the convention every later table proposal must follow, per Section 4's three standing constraints).

## 10. Backend Requirements

Convention only. States: soft-delete columns are prohibited on high-integrity/history tables per ADR-0005 (append-only versioning instead); every table gets a tenant-ready key convention from its first migration (per ADR-0012), even before multi-tenancy activates.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Convention documents that repository queries must respect tenant-ready scoping (WP-04-08 integration point) once organization/meet context exists.

## 16. Audit and Activity Events

Not Applicable directly — WP-06-01 builds on this convention.

## 17. Event, Queue, Notification, and Real-Time Requirements

Convention documents that domain events (WP-02-04) are dispatched after the transaction commits, not inside it.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Not Applicable — no concrete repository yet; convention is validated once EPIC-04/05/06 use it.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record convention in `.ai/architecture.md` addendum, explicitly restating GAP-01's decomposed-resolution approach (Section 16 of the main backlog document).

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document repository-interface pattern and transaction-boundary rule | WP-02-02 complete | Convention documented |
| TASK-02 | Document the three standing schema constraints (tenant-key, no-soft-delete-on-history, index/partition-readiness) | TASK-01 | Documented |
| TASK-03 | Record in `.ai/architecture.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the convention, when reviewed, then it prohibits soft-delete columns on high-integrity/history tables.
- **AC-02:** Given the convention, when reviewed, then it requires a tenant-ready key convention from every table's first migration.
- **AC-03:** Given the convention, when reviewed, then transactions are documented as command-handler-scoped, never repository-scoped.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Convention documented and reviewed by the Data owner.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-02-05-01 | A later work package's migration violates one of the three standing constraints unnoticed | High | WP-15-02 Foundation Database and Migration Review checks every accumulated migration against this convention | Data owner |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-05 — Repository, Transaction, and Persistence Conventions.

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
- Do not create any migration or Eloquent model.
- Every later work package's Section 9 must be checked against this convention's three standing constraints.
```
