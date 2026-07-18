# WP-02-04 — Domain Event and Integration Contract Conventions

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-02-04 | Title | Domain Event and Integration Contract Conventions |
| Epic | EPIC-02 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 12 |
| Target release group | Foundation Release A | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Defines how a bounded context announces something happened (a domain event) versus how another context is notified across a boundary (an integration event/Laravel event+listener), consistent with ADR-0011's event architecture, without adopting the still-undecided outbox pattern (WD-08) as a Phase 1 assumption.

## 3. Architecture Sources

[../../../../08-workflows/](../../../../08-workflows/), ADR-0011, [../../../10-review/workflow-event-notification-and-automation-review.md](../../../10-review/workflow-event-notification-and-automation-review.md).

## 4. Scope

Document domain-event naming convention (past-tense, context-prefixed, e.g. `OrganizationRegistered`, proposed); document that domain events extend the WP-02-02 `DomainEvent` marker; document Laravel's native `Event::dispatch`/listener mechanism as the Phase 1 default (no outbox table), explicitly flagged as revisitable once WD-08 resolves.

## 5. Explicit Exclusions

Does not implement the outbox pattern; does not implement any concrete domain event for a real capability; does not install a message-bus package.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-02 | Hard |

## 7. Current-State Inspection

No event/listener code exists beyond Laravel's own framework-level auth events (e.g., `Illuminate\Auth\Events\*` used implicitly by Fortify).

## 8. Proposed Implementation Direction

Proposed: `App\Domain\<Context>\Events\<Name>` implementing the shared-kernel `DomainEvent` marker; dispatched via Laravel's native event system; listeners registered per-context, not globally.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention only; no outbox table, no queued-event guarantee beyond Laravel's own `ShouldQueue` listener mechanism (queue reliability is WP-09-02's scope).

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Convention documents that domain events must not carry more sensitive data than the listener actually needs (minimum-necessary principle, cross-referenced to WP-14-01).

## 16. Audit and Activity Events

Convention documents that some domain events are also audit-worthy — the two are related but not identical (WP-06-01 distinction restated here).

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package *is* the event convention; explicitly states the outbox-pattern decision (WD-08) remains open and Phase 1 does not assume its resolution either way.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Convention documents that every event dispatch is logged with a correlation ID (WP-02-08 integration point).

## 20. Testing Requirements

Not Applicable — no concrete event yet.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record convention in `.ai/architecture.md` addendum; cross-reference WD-08 explicitly as unresolved.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document domain-event naming and dispatch convention | WP-02-02 complete | Convention documented |
| TASK-02 | Document explicit non-adoption of outbox pattern pending WD-08 | TASK-01 | Documented |
| TASK-03 | Record in `.ai/architecture.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the convention, when reviewed, then it does not implement or assume an outbox table.
- **AC-02:** Given the convention, when reviewed, then it explicitly states WD-08 remains open.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Convention documented; WD-08 non-assumption stated explicitly.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-02-04-01 | Convention is later invalidated if WD-08 resolves toward an outbox requirement, requiring rework of any event dispatched under the interim convention | Medium | No high-integrity event (Scoring/Official Results) is in Phase 1 scope, limiting blast radius | Lead architect |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-02-04-01 | Outbox pattern vs. `after_commit` dispatch (WD-08) | Non-blocking for Phase 1 (no high-integrity event in scope) |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-04 — Domain Event and Integration Contract Conventions.

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
- Do not implement an outbox table or a message-bus package.
- State explicitly that WD-08 remains open.
```
