# WP-04-06 — Context Propagation for HTTP and Inertia

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-04-06 | Title | Context Propagation for HTTP and Inertia |
| Epic | EPIC-04 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 30 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-10 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Wires WP-04-05's `CurrentContext` service into the HTTP middleware pipeline and Inertia shared props (consumed by WP-10-03), so every controller and every React page has trusted access to the resolved organization/meet context without re-resolving it independently.

## 3. Architecture Sources

ADR-0012 (context propagation rule).

## 4. Scope

Implement a middleware resolving `CurrentContext` once per request and binding it to the container; expose a minimal, non-sensitive context summary via Inertia shared props (organization name/ID, meet name/ID — not full records).

## 5. Explicit Exclusions

Does not implement job/event propagation (WP-04-07); does not implement the full Inertia shared-props contract (WP-10-03) beyond the context fields.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-04-05 | Hard |

## 7. Current-State Inspection

`app/Http/Middleware/HandleInertiaRequests.php` exists (starter-kit default, shares `auth.user` and appearance settings only).

## 8. Proposed Implementation Direction

Add a new middleware (proposed `ResolveCurrentContext`) before `HandleInertiaRequests` in the pipeline; extend `HandleInertiaRequests::share()` with a `context` key (organization/meet summary only).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Middleware ordering must guarantee context resolution happens before any controller action executes.

## 11. Web Frontend Requirements

Inertia `usePage().props.context` becomes available to every page (types added to `resources/js/types/`).

## 12. Flutter Requirements

Not Applicable — Flutter uses the API contract (WP-04-07/EPIC-10), not Inertia.

## 13. Authorization and Access Control

Not Applicable directly — this work package propagates context, EPIC-05 makes decisions with it.

## 14. Security Requirements

Only non-sensitive context summary fields are shared to the frontend (per WP-10-04's minimization rule, applied here for the first time).

## 15. Privacy and Data-Governance Requirements

Context summary contains no personal data beyond organization/meet names.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable in this work package.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test confirming `context` prop is present and correct on an authenticated page load; confirming it is absent/null pre-authentication.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record middleware pipeline change in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `ResolveCurrentContext` middleware | WP-04-05 complete | Middleware registered, tested |
| TASK-02 | Extend `HandleInertiaRequests::share()` with minimal context summary | TASK-01 | Feature test confirms prop present |

## 24. Acceptance Criteria

- **AC-01:** Given an authenticated request with valid context, when any Inertia page renders, then `context` prop contains the correct organization/meet summary.
- **AC-02:** Given the shared `context` prop, when inspected, then it contains no field beyond organization/meet ID and name.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-04-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, example Inertia prop payload.

## 28. Rollback and Recovery Considerations

Middleware removal reverts to pre-context behavior; no data affected.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-04-06-01 | Middleware ordering error causes context to be unavailable or stale in some controllers | High | AC-01 tested across multiple representative routes | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-04-06 — Context Propagation for HTTP and Inertia.

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
- Shared context prop must contain only non-sensitive summary fields.
```
