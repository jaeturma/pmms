# WP-10-06 — Inertia Navigation and Flash Message Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-06 | Title | Inertia Navigation and Flash Message Foundation |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 94 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Formalizes the flash-message pattern (success/error/info banners after a redirect) already partially present via the starter kit's `use-flash-toast.ts` hook, extending it into a consistent convention every future write-action controller uses.

## 3. Architecture Sources

ADR-0009.

## 4. Scope

Confirm and extend the existing `resources/js/hooks/use-flash-toast.ts` hook; document the server-side flash-message convention (`session()->flash('success', ...)`, proposed standard keys).

## 5. Explicit Exclusions

Does not implement toast notifications for real-time/broadcast events (a distinct concern from EPIC-09).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-03 | Hard |

## 7. Current-State Inspection

`resources/js/hooks/use-flash-toast.ts` already exists in the starter kit — this work package extends/confirms it, not replaces.

## 8. Proposed Implementation Direction

Document standard flash keys (`success`, `error`, `info`, `warning`); confirm the existing hook handles all four consistently.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention for flash-key naming; no new backend code beyond documentation.

## 11. Web Frontend Requirements

Confirmation/extension of the existing `use-flash-toast.ts` hook.

## 12. Flutter Requirements

Not Applicable — Flutter uses its own in-app notification mechanism for equivalent feedback.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Flash messages must never echo back sensitive submitted data.

## 15. Privacy and Data-Governance Requirements

Not Applicable beyond the security requirement above.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable — distinct from EPIC-09's async notification system.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Feature test confirming a flash message set server-side appears correctly in the redirected response.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the flash-key convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document standard flash-key convention and confirm existing hook coverage | WP-10-03 complete | Documented, test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a `success`/`error`/`info`/`warning` flash message set server-side, when the page redirects, then the existing hook displays it correctly.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-03 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Not Applicable — confirms/documents existing behavior.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-06-01 | Inconsistent flash-key naming across future controllers | Low | Documented convention as the standing reference | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-06 — Inertia Navigation and Flash Message Foundation.

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
- Extend the existing use-flash-toast.ts hook — do not replace it.
```
