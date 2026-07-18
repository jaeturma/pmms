# WP-05-07 — Authorization Decision Service

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-07 | Title | Authorization Decision Service |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Large |
| Priority | P1 | Implementation sequence | 40 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | All (every future authorization check) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, Lead architect |

## 2. Purpose

The single most consequential work package in EPIC-05: implements the actual authority-evaluation logic combining role, permission, scope, and time-valid assignment into one decision — "can this user perform this permission within this scope right now" — as one canonical service every later authorization check (Laravel Policies, frontend capability contract, mobile) calls, so the "never a role-only shortcut" rule from ADR-0003 has exactly one enforcement point.

## 3. Architecture Sources

[../../../../01-architecture/phase-0.3-access-and-assignment-architecture.md](../../../../01-architecture/phase-0.3-access-and-assignment-architecture.md), ADR-0003, [../../../10-review/identity-access-scope-and-assignment-review.md](../../../10-review/identity-access-scope-and-assignment-review.md).

## 4. Scope

Implement `AuthorizationDecisionService::can(User $user, string $permission, Scope $scope): bool` (proposed signature) querying WP-05-05/06's time-valid assignments joined through WP-05-03's role-permission mapping; implement a decision-explanation mode for debugging (why was this denied) without leaking it to end users.

## 5. Explicit Exclusions

Does not implement Laravel Policy classes (WP-05-08 wraps this service); does not implement explicit-denial/security-hold (WP-05-09) or separation-of-duties (WP-05-10) — those extend this service in their own work packages.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-05, WP-05-06 | Hard |

## 7. Current-State Inspection

All prerequisite tables (`roles`, `permissions`, `role_permissions`, `scope_types`, `assignments`) exist by this point per WP-05-01 through WP-05-06.

## 8. Proposed Implementation Direction

Proposed `App\Domain\Access\AuthorizationDecisionService`, a single query joining `assignments` → `roles` → `role_permissions` → `permissions`, filtered by time-validity (WP-05-06) and scope match (exact scope-type + target, non-inheriting per WP-05-04); cached per-request (not cross-request) to avoid re-querying the same check repeatedly within one HTTP request.

## 9. Database Changes

Database Changes: None (reads existing tables only).

## 10. Backend Requirements

One evaluation service; no new tables. Every call is logged with correlation ID for debugging (WP-02-08 integration).

## 11. Web Frontend Requirements

Not Applicable directly — WP-10-05 exposes a *read-only summary* of decisions for frontend usability, never the raw service.

## 12. Flutter Requirements

Not Applicable directly — same pattern as web, via the API contract.

## 13. Authorization and Access Control

This work package **is** the Authorization Decision Service — the sole canonical enforcement point. No other work package implements a parallel authorization check.

## 14. Security Requirements

Fail closed: any exception, timeout, or ambiguous result during evaluation must resolve to deny, never allow; decision-explanation mode (why denied) is never exposed to the end user, only to internal logs/debugging.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Denials on sensitive permissions may be Security-Event-worthy (WP-06-03), evaluated case-by-case once that model exists — not every single denial (that would be excessive volume), but repeated-denial patterns are.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable directly.

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1 — offline authorization snapshots are a future mobile concern (Phase 0.3's offline-authorization-model.md), not built here.

## 19. Observability Requirements

Every evaluation call logged with correlation ID, user, permission, scope, and result (allow/deny) — never the full reasoning chain in production logs (avoid enabling enumeration of the authorization model via logs).

## 20. Testing Requirements

Comprehensive unit tests: valid assignment + matching scope grants; wrong scope denies; revoked assignment denies; expired assignment denies; role with no matching permission denies; nonexistent user/permission denies (never throws an unhandled exception).

## 21. Test Data Requirations

Reuses `AssignmentFactory`, `RoleFactory`, seeded `roles`/`permissions`/`role_permissions`/`scope_types`. Do not create new production-like test data beyond factories.

## 22. Documentation Updates

Record the service's contract and evaluation algorithm in `.ai/architecture.md` addendum, explicitly restating "never a role-only shortcut."

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `AuthorizationDecisionService::can()` with full role+permission+scope+assignment evaluation | WP-05-05, WP-05-06 complete | Unit tests pass |
| TASK-02 | Implement fail-closed exception handling | TASK-01 | Exception-path test passes |
| TASK-03 | Implement per-request evaluation caching | TASK-01 | Performance/repeat-call test confirms single query per unique check |
| TASK-04 | Implement internal-only decision-explanation logging | TASK-01..02 | Log output reviewed, confirmed not user-facing |

## 24. Acceptance Criteria

- **AC-01:** Given a user with a valid, time-current assignment matching the exact permission and scope requested, when evaluated, then the decision is allow.
- **AC-02:** Given a user with an assignment for a different scope than requested, when evaluated, then the decision is deny.
- **AC-03:** Given a user with a revoked or expired assignment, when evaluated, then the decision is deny.
- **AC-04:** Given an internal error during evaluation (e.g., database exception), when it occurs, then the decision defaults to deny, and the error is logged, not silently swallowed as an allow.
- **AC-05:** Given a denial, when the response is inspected by an end user, then no internal reasoning (which specific check failed) is exposed to them.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-05, WP-05-06 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All five acceptance criteria verified; reviewed jointly by Security reviewer and Lead architect given this service's centrality.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): full test-suite output covering all five criteria, including the fail-closed exception path.

## 28. Rollback and Recovery Considerations

Not Applicable — new service, read-only against existing tables, no data risk.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-07-01 | A future work package bypasses this service and implements its own ad hoc authorization check | Critical | WP-15-03 Foundation Authorization Review explicitly checks every authorization-sensitive code path traces back to this one service | Security reviewer |
| RISK-WP-05-07-02 | Per-request caching returns a stale decision if an assignment changes mid-request | Low | Cache scope is per-request only (not cross-request), and mid-request assignment changes are already an edge case outside normal operation | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-07 — Authorization Decision Service.

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
- This must be the single canonical authorization evaluation point — do not create a second, parallel authorization mechanism.
- Every failure mode must resolve to deny, never allow.
- Never expose internal denial reasoning to an end user.
```
