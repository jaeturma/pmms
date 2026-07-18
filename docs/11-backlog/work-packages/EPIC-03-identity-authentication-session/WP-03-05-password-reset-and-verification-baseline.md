# WP-03-05 — Password Reset and Verification Baseline

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-03-05 | Title | Password Reset and Verification Baseline |
| Epic | EPIC-03 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 21 |
| Target release group | Foundation Release B | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | EPIC-09 (email delivery) | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Verifies and documents the existing Fortify password-reset and email-verification flows (already implemented in the starter kit and covered by `PasswordResetTest.php`/`EmailVerificationTest.php`), confirming they respect the WP-03-04 account-status guard (a revoked account must not be able to reset its password back into usability without reactivation).

## 3. Architecture Sources

[../../../../01-architecture/identity-model.md](../../../../01-architecture/identity-model.md).

## 4. Scope

Confirm existing password-reset/verification tests pass; add a test confirming a revoked account's reset link does not bypass the WP-03-04 login guard; document mail-delivery dependency on WP-09-08 (currently `MAIL_MAILER=log` in `.env.example`).

## 5. Explicit Exclusions

Does not change Fortify's reset/verification controllers; does not implement production email delivery (WP-09-08).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-03-04 | Hard |

## 7. Current-State Inspection

`tests/Feature/Auth/PasswordResetTest.php`, `tests/Feature/Auth/EmailVerificationTest.php`, `tests/Feature/Auth/VerificationNotificationTest.php` (all present in the starter kit); `.env.example` `MAIL_MAILER=log`.

## 8. Proposed Implementation Direction

No new production code beyond one new regression test confirming revoked-account reset behavior.

## 9. Database Changes

Database Changes: None

## 10. Backend Requirements

One new Feature test; no controller/action change.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Not Applicable.

## 13. Authorization and Access Control

Confirms a revoked account cannot regain login capability solely via password reset.

## 14. Security Requirements

Password-reset tokens must remain time-limited and single-use (Fortify default, confirmed not weakened).

## 15. Privacy and Data-Governance Requirements

Not Applicable.

## 16. Audit and Activity Events

Not implemented here — WP-03-07's scope.

## 17. Event, Queue, Notification, and Real-Time Requirements

Confirms email notification dispatch uses the queue (`ShouldQueue`), consistent with EPIC-09's eventual `redis` queue connection.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

Not Applicable.

## 20. Testing Requirements

Existing suite plus one new test: revoked-account password reset does not restore login capability.

## 21. Test Data Requirements

Reuses `UserFactory`. Do not create new test data beyond factory states already established in WP-03-04.

## 22. Documentation Updates

Record findings in `.ai/engineering-baseline.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Run existing password-reset/verification tests | WP-03-04 complete | Output captured |
| TASK-02 | Add regression test: revoked account + password reset does not restore login | TASK-01 | Test passes |

## 24. Acceptance Criteria

- **AC-01:** Given the existing password-reset/verification test suite, when run, then all tests pass unmodified.
- **AC-02:** Given a revoked account, when its password is reset via the standard flow, then it still cannot log in (WP-03-04's guard still applies).

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-03-04 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both acceptance criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output.

## 28. Rollback and Recovery Considerations

Not Applicable — additive test only.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-03-05-01 | Password reset silently bypasses the WP-03-04 status guard, allowing a revoked user back in | High | AC-02 explicitly tests this | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-03-05 — Password Reset and Verification Baseline.

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
- Do not modify Fortify's reset/verification controllers.
```
