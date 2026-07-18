# WP-03-03 — Session Security and Login Protection

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-03 | Title | Session Security and Login Protection |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 19 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | EPIC-14 | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Hardens session behavior (timeout, rate limiting on login attempts, session-fixation prevention) beyond the starter kit's defaults, per Phase 0.6's security architecture, and provides the input WP-14-07 later reviews.

## 3. Architecture Sources

[../../../../03-security/](../../../../03-security/), ADR-0006.

## 4. Scope

Configure/verify Laravel's built-in login-throttling (`ThrottleRequests`), confirm session regeneration on login (`Session::regenerate()`, Fortify default), document a session-timeout value (`SESSION_LIFETIME`) recommendation.

## 5. Explicit Exclusions

Does not implement account lockout beyond Laravel's default throttle; does not implement device/location-based anomaly detection.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-02 | Hard |

## 7. Current-State Inspection

`config/session.php` (`SESSION_LIFETIME=120` default), Fortify's default rate limiter (`fortify.limiters.login`), `RouteServiceProvider`-equivalent rate-limiter registration in Laravel 13's `bootstrap/app.php`.

## 8. Proposed Implementation Direction

Confirm Fortify's default login rate limiter is active; document recommended `SESSION_LIFETIME` (proposed: keep 120 minutes for Phase 1 pilot use, revisit at production readiness).

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Configuration verification and, if needed, minimal `config/fortify.php` rate-limiter confirmation; no new domain code.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable directly — token-based session behavior for Flutter is WP-03-02/WP-12-05's concern.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

Confirms login-throttling is active and session regenerates on authentication; feeds WP-14-07's CSRF/session review.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Failed-login-throttle events are a WP-03-07/WP-06-03 (Security Event Model) integration point.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Feature test confirming login throttling activates after repeated failed attempts (extends existing `AuthenticationTest.php`, does not replace it).

## 21. Test Data Requirements

Reuses `UserFactory`. Do not create new test data.

## 22. Documentation Updates

Record session-security configuration in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Confirm Fortify login rate limiter active | WP-03-02 complete | Confirmed |
| TASK-02 | Confirm session regeneration on login | TASK-01 | Confirmed |
| TASK-03 | Document `SESSION_LIFETIME` recommendation | TASK-01..02 | Documented |

## 24. Acceptance Criteria

- **AC-01:** Given repeated failed login attempts, when the configured throttle threshold is exceeded, then further attempts are rejected with a 429 response.
- **AC-02:** Given a successful login, when it occurs, then the session ID is regenerated (not reused from the pre-auth session).

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Throttle and session-regeneration behavior verified by test.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output demonstrating throttle activation.

## 28. Rollback and Recovery Considerations

Any config change is reversible via `.env`/`config` rollback.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-03-01 | Throttle threshold too permissive for a public-facing login page | Medium | WP-14-05 (rate limiting baseline) reviews this jointly | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-03 — Session Security and Login Protection.

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
- Do not implement anomaly-detection or device-fingerprinting features.
```
