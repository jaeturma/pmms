# WP-10-02 — Validation Error Contract Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-02 | Title | Validation Error Contract Foundation |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 90 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Confirms Laravel's native `ValidationException` shape (`{message, errors: {field: [messages]}}`) is used consistently across both Inertia (where it's automatic) and JSON API responses, extending WP-10-01's general error contract with the field-level detail validation errors specifically need.

## 3. Architecture Sources

ADR-0004.

## 4. Scope

Confirm/document Laravel's default `ValidationException` JSON rendering behavior; ensure it is not overridden by WP-10-01's generic handler (validation errors get their own, more detailed shape).

## 5. Explicit Exclusions

Does not implement any Form Request class for a real capability.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-10-01 | Hard |

## 7. Current-State Inspection

Laravel 13's default `ValidationException` handling, unmodified.

## 8. Proposed Implementation Direction

Confirm WP-10-01's generic exception handler explicitly excludes `ValidationException` from its shape override (Laravel's default validation-error shape is already correct and field-detailed).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Confirmation/test only — no new code beyond ensuring WP-10-01 doesn't accidentally override this.

## 11. Web Frontend Requirements

Inertia's `errors` prop (automatic from `ValidationException`) is the frontend consumption point (WP-10-08 builds form-handling on top).

## 12. Flutter Requirements

This is the exact validation-error shape WP-12-03's Flutter client will parse for form validation.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Validation error messages must not echo back a sensitive field's raw value (e.g., a password field's attempted value).

## 15. Privacy and Data-Governance Requirements

Not Applicable beyond the security requirement above.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test confirming a validation failure on both an Inertia request and a JSON API request each produce the correctly-shaped, unmodified Laravel validation-error response.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the validation-error contract in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Confirm WP-10-01's handler does not override `ValidationException`'s default shape | WP-10-01 complete | Test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a validation failure, when it occurs on either an Inertia or JSON API request, then Laravel's default field-level error shape is preserved, not overridden by WP-10-01's generic handler.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-10-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Criterion verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, example validation-error response (redacted).

## 28. Rollback and Recovery Considerations

Not Applicable — confirmation of existing default behavior.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-02-01 | WP-10-01's generic handler inadvertently overrides validation errors, losing field-level detail | Medium | AC-01 is a standing regression guard | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-02 — Validation Error Contract Foundation.

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
- Do not implement any Form Request for a real capability — confirmation and test only.
```
