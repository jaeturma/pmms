# WP-11-10 — Dialog, Drawer, and Confirmation Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-11-10 | Title | Dialog, Drawer, and Confirmation Foundation |
| Epic | EPIC-11 | Phase | Phase 1 | Status | Planned — Not Started |
| Complexity | Medium | Priority | P1 | Implementation sequence | 108 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | UX reviewer |

## 2. Purpose

Extends the existing `dialog.tsx`, `sheet.tsx` (drawer) primitives with a `ConfirmationDialog` component — required for WP-05-11's assignment revoke action and any future destructive/high-stakes action, ensuring a consistent "are you sure" pattern.

## 3. Architecture Sources

[../../../../06-design/](../../../../06-design/).

## 4. Scope

Implement `ConfirmationDialog` (title, description, confirm/cancel actions) composing existing `dialog.tsx`.

## 5. Explicit Exclusions

Does not implement any dialog content for a real capability beyond the generic confirmation pattern.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-11-01, WP-11-08 | Hard |

## 7. Current-State Inspection

`resources/js/components/ui/dialog.tsx`, `sheet.tsx` already exist and are reused.

## 8. Proposed Implementation Direction

`ConfirmationDialog` composing `dialog.tsx`; a `useConfirmation()` hook (proposed) for imperative usage.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

`ConfirmationDialog` component and `useConfirmation()` hook.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable directly — the confirmation UI does not substitute for backend authorization on the confirmed action.

## 14. Security Requirements

Not Applicable.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Component test confirming the confirm/cancel callbacks fire correctly.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the component in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement `ConfirmationDialog` and `useConfirmation()` hook | WP-11-01, WP-11-08 complete | Component test passes |

## 24. Acceptance Criteria

- **AC-01:** Given `ConfirmationDialog`, when the confirm action is clicked, then the provided callback fires exactly once.
- **AC-02:** Given `ConfirmationDialog`, when the cancel action is clicked, then no callback fires and the dialog closes.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-11-01, WP-11-08 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): component test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive component.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-11-10-01 | A destructive action ships without a confirmation step because this component wasn't available/used | Low | Documented as required for any destructive action going forward | UX reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-11-10 — Dialog, Drawer, and Confirmation Foundation.

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
- Reuse existing dialog.tsx/sheet.tsx — do not duplicate them.
```
