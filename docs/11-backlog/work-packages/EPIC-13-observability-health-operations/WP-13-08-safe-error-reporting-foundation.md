# WP-13-08 — Safe Error Reporting Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-13-08 | Title | Safe Error Reporting Foundation |
| Epic | EPIC-13 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 131 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead, Security reviewer |

## 2. Purpose

Ensures unhandled exceptions are captured completely for diagnosis (full detail into structured logs with correlation ID) while users receive only safe, generic messages with an error reference — closing the gap between "developers need stack traces" and "stack traces must never reach a browser," per ADR-0008 and the WP-10-01 error contract.

## 3. Architecture Sources

[../../../01-architecture/observability-and-error-handling.md](../../../01-architecture/observability-and-error-handling.md), ADR-0008; consumes WP-13-01 (structured logging), WP-13-02 (correlation), WP-14-03 (log redaction), WP-02-07/WP-10-01 (exception translation and error contract).

## 4. Scope

Exception-handler integration writing full exception detail (class, message, trace, sanitized context) to the structured log channel with correlation ID; verified production-mode behavior: `APP_DEBUG=false` renders no stack trace, exception message, or internal path in any HTTP response (HTML or JSON); user-facing errors carry the correlation-derived error reference per WP-10-01; redaction (WP-14-03) applied to logged exception context.

## 5. Explicit Exclusions

Does not integrate an external error-tracking service (Sentry etc. — future, separately-authorized decision); does not redesign the exception-translation hierarchy (WP-02-07); does not build the error-display UI components (WP-10-09/WP-11-11).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-13-01 | Hard |
| WP-13-02 | Hard |
| WP-14-03 | Hard (redaction applied to logged exception context) |

## 7. Current-State Inspection

Laravel's default handler logs exceptions plainly and (with debug off) renders generic error pages; no correlation reference, no redaction, no verified no-leak guarantee for JSON responses.

## 8. Proposed Implementation Direction

Exception-handler reporting hook (proposed, in `bootstrap/app.php`) routing through the structured channel with redacted context; render hook ensuring both HTML and JSON error responses stay generic and carry the error reference.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Reporting hook (full detail, redacted, correlated), rendering verification (generic + reference), debug-mode guardrails.

## 11. Web Frontend Requirements

Not Applicable in this work package — display components consume the contract elsewhere.

## 12. Flutter Requirements

Not Applicable — WP-12-11 owns the mobile counterpart; mobile clients receive the same safe JSON error shape.

## 13. Authorization and Access Control

Not Applicable directly.

## 14. Security Requirements

With `APP_DEBUG=false`: no stack trace, exception class/message, SQL, file path, or environment value in any response; logged context passes WP-14-03 redaction; the test suite must include a guard asserting debug mode is not enabled in the production configuration path.

## 15. Privacy and Data-Governance Requirements

Exception context may capture request input — redaction must strip personal/sensitive fields before writing, per WP-14-03's rules.

## 16. Audit and Activity Events

Not Applicable — errors are operational events, not business audit events; security-relevant exceptions (e.g., repeated authorization failures) are WP-06-03's domain via its own conventions.

## 17. Event, Queue, Notification, and Real-Time Requirements

Queued-job exceptions report through the same hook with the propagated correlation ID (WP-13-02).

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Every unhandled exception becomes one complete, correlated, redacted structured log record — the primary diagnosis artifact for Phase 1.

## 20. Testing Requirements

Tests: JSON and HTML error responses under `APP_DEBUG=false` contain no trace/message/path; error response reference matches the logged record's correlation ID; a thrown exception with sensitive request input produces a redacted log record; queued-job exception carries the originating correlation ID.

## 21. Test Data Requirements

Synthetic sensitive-looking values for redaction tests only.

## 22. Documentation Updates

Record the reporting/rendering design and the debug-mode guardrail in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement reporting hook (structured, correlated, redacted) | WP-13-01/02, WP-14-03 complete | Redacted correlated record test passes |
| TASK-02 | Verify safe rendering for HTML and JSON | TASK-01 | No-leak tests pass |
| TASK-03 | Wire error reference into responses | TASK-02 | Reference matches log record |
| TASK-04 | Add debug-mode guard test | TASK-02 | Guard test passes |

## 24. Acceptance Criteria

- **AC-01:** Given an unhandled exception with `APP_DEBUG=false`, when the response is rendered (HTML or JSON), then it contains no stack trace, exception message, file path, or environment value.
- **AC-02:** Given the same exception, when its log record is written, then it contains full diagnostic detail, the correlation ID, and redacted context.
- **AC-03:** Given the error response, when compared with the log record, then the user-visible error reference resolves to that record's correlation ID.
- **AC-04:** Given a queued-job exception, when reported, then its record carries the originating request's correlation ID.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-13-01, WP-13-02, WP-14-03 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All four criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): paired capture (safe response + full redacted log record), test output.

## 28. Rollback and Recovery Considerations

Handler hooks are additive; removal restores framework defaults. No data migration.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-13-08-01 | A later module catches-and-renders its own exceptions, bypassing the safe path | High | Convention documented; WP-14-10 and WP-15-09 regression-review response bodies | Security reviewer |
| RISK-WP-13-08-02 | Debug mode accidentally enabled in a pilot environment | High | AC-01 guard test plus WP-01-06 environment-hygiene baseline | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-13-08-01 | External error-tracking service selection | Non-blocking for Phase 1 — structured logs only |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-13-08 — Safe Error Reporting Foundation.

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
- No stack trace, exception message, path, or environment value may ever reach a response with debug off.
- Logged exception context must pass redaction before being written.
- Do not integrate an external error-tracking service.
```
