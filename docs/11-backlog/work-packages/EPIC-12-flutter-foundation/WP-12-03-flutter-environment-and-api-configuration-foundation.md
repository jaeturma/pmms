# WP-12-03 — Flutter Environment and API Configuration Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-12-03 | Title | Flutter Environment and API Configuration Foundation |
| Epic | EPIC-12 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 114 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | Cross-cutting (mobile) |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Engineering lead |

## 2. Purpose

Configures the Flutter app's API base URL and an HTTP client wrapper consuming WP-10-01's response/error contract, binding the mobile client to the backend for the first time.

## 3. Architecture Sources

[../../../../01-architecture/api-and-client-boundaries.md](../../../../01-architecture/api-and-client-boundaries.md).

## 4. Scope

Implement environment-based configuration (dev/staging/prod API base URL, proposed via `--dart-define`); an `ApiClient` wrapper parsing WP-10-01's error shape into typed exceptions.

## 5. Explicit Exclusions

Does not implement any real API call beyond a connectivity-check/health-endpoint call (WP-12-05 implements the first real authenticated call).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-12-02, WP-10-01 | Hard |

## 7. Current-State Inspection

No API client exists yet.

## 8. Proposed Implementation Direction

`lib/core/api/api_client.dart` (proposed) using Dart's `http` or `dio` package, parsing WP-10-01's `{error: {code, message, correlation_id}}` shape into a typed `ApiException`.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

`ApiClient` and environment configuration.

## 13. Authorization and Access Control

Not Applicable in this work package — auth tokens are WP-12-05's scope.

## 14. Security Requirements

API base URL must use HTTPS in non-local environments; never hard-code a production URL as the only option.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable in this work package — WP-12-08 handles connectivity state.

## 19. Observability Requirements

Correlation ID from every response is captured for future client-side logging (WP-12-11).

## 20. Testing Requirements

Unit test confirming `ApiClient` correctly parses WP-10-01's error shape into `ApiException`.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the `ApiClient` and environment configuration in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement environment-based API base URL configuration | WP-12-02 complete | Configuration documented |
| TASK-02 | Implement `ApiClient` with error-shape parsing | WP-10-01 complete | Unit test passes |

## 24. Acceptance Criteria

- **AC-01:** Given WP-10-01's error shape, when `ApiClient` receives it, then it is parsed into a typed `ApiException` with the correlation ID preserved.
- **AC-02:** Given the environment configuration, when reviewed, then it supports at least dev and a non-local target, never hard-coding one URL.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-12-02, WP-10-01 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): unit test output.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive code.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-12-03-01 | A hard-coded local IP is left in production configuration | Medium | AC-02 explicitly requires environment-based configuration | Engineering lead |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-12-03 — Flutter Environment and API Configuration Foundation.

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
- Never hard-code a single environment's API URL as the only option.
```
