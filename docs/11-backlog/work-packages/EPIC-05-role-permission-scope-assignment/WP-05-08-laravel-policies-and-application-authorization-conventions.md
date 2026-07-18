# WP-05-08 — Laravel Policies and Application Authorization Conventions

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-05-08 | Title | Laravel Policies and Application Authorization Conventions |
| Epic | EPIC-05 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 41 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All controllers | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Wraps WP-05-07's `AuthorizationDecisionService` in Laravel's native Policy convention (`Gate::define`/`can()`), so controllers use idiomatic Laravel authorization syntax while every check still routes through the one canonical service — closing the loop between WP-02-03's command-handler convention (authorization checked before handler invocation) and the actual decision logic.

## 3. Architecture Sources

ADR-0003, ADR-0004.

## 4. Scope

Implement a base Policy convention (proposed `App\Policies\Concerns\UsesAuthorizationDecisionService` trait) that every future context-specific Policy class extends; wire it into Laravel's `AuthServiceProvider`/policy discovery.

## 5. Explicit Exclusions

Does not implement any context-specific Policy (e.g., `OrganizationPolicy`) — that belongs to whichever epic first needs a controller-level check (none in Phase 1's current scope, since no admin UI ships yet beyond WP-05-11's foundation).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-05-07 | Hard |

## 7. Current-State Inspection

No Policy classes exist in the starter kit; Laravel 13's policy auto-discovery convention is available by default.

## 8. Proposed Implementation Direction

Proposed `App\Policies\Concerns\UsesAuthorizationDecisionService` trait providing a `authorize(string $permission, Scope $scope)` helper every Policy class can call, delegating to WP-05-07.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

One trait/convention; no context-specific Policy yet.

## 11. Web Frontend Requirements

Not Applicable in this work package.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

This is the idiomatic-Laravel wrapper around WP-05-07 — it must not implement any authorization logic itself, only delegate.

## 14. Security Requirements

The trait must not provide a bypass path (e.g., a "trusted" shortcut) around the underlying service.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Unit test confirming the trait correctly delegates to `AuthorizationDecisionService` and does not itself implement any decision logic (a "does not duplicate logic" architectural test).

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the Policy convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `UsesAuthorizationDecisionService` trait | WP-05-07 complete | Trait delegates correctly, unit-tested |
| TASK-02 | Confirm Laravel's policy auto-discovery is configured correctly | TASK-01 | Confirmed |

## 24. Acceptance Criteria

- **AC-01:** Given the trait, when a Policy method calls `authorize()`, then it delegates entirely to `AuthorizationDecisionService::can()`, with no independent logic.
- **AC-02:** Given Laravel's policy discovery, when a future context-specific Policy is added, then it is auto-discovered without additional registration code.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-05-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): trait implementation, delegation test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new convention, no prior behavior.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-05-08-01 | A future Policy class implements its own logic instead of delegating, creating a second authorization path | High | WP-15-03 checks every Policy class against this convention | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-05-08 — Laravel Policies and Application Authorization Conventions.

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
- Do not implement any context-specific Policy class.
- The trait must only delegate, never implement independent authorization logic.
```
