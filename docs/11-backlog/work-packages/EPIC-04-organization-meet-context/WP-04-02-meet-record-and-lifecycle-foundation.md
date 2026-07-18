# WP-04-02 — Meet Record and Lifecycle Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-02 | Title | Meet Record and Lifecycle Foundation |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 26 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-04 Meet Administration |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Implements the `Meet` aggregate and its lifecycle states per WF-01, giving PMMS its second real bounded-context table and the anchor every later meet-scoped context (registration, scoring, etc., all out of Phase 1) will eventually reference.

## 3. Architecture Sources

[../../../../08-workflows/](../../../../08-workflows/) (WF-01), [../../../../01-architecture/domain-open-decisions.md](../../../../01-architecture/domain-open-decisions.md) (OD-03).

## 4. Scope

Implement `Meet` aggregate (name, organization reference, level — provincial/division/regional/national, status, scheduled dates); implement lifecycle-state transitions per WF-01's meet states (proposed subset for Phase 1: Draft → Scheduled → Active → Closed — full state machine detail deferred to whichever phase implements meet operations).

## 5. Explicit Exclusions

Does not implement single-vs-multi-meet-per-organization business rules (OD-03 remains open); does not implement any sport/event/registration linkage.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-01 | Hard |

## 7. Current-State Inspection

No `meets` table or model exists.

## 8. Proposed Implementation Direction

Proposed: `App\Domain\Meet\Meet` aggregate, `App\Infrastructure\Meet\EloquentMeetRepository`.

## 9. Database Changes

Proposed `meets` table: `id`, `organization_id` (FK to `organizations`), `name`, `level` (enum), `status` (enum: draft/scheduled/active/closed), `start_date`, `end_date`, timestamps. Ownership: Meet Administration context. Index: `organization_id`, `status`. No soft-delete.

## 10. Backend Requirements

Domain: `Meet` aggregate with a state-transition guard rejecting invalid transitions (e.g., Closed → Draft); Application: `CreateMeetCommand`, `TransitionMeetStatusCommand` (proposed).

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable at this point in the sequence.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Not a personal-data table.

## 16. Audit and Activity Events

`MeetCreated`/`MeetStatusChanged` domain events (proposed), consumed by WP-06-06 once it exists.

## 17. Event, Queue, Notification, and Real-Time Requirements

Status-change events are the eventual trigger for notifications (WP-09-07), not implemented here.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Unit tests for the state-transition guard; integration test for create/persist.

## 21. Test Data Requirements

New `MeetFactory` (proposed) for test fixtures.

## 22. Documentation Updates

Record `Meet` aggregate design in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Design and implement `meets` migration | WP-04-01 complete | Migration reversible |
| TASK-02 | Implement `Meet` aggregate with state-transition guard | TASK-01 | Unit tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a Draft meet, when transitioned to Scheduled, then the transition succeeds.
- **AC-02:** Given a Closed meet, when a transition back to Draft is attempted, then it is rejected.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both acceptance criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output.

## 28. Rollback and Recovery Considerations

Migration reversible; no existing data to repair.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-02-01 | Phase 1's simplified 4-state lifecycle proves insufficient once real meet operations are implemented, requiring a later migration | Medium | Explicitly scoped as a Phase 1 subset in Section 4; full WF-01 state machine is a documented future extension | Domain reviewer |

## 30. Open Decisions

None (OD-03 tracked externally, non-blocking).

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-02 — Meet Record and Lifecycle Foundation.

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
- Implement only the 4-state Phase 1 lifecycle subset (Draft/Scheduled/Active/Closed).
- Do not implement any sport/event/registration linkage.
```
