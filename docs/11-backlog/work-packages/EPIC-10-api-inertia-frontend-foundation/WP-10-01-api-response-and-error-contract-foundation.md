# WP-10-01 — API Response and Error Contract Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-10-01 | Title | API Response and Error Contract Foundation |
| Epic | EPIC-10 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 89 |
| Target release group | Foundation Release D | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | All | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Lead architect |

## 2. Purpose

Implements WP-02-07's exception-translation convention concretely: a consistent JSON error shape for API responses (`{error: {code, message, correlation_id}}`, proposed) applied via Laravel 13's `bootstrap/app.php` exception handler — the contract WP-12-03's Flutter client and any future direct API consumer relies on.

## 3. Architecture Sources

[../../../../01-architecture/api-and-client-boundaries.md](../../../../01-architecture/api-and-client-boundaries.md), ADR-0004.

## 4. Scope

Implement the exception handler translating `DomainException`/`AuthorizationException`/generic exceptions into the consistent JSON shape for JSON-accepting requests (API/Flutter), while Inertia requests continue using Inertia's own error/flash mechanism (WP-10-06).

## 5. Explicit Exclusions

Does not implement validation-error-specific formatting (WP-10-02's narrower scope); does not implement any actual API endpoint for a real capability.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-02-07 | Hard |

## 7. Current-State Inspection

Laravel 13's default `bootstrap/app.php` exception handling, unmodified.

## 8. Proposed Implementation Direction

Extend `bootstrap/app.php`'s `withExceptions()` closure to render the consistent JSON shape when `$request->expectsJson()`, delegating to WP-02-07's exception hierarchy for the `code`/`message` mapping.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Exception-handler extension; correlation ID (WP-02-08) included in every error response.

## 11. Web Frontend Requirements

Not Applicable in this work package — this is the API-consumer contract, distinct from Inertia's own error handling.

## 12. Flutter Requirements

This is the exact contract WP-12-03 will parse client-side.

## 13. Authorization and Access Control

Authorization-failure responses use a distinct `code` (e.g., `AUTHORIZATION_DENIED`) from generic domain errors, without leaking internal reasoning (per WP-05-07's discipline).

## 14. Security Requirements

Error responses in production mode must never include a stack trace or file path — only in local/debug mode, per WP-02-07's convention.

## 15. Privacy and Data-Governance Requirements

Error messages must not echo back sensitive input values.

## 16. Audit and Activity Events

Not Applicable directly.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable directly — Flutter's own offline error-handling (EPIC-12) builds on this contract.

## 19. Observability Requirements

Every error response includes the correlation ID, linking it back to server-side logs.

## 20. Testing Requirements

Feature tests: a domain exception produces the correct JSON shape; an authorization exception produces a distinct code without leaking reasoning; a production-mode error response contains no stack trace.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the API error contract in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Extend `bootstrap/app.php`'s exception handling for JSON responses | WP-02-07 complete | Feature tests pass |
| TASK-02 | Confirm production-mode responses contain no stack trace | TASK-01 | Test passes |

## 24. Acceptance Criteria

- **AC-01:** Given a domain exception on a JSON-expecting request, when it occurs, then the response matches the documented `{error: {code, message, correlation_id}}` shape.
- **AC-02:** Given production mode (`APP_DEBUG=false`), when an error response is returned, then it contains no stack trace or file path.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-02-07 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): example API error responses (redacted), test output.

## 28. Rollback and Recovery Considerations

Not Applicable — extends existing default exception handling additively.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-10-01-01 | A debug-mode stack trace accidentally leaks into a production response due to misconfiguration | Critical | AC-02 is a standing regression guard; WP-14-06/WP-15-09 re-check | Lead architect |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-10-01 — API Response and Error Contract Foundation.

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
- Production-mode error responses must never include a stack trace or file path.
- Do not implement any real API endpoint — contract/handler only.
```
