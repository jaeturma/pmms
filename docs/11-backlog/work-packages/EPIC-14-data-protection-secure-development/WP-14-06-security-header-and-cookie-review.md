# WP-14-06 — Security Header and Cookie Review

## 1. Work Package Identity

| Field | Value | Field | Value |
|---|---|---|---|
| Work Package ID | WP-14-06 | Title | Security Header and Cookie Review |
| Epic | EPIC-14 | Phase | Phase 1 |
| Status | Planned — Not Started | Complexity | Small |
| Priority | P1 | Implementation sequence | 139 |
| Target release group | Foundation Release E | Pilot relevance | Required |
| Architecture readiness classification | Ready for Implementation | Primary bounded context | Cross-cutting |
| Secondary affected contexts | None | Product owner | To be identified |
| Technical owner | To be identified | Required reviewers | Security reviewer |

## 2. Purpose

Reviews and hardens the HTTP security-header and cookie posture of the foundation — inspect what the starter kit actually sends, close the gaps, and lock the result in with regression tests — before any sensitive module ships a page.

## 3. Architecture Sources

[../../../03-security/application-api-and-client-security.md](../../../03-security/application-api-and-client-security.md), [../../../03-security/identity-authentication-and-session-security.md](../../../03-security/identity-authentication-and-session-security.md), ADR-0006.

## 4. Scope

Inspect current response headers and cookie attributes; implement/verify: `X-Content-Type-Options: nosniff`, `X-Frame-Options`/frame-ancestors, `Referrer-Policy`, a Content-Security-Policy compatible with Inertia/Vite (report-only first if a strict policy breaks the starter kit, with the path to enforcement documented), HSTS readiness (documented as environment-dependent — enabled where TLS is guaranteed); cookie attributes: `Secure` (environment-dependent, documented), `HttpOnly`, `SameSite=Lax` (or the reviewed appropriate value) on session and CSRF cookies; regression tests asserting the final posture.

## 5. Explicit Exclusions

Does not configure TLS/certificates or web-server-level headers (infrastructure, DV-01-dependent, deferred); does not implement CORS policy for third-party API consumers (no external API in Phase 1); does not harden the public portal (no public portal exists in Phase 1).

## 6. Dependencies

| Dependency | Classification |
|---|---|
| WP-01-01 | Hard |

## 7. Current-State Inspection

The starter kit's default header/cookie posture has not been formally reviewed; Laravel defaults cover some attributes (HttpOnly, SameSite) — inspection, not assumption, decides what needs changing.

## 8. Proposed Implementation Direction

Header middleware (proposed: one `SecurityHeaders` middleware in the web group) for headers the framework doesn't set; `config/session.php` review for cookie attributes; CSP delivered via the same middleware with an Inertia/Vite-compatible policy.

## 9. Database Changes

Database Changes: None.

## 10. Backend Requirements

Middleware, session-config review, documented environment-dependent settings (Secure, HSTS).

## 11. Web Frontend Requirements

Verify Vite dev/build assets and Inertia navigation work under the chosen CSP — a policy that breaks the app in development gets silently disabled, which is worse than a slightly looser enforced one.

## 12. Flutter Requirements

Not Applicable — mobile clients don't consume these browser protections; API-side posture is unchanged.

## 13. Authorization and Access Control

Not Applicable.

## 14. Security Requirements

This work package is itself the security control; the regression tests are its enforcement.

## 15. Privacy and Data-Governance Requirements

Referrer-Policy prevents URL-borne context leaking to any future external link targets.

## 16. Audit and Activity Events

Not Applicable.

## 17. Event, Queue, Notification, and Real-Time Requirements

CSP must accommodate the Reverb WebSocket origin (connect-src) once WP-09-09 is active — verified, not assumed.

## 18. Offline and Synchronization Requirements

Not Applicable.

## 19. Observability Requirements

If CSP starts in report-only mode, violation reports log through the structured channel so enforcement readiness is measurable.

## 20. Testing Requirements

Feature tests asserting each required header on representative responses (page, JSON, error); cookie-attribute assertions on session/CSRF cookies; a CSP-presence test (enforced or report-only, per the decision recorded).

## 21. Test Data Requirements

Not Applicable. Do not create test data.

## 22. Documentation Updates

Record the final posture (each header, each cookie attribute, environment-dependent items, CSP status and enforcement path) in `.ai/architecture.md` addendum.

## 23. Tasks

| Task ID | Description | Preconditions | Verification |
|---|---|---|---|
| TASK-01 | Inspect and document current posture | WP-01-01 complete | Findings recorded |
| TASK-02 | Implement header middleware and cookie hardening | TASK-01 | Regression tests pass |
| TASK-03 | Establish Inertia/Vite/Reverb-compatible CSP | TASK-02 | App functions under policy; status recorded |

## 24. Acceptance Criteria

- **AC-01:** Given any HTML or JSON response, when headers are inspected, then nosniff, frame protection, and referrer policy are present as documented.
- **AC-02:** Given session and CSRF cookies, when inspected, then HttpOnly and SameSite attributes match the reviewed posture, with Secure documented per environment.
- **AC-03:** Given the CSP decision (enforced or report-only), when the application is exercised, then Inertia navigation, Vite assets, and Reverb connections function, and the status plus enforcement path is documented.

## 25. Definition of Ready

Per [../../phase-1-definition-of-ready.md](../../phase-1-definition-of-ready.md). WP-01-01 complete.

## 26. Definition of Done

Per [../../phase-1-definition-of-done.md](../../phase-1-definition-of-done.md). All three criteria verified.

## 27. Completion Evidence

Per [../../phase-1-completion-evidence-standard.md](../../phase-1-completion-evidence-standard.md): header capture, cookie capture, posture document, test output.

## 28. Rollback and Recovery Considerations

Middleware and configuration only; removal restores defaults.

## 29. Risks and Mitigations

| Risk ID | Risk | Impact | Mitigation | Owner |
|---|---|---|---|---|
| RISK-WP-14-06-01 | CSP left permanently in report-only because enforcement was never scheduled | Medium | Enforcement path documented with a named follow-up checkpoint (WP-15-09 review asks for it) | Security reviewer |
| RISK-WP-14-06-02 | Secure-cookie flag hardcoded on, breaking non-TLS local development | Low | Environment-dependent settings explicitly documented, not hardcoded | Engineering lead |

## 30. Open Decisions

| Decision ID | Question | Blocking Status |
|---|---|---|
| DEC-WP-14-06-01 | CSP enforced vs. report-only at Phase 1 close | Non-blocking — either state acceptable if documented with an enforcement path |

## 31. Future Claude Code Execution Prompt

```text
Implement WP-14-06 — Security Header and Cookie Review.

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
- Inspect actual current behavior before changing anything — do not assume framework defaults.
- The CSP must not break Inertia, Vite, or Reverb — verify by exercising the app.
- Environment-dependent settings (Secure, HSTS) are documented, never hardcoded.
```
