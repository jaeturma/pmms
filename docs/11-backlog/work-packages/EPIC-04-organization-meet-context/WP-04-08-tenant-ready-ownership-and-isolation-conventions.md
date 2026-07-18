# WP-04-08 — Tenant-Ready Ownership and Isolation Conventions

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-08 | Title | Tenant-Ready Ownership and Isolation Conventions |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 32 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-06, EPIC-07, EPIC-08 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, Lead architect |

## 2. Purpose

Formalizes the defense-in-depth tenant-isolation convention (ADR-0012) every future table with organization/meet ownership must follow — a global Eloquent scope is never relied on alone; every layer (query, cache, storage, queue, audit) independently enforces the boundary.

## 3. Architecture Sources

[../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md](../../../../09-enterprise/tenant-data-ownership-and-isolation-architecture.md), ADR-0012.

## 4. Scope

Document the convention: every organization/meet-owned table includes an explicit `organization_id`/`meet_id` column (never inferred); every repository query explicitly filters by resolved context (via WP-04-05) rather than relying solely on a global Eloquent scope; cache keys, queue payloads, and storage paths (EPIC-08) include the tenant identifier explicitly.

## 5. Explicit Exclusions

Does not activate multi-tenancy (no second organization is created in Phase 1); does not implement database-per-tenant or any sharding.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-01, WP-04-02, WP-02-05 | Hard |

## 7. Current-State Inspection

`organizations` (WP-04-01) and `meets` (WP-04-02) tables exist by this point; no isolation convention yet formalized.

## 8. Proposed Implementation Direction

A global Eloquent scope (proposed `HasOrganizationScope` trait) is provided as a convenience layer, but every repository implementation must also explicitly pass the resolved context — never relying on the global scope as the sole enforcement mechanism, per ADR-0012's explicit rule.

## 9. Database Changes

Database Changes: None (this work package is a convention applied retroactively to WP-04-01/04-02's tables and prospectively to every later table).

## 10. Backend Requirements

Document and apply the convention to `Organization`/`Meet` repositories as the reference implementation.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This convention is a precondition for EPIC-05's scope model to be trustworthy — an authorization decision is meaningless if the underlying query wasn't actually tenant-scoped.

## 14. Security Requirements

No single missed filter should expose cross-tenant data — defense-in-depth means a bug in one layer (e.g., a missing global-scope application) is still caught by another (e.g., an explicit repository-level filter).

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Every audit record (WP-06-04) carries the tenant context explicitly, per this convention.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queue payloads carry tenant context explicitly, per WP-04-07.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Confirmed by WP-04-09's dedicated isolation-test work package, not duplicated here.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the convention formally in `.ai/architecture.md` addendum, extending ADR-0012's tenant-isolation rule with concrete Phase 1 implementation guidance.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document the defense-in-depth isolation convention | WP-04-01, WP-04-02 complete | Convention documented |
| TASK-02 | Apply convention to `Organization`/`Meet` repositories as reference implementation | TASK-01 | Repositories updated, reviewed |

## 24. Acceptance Criteria

- **AC-01:** Given the convention, when reviewed, then it explicitly states a global Eloquent scope alone is never sufficient.
- **AC-02:** Given the `Organization`/`Meet` repositories, when reviewed as the reference implementation, then each independently filters by resolved context rather than relying solely on the global scope.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-01, WP-04-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified; convention reviewed by Security reviewer and Lead architect jointly.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention, reference-implementation diff.

## 28. Rollback and Recovery Considerations

Not Applicable — convention document plus a repository refinement, not a destructive change.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-08-01 | A future context (EPIC-07/08) applies only the global scope and skips the explicit repository-level filter | Critical | WP-15-01/WP-15-03 checks every later repository against this convention | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-08 — Tenant-Ready Ownership and Isolation Conventions.

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
- Do not activate multi-tenancy or create a second organization.
- Every repository must independently filter by context, never relying solely on a global Eloquent scope.
```
