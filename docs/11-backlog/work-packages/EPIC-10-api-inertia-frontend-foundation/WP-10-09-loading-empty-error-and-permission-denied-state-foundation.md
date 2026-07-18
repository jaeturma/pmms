# WP-10-09 — Loading, Empty, Error, and Permission-Denied State Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-09 | Title | Loading, Empty, Error, and Permission-Denied State Foundation |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 97 (closes EPIC-10's core contract) |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-11 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Defines the standard set of UI states every page/component must handle consistently (loading, empty result, error, permission-denied) — the contract WP-11-11's actual component implementations fulfill, and WP-05-11/WP-06-07/WP-07-10's admin pages already need.

## 3. Architecture Sources

ADR-0009.

## 4. Scope

Document the four-state contract; define how a 403 (permission-denied) response from WP-10-01's error contract maps to a dedicated frontend state, not a generic error page.

## 5. Explicit Exclusions

Does not implement the actual visual components (WP-11-11's scope) — this work package defines the contract/states, WP-11-11 builds the components.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-01, WP-10-05 | Hard |

## 7. Current-State Inspection

No standardized state-handling convention exists.

## 8. Proposed Implementation Direction

Document the four states as a TypeScript discriminated union (proposed `PageState = 'loading' | 'empty' | 'error' | 'denied' | 'ready'`) that every listing/detail page's local state follows.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable directly — this is a frontend-state contract, informed by WP-10-01's backend error shape.

## 11. Web Frontend Requirements

The `PageState` type and the convention for transitioning between states, consumed by WP-11-11's components.

## 12. Flutter Requirements

Not Applicable directly — Flutter defines its own analogous state handling once EPIC-12 builds real screens.

## 13. Authorization and Access Control

The `denied` state is specifically for permission-denied responses (403), distinct from generic errors — giving users a clear, non-alarming message rather than a scary error page.

## 14. Security Requirements

The `denied` state's message must not leak why access was denied beyond what's safe to share (per WP-05-07's non-leaking discipline, extended to the frontend).

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

A future `offline`/`stale` state (WP-11-11's broader component set) builds on this same pattern.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Not Applicable directly in this work package — contract definition only, validated once WP-11-11/WP-05-11 use it.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the `PageState` contract in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document the `PageState` discriminated union and transition rules | WP-10-01, WP-10-05 complete | Documented |

## 24. Acceptance Criteria

- **AC-01:** Given the `PageState` contract, when reviewed, then it includes distinct `loading`/`empty`/`error`/`denied`/`ready` states.
- **AC-02:** Given the `denied` state's documented messaging guidance, when reviewed, then it explicitly prohibits leaking internal denial reasoning.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-01, WP-10-05 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented contract.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-09-01 | Different pages implement inconsistent ad hoc state handling instead of this shared contract | Medium | WP-11-11 provides shared components enforcing this contract structurally | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-09 — Loading, Empty, Error, and Permission-Denied State Foundation.

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
- Do not implement the visual components — contract/type definition only, WP-11-11 builds the components.
```
