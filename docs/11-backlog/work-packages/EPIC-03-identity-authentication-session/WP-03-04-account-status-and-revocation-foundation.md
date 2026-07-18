# WP-03-04 — Account Status and Revocation Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-04 | Title | Account Status and Revocation Foundation |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 20 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | EPIC-06 (audit) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the status-transition commands (suspend, reactivate, revoke) for the `account_status` column WP-03-01 introduced, per [../../../10-review/identity-access-scope-and-assignment-review.md](../../../10-review/identity-access-scope-and-assignment-review.md)'s "Missing Role-Permission Coverage" discipline — a revoked/suspended user must be denied authentication immediately, not merely flagged.

## 3. Architecture Sources

[../../../../01-architecture/access-review-and-revocation.md](../../../../01-architecture/access-review-and-revocation.md).

## 4. Scope

Implement `SuspendUserAccountCommand`, `ReactivateUserAccountCommand`, `RevokeUserAccountCommand` (proposed) per WP-02-03's convention; enforce status check at authentication time (Fortify's `authenticateUsing` hook or an auth-pipeline listener).

## 5. Explicit Exclusions

Does not implement who is authorized to issue these commands — EPIC-05's scope; commands exist but are not yet wired to any UI or policy check.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-01, WP-03-02 | Hard |

## 7. Current-State Inspection

No status-transition code exists yet; depends on WP-03-01's `account_status` column.

## 8. Proposed Implementation Direction

Proposed: `App\Application\Identity\Commands\{Suspend,Reactivate,Revoke}UserAccountCommand` + handlers; a Fortify `authenticateUsing` closure checking `account_status === 'active'` before allowing login.

## 9. Database Changes

Database Changes: None beyond WP-03-01's `account_status` column (already proposed there); this work package only adds behavior.

## 10. Backend Requirements

Three commands with handlers; a login-time guard rejecting non-active accounts with a specific, non-leaking error message ("Account is not active" rather than differentiating suspended vs. revoked to an unauthenticated caller).

## 11. Web Frontend Requirements

Not Applicable — no UI in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Command authorization (who may suspend/revoke) is explicitly deferred to EPIC-05; this work package's commands exist unguarded until then, and must not be exposed via any route until EPIC-05 completes.

## 14. Security Requirements

Revoked/suspended accounts must be denied at authentication, not merely at a later authorization check — fail closed.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Every status transition must be audit-recorded once WP-06-06 exists; this work package defines the trigger point, WP-06-06 implements the recording.

## 17. Event, Queue, Notification, and Real-Time Requirements

A `UserAccountSuspended`/`UserAccountReactivated`/`UserAccountRevoked` domain event (proposed) per WP-02-04's convention.

## 18. Offline and Synchronization Requirements

Not Applicable directly — revocation-priority sync for mobile is EPIC-12's later concern.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Feature tests: a suspended/revoked user cannot authenticate; an active user can; a reactivated user can again.

## 21. Test Data Requirements

Extend `UserFactory` with `suspended()`/`revoked()` states. Do not create standalone test fixtures.

## 22. Documentation Updates

Record commands and login-guard behavior in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement three status-transition commands/handlers | WP-03-01 complete | Commands implemented, unit-tested |
| TASK-02 | Implement login-time active-status guard | WP-03-02 complete | Guard implemented |
| TASK-03 | Feature-test all three status transitions against login | TASK-01..02 | Tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a suspended account, when a login attempt is made, then authentication is denied.
- **AC-02:** Given a revoked account, when a login attempt is made, then authentication is denied.
- **AC-03:** Given a reactivated account (previously suspended), when a login attempt is made, then authentication succeeds.
- **AC-04:** Given an unauthenticated caller, when denied due to non-active status, then the error message does not distinguish suspended from revoked.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-01, WP-03-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All four acceptance criteria verified by test; no route exposes these commands yet.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output for AC-01 through AC-04.

## 28. Rollback and Recovery Considerations

A wrongly revoked account must be reactivable via the `ReactivateUserAccountCommand` — no destructive action is taken on revocation (data is retained).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-04-01 | Commands exposed via an unguarded route before EPIC-05 authorization exists | Critical | Section 13 explicitly prohibits route exposure until EPIC-05 completes; WP-15-03 checks this | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-04 — Account Status and Revocation Foundation.

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
- Do not expose these commands via any route or UI — authorization (EPIC-05) does not exist yet.
- Denial message must not distinguish suspended from revoked to an unauthenticated caller.
```
