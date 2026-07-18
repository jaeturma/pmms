# WP-11-07 — Form Control Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-07 | Title | Form Control Foundation |
| Epic | EPIC-11 | Phase | Phase 1 | Status | Planned — Not Started |
| Complexity | Medium | Priority | P1 | Implementation sequence | 105 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Extends the existing `input.tsx`, `label.tsx`, `checkbox.tsx`, `select.tsx`, `input-error.tsx`, `password-input.tsx`, `input-otp.tsx` primitives into a consistent `FormField` wrapper pattern integrating WP-10-02's validation-error contract, so every future form looks and behaves consistently.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/).

## 4. Scope

Implement a `FormField` component composing label/input/error-message, consuming `useForm()`'s error state (WP-10-08); confirm existing input primitives are reused, not duplicated.

## 5. Explicit Exclusions

Does not implement any form for a real capability (WP-05-11 already exists as the first consumer, built before this work package in sequence — retrofit is out of scope here, future forms use this pattern).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-01, WP-10-02 | Hard |

## 7. Current-State Inspection

`resources/js/components/ui/input.tsx`, `label.tsx`, `checkbox.tsx`, `select.tsx`, `resources/js/components/input-error.tsx`, `password-input.tsx` all exist and are reused, not rebuilt.

## 8. Proposed Implementation Direction

`FormField` composing existing primitives; no new low-level input component created.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

`FormField` component.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Component test confirming `FormField` correctly displays a validation error passed via `useForm()`'s error state.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the `FormField` pattern in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `FormField` composing existing input primitives | WP-11-01, WP-10-02 complete | Component test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a validation error for a field, when `FormField` renders, then the error message displays correctly beneath the input.
- **AC-02:** Given `FormField`, when reviewed, then it reuses existing `input.tsx`/`label.tsx` primitives rather than duplicating them.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-01, WP-10-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): component test output, screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive component.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-07-01 | A future page reimplements form-field wrapping ad hoc instead of using this component | Low | Documented as the standard pattern | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-07 — Form Control Foundation.

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
- Reuse existing input primitives — do not duplicate input.tsx/label.tsx/checkbox.tsx/select.tsx.
```
