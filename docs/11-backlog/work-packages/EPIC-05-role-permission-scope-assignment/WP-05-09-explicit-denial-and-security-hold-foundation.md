# WP-05-09 — Explicit Denial and Security Hold Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-09 | Title | Explicit Denial and Security Hold Foundation |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 42 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements an explicit-deny override (a security hold that blocks a specific user/permission/scope combination regardless of an otherwise-valid assignment) — distinct from and stronger than simply not having an assignment, per Phase 0.3's access architecture, for cases like an active investigation where a user's normal authority must be suspended without revoking their underlying role assignment.

## 3. Architecture Sources

[../../../../01-architecture/high-integrity-access-controls.md](../../../../01-architecture/high-integrity-access-controls.md).

## 4. Scope

Implement `security_holds` table (user_id, permission or scope-target, reason, placed_at, placed_by, lifted_at nullable); extend WP-05-07's `AuthorizationDecisionService` to check for an active hold before returning allow — a hold always overrides an otherwise-valid decision.

## 5. Explicit Exclusions

Does not implement a UI for placing/lifting holds; does not implement automated hold-triggering (e.g., from fraud detection) — manual/command-only in Phase 1.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-07 | Hard |

## 7. Current-State Inspection

No security-hold table exists.

## 8. Proposed Implementation Direction

Proposed `security_holds` table; `AuthorizationDecisionService::can()` extended with a hold-check as its final gate, evaluated after the normal role+permission+scope+assignment check succeeds (a hold can only narrow, never widen, authority).

## 9. Database Changes

Proposed `security_holds` table: `id`, `user_id` (FK), `scope_type` nullable, `scope_target_id` nullable (null = applies to all scopes for this user), `reason`, `placed_at`, `placed_by_user_id` (FK), `lifted_at` nullable, `lifted_by_user_id` nullable FK, timestamps. No soft-delete — lifting is `lifted_at`, preserving history.

## 10. Backend Requirements

`PlaceSecurityHoldCommand`, `LiftSecurityHoldCommand` (proposed, unexposed to any route in Phase 1 — same discipline as WP-03-04/WP-05-05); `AuthorizationDecisionService` extended to check holds.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

A security hold is the only mechanism in the authorization model that can override an otherwise-valid decision to deny — it never grants additional authority.

## 14. Security Requirements

Hold placement/lifting must itself be a highly privileged action once EPIC-05's model is fully wired — commands remain unexposed pending a future phase's explicit authorization of who may place/lift holds (AD-09/AD-10, still open per Phase 0.13).

## 15. Privacy and Data-Governance Requirements

`reason` field may contain sensitive information (e.g., relating to an investigation) — classified per WP-14-01 once that exists, access restricted accordingly.

## 16. Audit and Activity Events

Hold placement/lifting is a Critical-severity audit event once WP-06-06 exists — among the most sensitive actions in the system.

## 17. Event, Queue, Notification, and Real-Time Requirements

`SecurityHoldPlaced`/`SecurityHoldLifted` domain events (proposed).

## 18. Offline and Synchronization Requirements

Not Applicable in Phase 1.

## 19. Observability Requirements

Every hold check is logged (allow/deny outcome), same discipline as WP-05-07.

## 20. Testing Requirements

Unit tests: an active hold denies an otherwise-valid decision; a lifted hold no longer denies; a hold scoped to one meet does not affect a different meet's authority for the same user.

## 21. Test Data Requirements

New `SecurityHoldFactory` (proposed).

## 22. Documentation Updates

Record the hold mechanism in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `security_holds` migration and entity | WP-05-07 complete | Migration reversible |
| TASK-02 | Extend `AuthorizationDecisionService` with hold-check gate | TASK-01 | Unit tests pass |
| TASK-03 | Implement place/lift commands (unexposed) | TASK-01..02 | Commands implemented, not routed |

## 24. Acceptance Criteria

- **AC-01:** Given a user with a valid assignment and an active security hold covering the same scope, when evaluated, then the decision is deny.
- **AC-02:** Given a lifted hold, when evaluated, then the underlying assignment's authority is restored.
- **AC-03:** Given a hold scoped to Meet A, when the same user's authority for Meet B is evaluated, then the hold does not affect it.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All three criteria verified; commands unexposed.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): migration status, test output for all three criteria.

## 28. Rollback and Recovery Considerations

Migration reversible; lifting a hold is non-destructive (row retained with `lifted_at`).

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-09-01 | Hold mechanism accidentally implemented as additive (granting) rather than restrictive (denying only) | Critical | AC-01/AC-02 explicitly test the restrictive-only behavior | Security reviewer |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-05-09-01 | Who is authorized to place/lift a security hold (AD-09/AD-10)? | Non-blocking for Phase 1 — commands remain unexposed until resolved |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-09 — Explicit Denial and Security Hold Foundation.

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
- A security hold may only restrict authority, never grant it.
- Do not expose place/lift commands via any route — AD-09/AD-10 remain unresolved.
```
