# WP-07-05 — Venue Reference Skeleton

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-07-05 | Title | Venue Reference Skeleton |
| Epic | EPIC-07 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 59 |
| Target release group | Foundation Release C | Pilot relevance | Required (pilot only) |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-34 Configuration and Reference Data |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Implements a bare `venues` skeleton table (name, address, capacity — no scheduling logic) per WP-07-01's pattern, as a foundation for future Logistics (BC-22–24, Pilot Enhancement) module use.

## 3. Architecture Sources

[../../../../02-data/](../../../../02-data/).

## 4. Scope

Implement `venues` table (name, address, capacity nullable, organization_id nullable FK) per WP-07-01's pattern.

## 5. Explicit Exclusions

Does not implement venue scheduling/booking logic (a future Logistics module capability).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-07-01, WP-04-01 | Hard |

## 7. Current-State Inspection

No venue table exists.

## 8. Proposed Implementation Direction

`venues` table per WP-07-01's pattern, extended with `address`/`capacity` fields specific to this reference type.

## 9. Database Changes

Proposed `venues` table: `id`, `name`, `address` nullable, `capacity` nullable, `organization_id` nullable FK, `active`, timestamps.

## 10. Backend Requirements

Basic CRUD-ready entity; no scheduling logic.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not personal data.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Basic CRUD/migration test.

## 21. Test Data Requirements

New `VenueFactory` (proposed) for future test use.

## 22. Documentation Updates

Record the model in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `venues` migration and entity | WP-07-01, WP-04-01 complete | Migration reversible |

## 24. Acceptance Criteria

- **AC-01:** Given the `venues` table, when created, then it contains no scheduling/booking-logic column.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-07-01, WP-04-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status.

## 28. Rollback and Recovery Considerations

Migration reversible.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-07-05-01 | Scheduling logic is prematurely added to this skeleton | Low | Section 5 explicitly excludes it | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-07-05 — Venue Reference Skeleton.

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
- Do not implement scheduling/booking logic.
```
