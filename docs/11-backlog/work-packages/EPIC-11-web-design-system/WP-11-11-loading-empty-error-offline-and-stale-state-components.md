# WP-11-11 — Loading, Empty, Error, Offline, and Stale-State Components

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-11 | Title | Loading, Empty, Error, Offline, and Stale-State Components |
| Epic | EPIC-11 | Phase | Phase 1 | Status | Planned — Not Started |
| Complexity | Medium | Priority | P1 | Implementation sequence | 109 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Implements the visual components fulfilling WP-10-09's `PageState` contract — `LoadingState`, `EmptyState`, `ErrorState`, `DeniedState` (plus a future-ready `OfflineState`/`StaleState` for EPIC-12's eventual needs) — using the existing `skeleton.tsx`/`spinner.tsx` primitives.

## 3. Architecture Sources

ADR-0009.

## 4. Scope

Implement the four Phase-1-active state components (`LoadingState`, `EmptyState`, `ErrorState`, `DeniedState`) per WP-10-09's contract; stub `OfflineState`/`StaleState` as placeholders for future EPIC-12 use.

## 5. Explicit Exclusions

Does not implement any capability-specific empty/error message content — generic, reusable components only.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-01, WP-10-09 | Hard |

## 7. Current-State Inspection

`resources/js/components/ui/skeleton.tsx`, `spinner.tsx` exist as building blocks.

## 8. Proposed Implementation Direction

Four (plus two stub) components, each accepting a customizable message/icon, composing existing primitives.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

`LoadingState`, `EmptyState`, `ErrorState`, `DeniedState` components (plus `OfflineState`/`StaleState` stubs).

## 12. Flutter Requirements

Not Applicable in this work package — Flutter's equivalent states are a future EPIC-12 concern.

## 13. Authorization and Access Control

`DeniedState`'s default message must not leak internal denial reasoning, per WP-10-09's discipline.

## 14. Security Requirements

Not Applicable beyond the above.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

`OfflineState`/`StaleState` are stubbed here for future use, not implemented with real logic in Phase 1.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Component tests for each of the four active states confirming correct rendering.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record all six components (four active, two stubbed) in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `LoadingState`, `EmptyState`, `ErrorState`, `DeniedState` | WP-11-01, WP-10-09 complete | Component tests pass |
| TASK-02 | Stub `OfflineState`/`StaleState` for future use | TASK-01 | Stubs documented as placeholders |

## 24. Acceptance Criteria

- **AC-01:** Given each of the four active state components, when rendered, then each displays correctly with its default or custom message.
- **AC-02:** Given `DeniedState`'s default message, when reviewed, then it does not leak internal denial reasoning.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-01, WP-10-09 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): component test output, screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive components.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-11-01 | `DeniedState`'s default message inadvertently reveals too much (e.g., naming the specific missing permission) | Medium | AC-02 explicitly reviews this | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-11 — Loading, Empty, Error, Offline, and Stale-State Components.

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
- DeniedState's default message must never leak internal denial reasoning.
- OfflineState/StaleState are stubs only in Phase 1 — no real offline logic implemented.
```
