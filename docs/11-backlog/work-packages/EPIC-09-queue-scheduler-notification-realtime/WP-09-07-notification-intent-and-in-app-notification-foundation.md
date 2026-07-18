# WP-09-07 — Notification Intent and In-App Notification Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-09-07 | Title | Notification Intent and In-App Notification Foundation |
| Epic | EPIC-09 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 83 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-31 Notifications |
| Secondary affected contexts | BC-32 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Implements an in-app notification model (Laravel's native `notifications` table via `php artisan notifications:table`) and a `NotificationIntent` abstraction — decoupling "something happened that a user should know about" from "how it's delivered" — with only in-app and email channels active in Phase 1, per Section 5's SMS/push exclusion.

## 3. Architecture Sources

[../../../../08-workflows/](../../../../08-workflows/).

## 4. Scope

Install Laravel's native notifications table; implement a `NotificationIntent` value object/interface every context can raise; implement one concrete example (e.g., `AssignmentGrantedNotification`, tied to WP-05-05's grant event) demonstrating the pattern end-to-end.

## 5. Explicit Exclusions

Does not implement SMS or push channels; does not implement a notification-preferences UI (a future enhancement).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-09-02, WP-06-01 | Hard |

## 7. Current-State Inspection

No notification infrastructure exists; Laravel's native notification system is available but unused.

## 8. Proposed Implementation Direction

Standard Laravel `Notification` classes implementing `via(): ['database', 'mail']`; a thin `NotificationIntent` interface (proposed) that domain events map to, decoupling event-raising from notification-class-selection.

## 9. Database Changes

Proposed standard Laravel `notifications` table (via the framework's own migration stub).

## 10. Backend Requirements

`NotificationIntent` interface; one concrete `AssignmentGrantedNotification` example wired to WP-05-05's grant flow.

## 11. Web Frontend Requirements

Not Applicable in this work package — a notification bell/inbox UI is a future enhancement beyond Phase 1's foundation scope.

## 12. Flutter Requirements

Not Applicable in Phase 1.

## 13. Authorization and Access Control

A user only ever receives their own notifications (Laravel's native `notifiable` scoping handles this correctly by default).

## 14. Security Requirements

Notification content must not leak more detail than the recipient is entitled to see (same discipline as WP-10-04's prop-minimization rule, applied here).

## 15. Privacy and Data-Governance Requirements

Notification payloads are classified per WP-14-01 once it exists.

## 16. Audit and Activity Events

Notification dispatch is not itself an audit event (it's a delivery mechanism), but the underlying domain event it responds to already is, per WP-06-06.

## 17. Event, Queue, Notification, and Real-Time Requirements

This work package **is** the notification foundation — queued (`ShouldQueue`) by default, using the `notifications` named queue from WP-09-03.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test confirming `AssignmentGrantedNotification` is created and dispatched when WP-05-05's grant command succeeds; confirms delivery via the `notifications` named queue.

## 21. Test Data Requirements

Reuses `AssignmentFactory`. Do not create new test data.

## 22. Documentation Updates

Record the `NotificationIntent` pattern and the example notification in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Install Laravel's native `notifications` table | WP-09-02 complete | Migration applies |
| TASK-02 | Implement `NotificationIntent` interface | WP-06-01 complete | Interface documented |
| TASK-03 | Implement `AssignmentGrantedNotification` end-to-end example | TASK-01..02 | Feature test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a role grant via WP-05-05, when it succeeds, then `AssignmentGrantedNotification` is created for the affected user.
- **AC-02:** Given the notification, when dispatched, then it is processed via the `notifications` named queue, not `default`.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-09-02, WP-06-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, example notification payload (redacted).

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive infrastructure.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-09-07-01 | Notification content accidentally includes more detail than the recipient should see | Medium | AC-01's test reviews payload content explicitly | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-09-07 — Notification Intent and In-App Notification Foundation.

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
- Do not implement SMS or push channels.
- Do not implement a notification-preferences UI.
```
