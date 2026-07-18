# WP-14-09 — Support and Impersonation Restriction Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-09 | Title | Support and Impersonation Restriction Foundation |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 142 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | Identity, Audit | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer, Engineering lead |

## 2. Purpose

Establishes the restrictive foundation for support access before any support tooling exists: impersonation is disabled by default at the architectural level, and if enabled ever, only through the controlled mechanism defined here — explicit permission, time-boxed session, unmissable UI indication, and dual-identity audit (actor vs. effective user, per WP-06-04's metadata model). Building the restriction before the feature is the point, per the Phase 0.6 access-review and production-support controls.

## 3. Architecture Sources

[../../../03-security/access-review-production-and-support-controls.md](../../../03-security/access-review-production-and-support-controls.md), [../../../01-architecture/identity-model.md](../../../01-architecture/identity-model.md), ADR-0003, ADR-0006.

## 4. Scope

An impersonation policy contract (proposed: a shared-kernel service defining `canImpersonate`, `beginImpersonation`, `endImpersonation`) that is **disabled by default** (no role/permission grants it in the Phase 1 seed); the actor-vs-effective-user distinction wired through the request context (WP-02-08) so audit (WP-06-04) records both identities whenever they differ; hard restrictions encoded in the contract: no impersonation of users with higher-privilege assignments, no state-changing high-integrity operations while impersonating (enforced list, initially all-blocking), mandatory time-box, mandatory audit events at begin/end; a visible-indication requirement documented for whatever UI later enables it.

## 5. Explicit Exclusions

Does not build a support UI or enable impersonation for any role (deliberately unreachable in Phase 1 — the seed grants it to no one); does not implement support-ticket tooling; does not define the eventual support-role catalog (future, tied to operating-model decisions).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-07 | Hard (permission evaluation) |
| WP-06-06 | Hard (audit recording) |
| WP-02-08 | Hard (dual-identity context) |

## 7. Current-State Inspection

No impersonation capability exists in the starter kit — which is exactly the state this work package preserves while defining the only permissible future shape.

## 8. Proposed Implementation Direction

`App\SharedKernel\Support\ImpersonationService` (proposed) with the restriction rules as non-overridable checks; effective-user accessor on the request context; audit event types `impersonation.begin`/`impersonation.end` (proposed) with actor, effective user, reason, time-box.

## 9. Database Changes

Database Changes: None beyond proposed audit event types flowing through existing WP-06-01 structures.

## 10. Backend Requirements

Service contract, restriction rules, context integration, audit wiring, no grants.

## 11. Web Frontend Requirements

Not Applicable in Phase 1 (no UI enables it); the visible-indication requirement is documented for the future UI.

## 12. Flutter Requirements

Not Applicable — impersonation from mobile is prohibited outright in the contract.

## 13. Authorization and Access Control

Impersonation requires an explicit permission that no Phase 1 role holds; while impersonating, authorization decisions evaluate the **effective user's** permissions intersected with the restriction list — never the support actor's elevated rights; higher-privilege targets are unimpersonatable regardless of permission.

## 14. Security Requirements

The restriction rules live in the service as non-overridable logic, not in configurable policy — loosening them requires a code change and review, by design; begin/end without audit success must fail the operation (audit-write failure aborts impersonation).

## 15. Privacy and Data-Governance Requirements

Impersonation reason is mandatory and recorded; sensitive-view audit conventions (WP-06-05) apply to everything viewed during an impersonated session, attributed to both identities.

## 16. Audit and Activity Events

`impersonation.begin`/`impersonation.end` audit events with actor, effective user, reason, and time-box; all events during impersonation carry both identities per WP-06-04.

## 17. Event, Queue, Notification, and Real-Time Requirements

Jobs dispatched during impersonation carry the dual-identity context (WP-04-07 propagation).

## 18. Offline and Synchronization Requirements

Not Applicable — prohibited on mobile.

## 19. Observability Requirements

Impersonation attempts (allowed or blocked) log through the structured channel.

## 20. Testing Requirements

Tests: no seeded role can impersonate (grant-absence sweep); higher-privilege target blocked even with the permission granted in a test; blocked-operations list enforced during a test impersonation; begin/end audit events recorded with both identities; audit-write failure aborts begin; mobile-origin requests cannot begin impersonation.

## 21. Test Data Requirements

Synthetic users with contrived permission sets for the test-only grant scenarios.

## 22. Documentation Updates

Record the contract, restriction rules, and the "disabled by default, code-change to loosen" posture in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement impersonation service with non-overridable restrictions | WP-05-07, WP-02-08 complete | Restriction tests pass |
| TASK-02 | Wire dual-identity context and audit events | WP-06-06 complete | Both identities on events |
| TASK-03 | Verify no Phase 1 grant and mobile prohibition | TASK-01 | Grant-absence and origin tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given the Phase 1 role/permission seed, when swept, then no role can begin impersonation.
- **AC-02:** Given a test-granted impersonation permission, when the target holds higher-privilege assignments, then impersonation is refused.
- **AC-03:** Given an active impersonation, when any blocked operation is attempted, then it is denied and the denial is audited with both identities.
- **AC-04:** Given impersonation begin/end, when audit write fails (simulated), then the impersonation operation itself fails.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-05-07, WP-06-06, WP-02-08 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): restriction test output, dual-identity audit evidence, grant-absence sweep.

## 28. Rollback and Recovery Considerations

Additive service; nothing grants it, so removal has no user-facing effect.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-09-01 | A future support crunch pressures ad-hoc impersonation outside this contract | High | The contract is the only code path with dual-identity audit; WP-15-03/09 reviews sweep for session-swapping outside it | Security reviewer |
| RISK-WP-14-09-02 | Blocked-operations list drifts stale as modules add high-integrity operations | Medium | List is initially all-blocking; loosening requires the documented code-change review | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-14-09-01 | Eventual support-role catalog and who may ever hold the permission | Non-blocking — nothing holds it in Phase 1 |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-09 — Support and Impersonation Restriction Foundation.

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
- Impersonation must be disabled by default — no Phase 1 role or seed grants it.
- Restriction rules are non-overridable code, not configuration.
- Audit-write failure aborts impersonation — never impersonate unaudited.
- No support UI in this work package.
```
