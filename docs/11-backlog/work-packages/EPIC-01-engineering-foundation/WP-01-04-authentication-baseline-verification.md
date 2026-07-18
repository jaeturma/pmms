# WP-01-04 — Authentication Baseline Verification

## 1. Work Package Identity

| Field | Value |
|---|---|
| Work Package ID | WP-01-04 |
| Title | Authentication Baseline Verification |
| Epic | EPIC-01 — Engineering Foundation and Repository Baseline |
| Phase | Phase 1 |
| Status | Planned — Not Started |
| Complexity | Small |
| Priority | P1 |
| Implementation sequence | 4 |
| Target release group | Foundation Release A |
| Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints |
| Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None |
| Product owner | To be identified |
| Technical owner | To be identified |
| Required reviewers | Security reviewer |

## 2. Purpose

Verifies the existing Laravel Fortify authentication baseline (login, registration, password reset, email verification, 2FA, passkeys) actually functions as installed, before EPIC-03 normalizes it against PMMS's identity model. Prevents EPIC-03 from assuming Fortify behavior that isn't actually present or working.

## 3. Architecture Sources

[../../../../01-architecture/identity-model.md](../../../../01-architecture/identity-model.md), [../../../10-review/identity-access-scope-and-assignment-review.md](../../../10-review/identity-access-scope-and-assignment-review.md).

## 4. Scope

Run the existing default Fortify-related test suite (`tests/Feature/Auth/*`, `tests/Feature/Settings/SecurityTest.php`); manually confirm login/registration/password-reset/2FA/passkey routes are registered (`php artisan route:list`); record Fortify configuration (`config/fortify.php`) feature flags currently enabled.

## 5. Explicit Exclusions

Does not modify Fortify configuration, the `User` model, or any authentication controller/action; does not normalize the user model (WP-03-01) or build session-security hardening (WP-03-03).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

Inspect `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Actions/Fortify/`, `app/Models/User.php`, `tests/Feature/Auth/`, `tests/Feature/Settings/SecurityTest.php`, `routes/settings.php`. At authoring time: Fortify `^1.37.2` installed with 2FA and passkeys wired (`@laravel/passkeys` frontend package present), `AuthenticationTest`, `EmailVerificationTest`, `PasswordConfirmationTest`, `PasswordResetTest`, `RegistrationTest`, `TwoFactorChallengeTest`, `VerificationNotificationTest` present under `tests/Feature/Auth/`.

## 8. Proposed Implementation Direction

Documentation-only. Record results in `.ai/engineering-baseline.md`.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

Not Applicable (verification-only).

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Not Applicable — no authorization model exists yet at this point in the sequence (EPIC-05 builds it).

## 14. Security Requirements

Confirm 2FA and passkey flows are not silently disabled by a misconfiguration; confirm `BCRYPT_ROUNDS` and session settings are sane defaults for local development.

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not Applicable — audit foundation is EPIC-06.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Recorded test/route output serves as the observability baseline for authentication.

## 20. Testing Requirements

Run the existing Fortify-related Feature tests; record pass/fail per file.

## 21. Test Data Requirements

Uses existing `UserFactory`; no new test data created by this work package.

## 22. Documentation Updates

Extend `.ai/engineering-baseline.md` with authentication baseline findings.

## 23. Tasks

| Task ID | Description | Expected Files/Areas | Preconditions | Verification | Notes |
|---|---|---|---|---|---|
| TASK-01 | Run `tests/Feature/Auth/*` and `tests/Feature/Settings/SecurityTest.php` | None (read-only) | WP-01-01, WP-01-02 complete | Output captured | — |
| TASK-02 | Run `php artisan route:list` and confirm auth routes registered | None (read-only) | None | Route list captured | — |
| TASK-03 | Record `config/fortify.php` enabled features | `config/fortify.php` (read-only) | None | Feature list captured | — |
| TASK-04 | Append results to `.ai/engineering-baseline.md` | `.ai/engineering-baseline.md` | TASK-01..03 complete | File updated | — |

## 24. Acceptance Criteria

- **AC-01:** Given the existing Fortify test suite, when it is run, then pass/fail per file is recorded.
- **AC-02:** Given `php artisan route:list`, when run, then login/registration/password-reset/2FA/passkey routes are confirmed present or their absence is flagged.
- **AC-03:** Given `config/fortify.php`, when inspected, then every enabled feature flag is listed in the baseline document.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-01-01 and WP-01-02 must be Implementation Complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). All authentication test results and route/config findings recorded.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): verbatim test output, route list excerpt, Fortify config summary.

## 28. Rollback and Recovery Considerations

Not Applicable — documentation-only change.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Detection | Escalation | Residual Concern |
|---|---|---|---|---|---|---|
| RISK-WP-01-04-01 | A pre-existing Fortify test failure is not surfaced, and WP-03-01 later assumes a working baseline that wasn't verified | Medium | AC-01 requires recording failures explicitly | Review of completion evidence | Security reviewer | Low |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-01-04 — Authentication Baseline Verification.

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
- Do not modify Fortify configuration or the User model.
- Do not print secret values.
```
