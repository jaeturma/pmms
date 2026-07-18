# WP-12-05 — Authentication Shell Foundation

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-12-05 | Title | Authentication Shell Foundation |
| Epic | EPIC-12 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Medium |
| Priority | P1 | Implementation sequence | 116 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready with Constraints | Primary bounded context | BC-02 Identity/Access |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Implements the first real authenticated API call — a login screen calling the web authentication endpoint (per WP-03-02's documented contract), storing the resulting session/token via WP-12-04's `SecureStorageService`, giving the mobile app its first working end-to-end authenticated flow.

## 3. Architecture Sources

[../../../../01-architecture/identity-model.md](../../../../01-architecture/identity-model.md).

## 4. Scope

Implement a login screen (`lib/features/auth/`) and an `AuthRepository` calling the backend per WP-03-02's contract; store the resulting credential via `SecureStorageService`; implement a logout action clearing it.

## 5. Explicit Exclusions

Does not implement 2FA/passkey support on mobile (web-only in Phase 1); does not implement biometric unlock.

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-12-03, WP-12-04, WP-03-02 | Hard |

## 7. Current-State Inspection

No auth screen exists in Flutter; WP-03-02's documented contract is the reference this work package implements against.

## 8. Proposed Implementation Direction

`lib/features/auth/login_screen.dart`, `lib/features/auth/auth_repository.dart` using `ApiClient` (WP-12-03) and `SecureStorageService` (WP-12-04).

## 9. Database Changes

Database Changes: None (client-side only; the backend endpoint already exists per WP-03-02).

## 10. Backend Requirements

Not Applicable — consumes the existing web authentication contract, no new backend endpoint.

## 11. Web Frontend Requirements

Not Applicable.

## 12. Flutter Requirements

Login screen, `AuthRepository`, logout action.

## 13. Authorization and Access Control

Not Applicable directly in this work package — this establishes identity, not authorization (EPIC-05's decisions still apply server-side to any subsequent request).

## 14. Security Requirements

Credentials must never be logged (even in debug mode); the stored token must be cleared completely on logout, not merely marked inactive client-side.

## 15. Privacy and Data-Governance Requirements

Not Applicable directly beyond the security requirement above.

## 16. Audit and Activity Events

Mobile logins are recorded via the same WP-03-07 listener as web logins, since both hit the same backend endpoint — no separate mobile-specific audit path needed.

## 17. Event, Queue, Notification, and Real-Time Requirements

Not Applicable.

## 18. Offline and Synchronization Requirements

Not Applicable in this work package — a cached credential's offline validity is a future EPIC-12 enhancement beyond Phase 1's foundation scope.

## 19. Observability Requirements

Not Applicable directly.

## 20. Testing Requirements

Widget test for the login screen's basic interaction (input, submit); unit test confirming logout clears stored credentials completely.

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the authentication shell in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Implement login screen and `AuthRepository` | WP-12-03, WP-03-02 complete | Widget test passes |
| TASK-02 | Implement logout with complete credential clearing | WP-12-04 complete | Unit test passes |

## 24. Acceptance Criteria

- **AC-01:** Given valid credentials, when submitted via the login screen, then the resulting token is stored via `SecureStorageService`.
- **AC-02:** Given logout, when performed, then the stored credential is completely cleared, not merely flagged.

## 25. Definition of Ready

Per [../../../phase-1-definition-of-ready.md](../../../phase-1-definition-of-ready.md). WP-12-03, WP-12-04, WP-03-02 complete.

## 26. Definition of Done

Per [../../../phase-1-definition-of-done.md](../../../phase-1-definition-of-done.md). Both criteria verified.

## 27. Completion Evidence

Per [../../../phase-1-completion-evidence-standard.md](../../../phase-1-completion-evidence-standard.md): test output, screenshots.

## 28. Rollback and Recovery Considerations

Not Applicable — new, additive feature.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-12-05-01 | A credential is logged in debug output during development | High | AC review explicitly checks for this; code review discipline | Security reviewer |

## 30. Open Decisions

None.

## 31. Future Claude Code Execution Prompt

```text
Implement WP-12-05 — Authentication Shell Foundation.

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
- Never log credentials, even in debug mode.
- Do not implement 2FA/passkey or biometric unlock on mobile.
```
