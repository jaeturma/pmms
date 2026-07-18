# WP-03-07 — Authentication Audit and Security Events

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-07 | Title | Authentication Audit and Security Events |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 23 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | EPIC-06 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Wires login success, login failure, logout, status-transition (WP-03-04), and 2FA-challenge events into the Audit Event Model (WP-06-01) and Security Event Model (WP-06-03), the first real consumer of EPIC-06's foundation.

## 3. Architecture Sources

[../../../../03-security/](../../../../03-security/), ADR-0006.

## 4. Scope

Listen for Laravel's native `Illuminate\Auth\Events\{Login,Failed,Logout}` plus WP-03-04's domain events; record a Security Event (per WP-06-03) for failed-login and status-transition; record an Audit Event (per WP-06-01) for successful login/logout.

## 5. Explicit Exclusions

Does not implement the Audit/Security Event models themselves (WP-06-01, WP-06-03) — this work package only wires listeners once they exist.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-02, WP-03-04, WP-06-01, WP-06-03 | Hard |

## 7. Current-State Inspection

No listener wiring exists yet for Laravel's native auth events.

## 8. Proposed Implementation Direction

Proposed: `App\Domain\Identity\Listeners\RecordLoginAuditEvent`, `RecordFailedLoginSecurityEvent`, `RecordLogoutAuditEvent`, registered against Laravel's native auth events plus WP-03-04's domain events.

## 9. Database Changes

Database Changes: None (uses WP-06-01/WP-06-03's tables).

## 10. Backend Requirements

Three to four listener classes; correlation ID (WP-02-08) attached to every recorded event.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable directly — mobile login events use the same listeners once WP-12-05 exists.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Failed-login events must be recorded without storing the attempted (possibly mistyped, possibly sensitive) password value.

## 15. Privacy and Data-Governance Requirements

IP address and user agent, if recorded, are classified per WP-14-01 and handled per WP-14-02's masking rules where applicable.

## 16. Audit and Activity Events

This work package is the first real producer of Audit and Security events, exercising WP-06-04's actor/context/correlation metadata contract for the first time.

## 17. Event, Queue, Notification, and Real-Time Requirements

Listener execution may be queued (`ShouldQueue`) once EPIC-09 exists; synchronous in the interim.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature tests: successful login produces an Audit Event; failed login produces a Security Event without a stored password value; logout produces an Audit Event.

## 21. Test Data Requirements

Reuses `UserFactory`. Do not create new test data.

## 22. Documentation Updates

Record listener wiring in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement login/logout audit-event listeners | WP-03-02, WP-06-01 complete | Listeners implemented, tested |
| TASK-02 | Implement failed-login security-event listener | WP-06-03 complete | Listener implemented, tested |
| TASK-03 | Implement status-transition audit listener | WP-03-04, WP-06-01 complete | Listener implemented, tested |

## 24. Acceptance Criteria

- **AC-01:** Given a successful login, when it occurs, then an Audit Event is recorded with actor, correlation ID, and timestamp.
- **AC-02:** Given a failed login, when it occurs, then a Security Event is recorded without the attempted password value present anywhere in the record.
- **AC-03:** Given a status transition (suspend/reactivate/revoke), when it occurs, then an Audit Event is recorded.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-02, WP-03-04, WP-06-01, WP-06-03 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three acceptance criteria verified by test.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): a representative recorded event example (redacted).

## 28. Rollback and Recovery Considerations

Not Applicable — additive listeners only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-07-01 | Failed-login listener accidentally logs the attempted password | Critical | AC-02 explicitly tests for absence of the password value | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-07 — Authentication Audit and Security Events.

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
- Never record an attempted password value in any event.
```
