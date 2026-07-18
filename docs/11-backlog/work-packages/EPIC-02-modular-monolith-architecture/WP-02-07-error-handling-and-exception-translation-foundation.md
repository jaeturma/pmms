# WP-02-07 — Error Handling and Exception Translation Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-02-07 | Title | Error Handling and Exception Translation Foundation |
| Epic | EPIC-02 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 15 |
| Target release group | Foundation Release A | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | EPIC-10 (API contract) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Defines how domain-layer exceptions (e.g., a business-rule violation) are translated into the API/Inertia error contract WP-10-01 builds — so domain code never needs to know about HTTP status codes, and controllers never need to know domain exception class names individually.

## 3. Architecture Sources

[../../../../01-architecture/phase-0.4-application-integration-and-runtime-architecture.md](../../../../01-architecture/phase-0.4-application-integration-and-runtime-architecture.md).

## 4. Scope

Document a base domain-exception hierarchy (proposed: `DomainException`, `AuthorizationException` extends framework's, `ValidationException` reuse of Laravel's own); document a single exception-to-response translation point (Laravel's exception handler, `bootstrap/app.php`'s `withExceptions`).

## 5. Explicit Exclusions

Does not implement the actual translation logic (that is WP-10-01's job, consuming this convention); does not create any concrete domain exception for a real capability.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-02 | Hard |

## 7. Current-State Inspection

Laravel 13's default exception handling is configured via `bootstrap/app.php`'s `withExceptions()` closure (framework default, unmodified in the starter kit).

## 8. Proposed Implementation Direction

Proposed: `App\SharedKernel\Exceptions\DomainException` base class; translation centralized in `bootstrap/app.php`'s `withExceptions()`, not scattered per-controller `try/catch`.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Convention only; no concrete exception class created for a specific capability yet.

## 11. Web Frontend Requirements

Not Applicable directly — WP-10-01 consumes this.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Convention documents that authorization failures map to a distinct exception type from generic domain-rule violations (403 vs. 422 semantics, consumed by WP-10-01).

## 14. Security Requirements

Convention documents that exception messages sent to clients must never leak internal implementation detail (stack traces, file paths) outside local development.

## 15. Privacy and Data-Governance Requirements

Convention documents that exception messages must not echo back sensitive input values.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Convention documents that every translated exception is logged with its correlation ID before translation (WP-02-08, WP-13-01 integration points).

## 20. Testing Requirements

Not Applicable — no concrete exception yet.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record convention in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Document domain-exception hierarchy | WP-02-02 complete | Hierarchy documented |
| TASK-02 | Document centralized-translation-point convention and no-leak rule | TASK-01 | Documented |
| TASK-03 | Record in `.ai/architecture.md` | TASK-01..02 | Section added |

## 24. Acceptance Criteria

- **AC-01:** Given the convention, when reviewed, then it states exceptions are translated at one centralized point, not per-controller.
- **AC-02:** Given the convention, when reviewed, then it explicitly prohibits leaking stack traces or file paths to clients outside local development.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Convention documented.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): the documented convention.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-02-07-01 | A later work package leaks a stack trace or internal detail in production-mode error response | High | WP-14-06/WP-15-09 security review checks error responses explicitly | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-02-07 — Error Handling and Exception Translation Foundation.

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
- Do not create a concrete domain exception for a real capability.
- Error responses must never leak stack traces outside local development.
```
