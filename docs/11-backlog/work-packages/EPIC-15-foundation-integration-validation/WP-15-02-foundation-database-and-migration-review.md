# WP-15-02 — Foundation Database and Migration Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-15-02 | Title | Foundation Database and Migration Review |
| Epic | EPIC-15 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P2 | Implementation sequence | 145 |
| Target release group | Foundation Release F | Pilot relevance | Required |
| Architecture readiness classification | Verification Only | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Data reviewer, Engineering lead |

## 2. Purpose

Verifies the foundation's physical schema against the Phase 0.5 data architecture: naming standards, identifier strategy, ownership boundaries, append-only structures where required, index coverage, and migration integrity (fresh-migrate, rollback, re-migrate all clean) — the first schema-level review since GAP-01 flagged that no physical schema existed before Phase 1.

## 3. Architecture Sources

[../../../02-data/database-naming-and-design-standards.md](../../../02-data/database-naming-and-design-standards.md), [../../../02-data/identifier-and-reference-strategy.md](../../../02-data/identifier-and-reference-strategy.md), [../../../02-data/persistence-ownership-map.md](../../../02-data/persistence-ownership-map.md), ADR-0005; reviews all migrations created in EPIC-01..09.

## 4. Scope

Full migration lifecycle verification (`migrate:fresh`, targeted rollbacks, re-migrate) on a clean database; schema sweep against naming/identifier standards; ownership check (every table mapped to its bounded context, no cross-context foreign keys violating the ownership map); append-only verification for audit/security-event tables (no updated_at/deleted_at, per WP-06-01's structural rule); index review against foundation query paths; findings routed as defects; review record for WP-15-12.

## 5. Explicit Exclusions

Does not design new schema or add indexes itself (owning work packages do); does not performance-test queries (WP-15-08); does not review seed/factory content beyond standards conformance.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| All EPIC-01..09 work packages at Implementation Complete | Hard |

## 7. Current-State Inspection

The review inspects the actual migration set and resulting schema, not the backlog's proposed table shapes — divergences between proposal and implementation are themselves findings if undocumented.

## 8. Proposed Implementation Direction

Scripted lifecycle runs + schema introspection compared against the standards documents; findings register in evidence format.

## 9. Database Changes

Database Changes: None (review executes migrations on disposable databases only).

## 10. Backend Requirements

Review execution only.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable (WP-15-03).

## 14. Security Requirements

Append-only structural verification is the security-relevant core here.

## 15. Privacy and Data-Governance Requirements

Verify no personal-data column exists outside its owning context's tables per the ownership map.

## 16. Audit and Activity Events

The three-way audit/activity/security table distinction re-verified structurally.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queue/outbox-adjacent tables (if any were created) checked against ownership and naming standards.

## 18. Offline and Synchronization Requirements

Not Applicable — no sync tables in Phase 1.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Lifecycle runs recorded; schema-sweep results; no new feature tests.

## 21. Test Data Requirements

Disposable databases only; no production-like data.

## 22. Documentation Updates

Review record to the sign-off evidence set; schema summary added to `.ai/architecture.md` if divergent from recorded conventions.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Full migration lifecycle verification on clean database | EPIC-01..09 complete | Fresh/rollback/re-migrate all clean |
| TASK-02 | Schema sweep against naming/identifier/ownership standards | TASK-01 | Sweep record complete |
| TASK-03 | Append-only and index review; file findings | TASK-02 | Findings register complete |

## 24. Acceptance Criteria

- **AC-01:** Given a clean database, when the full migration lifecycle runs, then fresh-migrate, rollback, and re-migrate complete without error.
- **AC-02:** Given the schema sweep, when complete, then every table has a recorded verdict against naming, identifier, and ownership standards.
- **AC-03:** Given audit/security-event tables, when inspected, then append-only structure is confirmed (no update/delete columns).

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). All EPIC-01..09 work packages at Implementation Complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): lifecycle run output, schema-sweep record, findings register.

## 28. Rollback and Recovery Considerations

Not Applicable — review on disposable databases.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-15-02-01 | Rollback paths untested during development turn out broken only now, late in the phase | Medium | This review is scheduled precisely to catch that before sign-off; findings are defects, not waivers | Data reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-15-02 — Foundation Database and Migration Review.

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
- Verification only — schema fixes route to owning work packages.
- Use disposable databases — never a shared or persistent environment.
- Undocumented divergence from proposed schema is itself a finding.
```
