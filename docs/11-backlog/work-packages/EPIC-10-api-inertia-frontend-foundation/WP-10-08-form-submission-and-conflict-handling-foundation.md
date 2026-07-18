# WP-10-08 — Form Submission and Conflict Handling Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-08 | Title | Form Submission and Conflict Handling Foundation |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 96 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Domain reviewer |

## 2. Purpose

Establishes the Inertia form-submission pattern (using `useForm()`) and a conflict-handling convention (optimistic-concurrency, e.g., a 409 response when a record was modified since the form was loaded) — a data-integrity concern relevant even in Phase 1's foundation, since WP-04-01/04-02's records can already be concurrently edited.

## 3. Architecture Sources

ADR-0005.

## 4. Scope

Document the `useForm()` submission pattern with WP-10-02's validation-error integration; implement an optimistic-concurrency check (a `updated_at`-based version token, proposed) for update operations.

## 5. Explicit Exclusions

Does not implement conflict-resolution UI (merge/override choice) — Phase 1 simply rejects a stale update with a clear error, deferring richer conflict UX to a future enhancement.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-02 | Hard |

## 7. Current-State Inspection

`useForm()` is already used in the starter kit's existing settings pages (a pattern to extend, not invent).

## 8. Proposed Implementation Direction

A `HasOptimisticConcurrency` trait (proposed) checking a submitted `updated_at` token against the current database value before applying an update, returning a 409 if mismatched.

## 9. Database Changes

Database Changes: None (uses existing `updated_at` columns where present).

## 10. Backend Requirements

`HasOptimisticConcurrency` trait, applied to update commands that need it.

## 11. Web Frontend Requirements

Form-submission pattern documentation; a conflict-error state (feeding WP-10-09's error-state foundation).

## 12. Flutter Requirements

Not Applicable directly — a similar principle applies to Flutter's own future form submissions.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

Not Applicable beyond standard validation.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

A conflict rejection is not itself audit-worthy (no state changed), but the eventual successful update is, per existing conventions.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable directly in Phase 1 — conceptually related to but distinct from EPIC-12's future offline conflict handling.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test: an update with a stale `updated_at` token is rejected with a 409; a current token succeeds.

## 21. Test Data Requirements

Reuses existing factories. Do not create new test data.

## 22. Documentation Updates

Record the form-submission and conflict-handling conventions in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document the `useForm()` submission pattern | WP-10-02 complete | Documented |
| TASK-02 | Implement `HasOptimisticConcurrency` trait | TASK-01 | Feature tests pass |

## 24. Acceptance Criteria

- **AC-01:** Given a stale `updated_at` token on an update request, when submitted, then it is rejected with a 409.
- **AC-02:** Given a current `updated_at` token, when submitted, then the update succeeds.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive trait.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-08-01 | A future update handler skips the concurrency check, allowing silent overwrite of concurrent changes | Medium | WP-15-02 database review checks update handlers for this pattern | Domain reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-08 — Form Submission and Conflict Handling Foundation.

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
- Do not implement conflict-resolution merge UI — reject-with-clear-error only in Phase 1.
```
