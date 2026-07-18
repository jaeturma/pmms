# EPIC-03 — Identity, Authentication, and Session Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release B
**Status:** Planned — Not Started

## Purpose

Implement the basic user-account, authentication, and session-security foundation, normalizing the existing Laravel Fortify baseline (login, registration, password reset, 2FA, passkeys — already present in the starter kit) against PMMS's identity model, without implementing enterprise SSO.

## Architecture Sources

[../../../../01-architecture/identity-model.md](../../../../01-architecture/identity-model.md), ADR-0003, ADR-0006.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-03-01](WP-03-01-core-user-account-model-review-and-normalization.md) | Core User Account Model Review and Normalization | Medium | P1 |
| [WP-03-02](WP-03-02-authentication-flow-baseline.md) | Authentication Flow Baseline | Medium | P1 |
| [WP-03-03](WP-03-03-session-security-and-login-protection.md) | Session Security and Login Protection | Medium | P1 |
| [WP-03-04](WP-03-04-account-status-and-revocation-foundation.md) | Account Status and Revocation Foundation | Small | P1 |
| [WP-03-05](WP-03-05-password-reset-and-verification-baseline.md) | Password Reset and Verification Baseline | Small | P1 |
| [WP-03-06](WP-03-06-service-and-device-identity-architectural-skeleton.md) | Service and Device Identity Architectural Skeleton | Medium | P1 |
| [WP-03-07](WP-03-07-authentication-audit-and-security-events.md) | Authentication Audit and Security Events | Medium | P1 |
| [WP-03-08](WP-03-08-authentication-test-coverage.md) | Authentication Test Coverage | Medium | P1 |

## Dependencies

WP-02-01, WP-02-05 (Hard).

## Completion Outcome

A normalized user-account model built on the existing Fortify baseline, with session security, account status/revocation, service/device identity skeleton, and authentication audit wired to EPIC-06.

## Deferred Items

Enterprise SSO implementation; full device-credential rotation cadence (open decision, Phase 0.3).

## Risks

RISK-EPIC03-01 — normalizing the existing Fortify `User` model risks breaking passkey/2FA behavior if not carefully regression-tested against WP-01-04's baseline.
