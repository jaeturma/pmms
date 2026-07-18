# PMMS Identity, Authentication, and Session Security

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/identity-model.md](../01-architecture/identity-model.md) · [../01-architecture/runtime-security-architecture.md](../01-architecture/runtime-security-architecture.md) · [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md)

This document defines authentication, session, mobile-token, and device/service-identity security requirements. **No authentication package, middleware, or configuration is selected or created here.** The repository's existing Laravel Fortify scaffolding (registration, password reset, email verification, 2FA, passkeys) is the confirmed foundation these requirements build on.

---

## 1. Authentication Architecture

| Requirement | Direction |
|---|---|
| Unique user accounts | Every User Account maps to exactly one authenticated identity (per [../01-architecture/identity-model.md](../01-architecture/identity-model.md)) — no shared logins |
| Password security | Framework-managed hashing (bcrypt, `BCRYPT_ROUNDS=12` in the current starter-kit default); no plaintext or reversible storage under any circumstance |
| Password reset | Token-based, time-limited, single-use — Fortify's existing mechanism is the confirmed foundation |
| Account recovery | Owned by Identity and Access (BC-02), per [../01-architecture/identity-model.md, Section 5](../01-architecture/identity-model.md#5-account-recovery-ownership) |
| Email verification | Required before an account is treated as fully activated, per Fortify's existing feature |
| MFA readiness | 2FA (TOTP) and passkeys (WebAuthn) are present in the starter-kit scaffolding; **mandatory enforcement scope for high-integrity roles is an open decision** ([../01-architecture/access-open-decisions.md, AD-21](../01-architecture/access-open-decisions.md#ad-21--authentication-mechanism-selection-mfassorecovery)) |
| Risk-based authentication readiness | Architecturally anticipated (device/location anomaly signals triggering elevated challenge) — not implemented; a future-phase capability |
| SSO readiness | Not currently approved or scoped; any future SSO integration is a new external-integration decision, not assumed |
| Login throttling | Required for every login/password-reset/2FA endpoint — Fortify's default rate limiter is the confirmed foundation, exact thresholds are an implementation-phase tuning decision |
| Credential-stuffing protection | Rate limiting plus anomaly detection (Section 37, security events) — specific mechanism not selected here |
| Brute-force protection | Same as credential-stuffing protection, applied per-account and per-IP |
| Account lock and recovery | An account may be temporarily locked after repeated failures; recovery requires identity re-verification — exact lock duration/threshold is an open decision |
| Dormant account handling | Restated from [../01-architecture/access-review-and-revocation.md, Section 5](../01-architecture/access-review-and-revocation.md#5-dormant-account-review) — dormant accounts require periodic review, inactivity threshold TBD |
| Device recognition | A candidate future control (recognizing a previously-trusted device to reduce friction) — not implemented, not required for launch |
| Privileged reauthentication | A privileged action (e.g., entering the Platform Administration area) may require fresh password confirmation, extending Fortify's existing `password_timeout` (currently 3 hours by starter-kit default) — exact scope is an implementation decision |
| Sensitive-action reauthentication | High-integrity actions (result certification, eligibility approval) are candidates for a reauthentication step beyond ordinary session validity — an open decision, not assumed by default |
| Mobile token lifecycle | See Section 4 |
| Service identity authentication | Per [../01-architecture/device-and-service-identity-model.md, Section 5–6](../01-architecture/device-and-service-identity-model.md#5-service-identity-categories) — never a human account's credential |
| Device credential authentication | Per Section 5 below and [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md) |

No exact timeout, lockout threshold, or MFA-enforcement scope is finalized in this document — every such value is either a framework default (noted where applicable) or an open decision in [security-open-decisions.md](security-open-decisions.md).

## 2. Authorization Assurance (Cross-Reference)

The full authorization model — Permission + Scope + Assignment + Resource State + Data Classification + Separation of Duties + Device Trust + Time Validity + Explicit Restrictions — is preserved unchanged from Phase 0.3 and detailed in [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md), not restated here. This document covers authentication (proving identity); that document covers authorization (deciding allowed action) — the distinction is deliberate and never conflated, per [../01-architecture/runtime-security-architecture.md, Section 1](../01-architecture/runtime-security-architecture.md#1-authentication-integration-boundary).

## 3. Session Security

| Control | Direction |
|---|---|
| Secure cookies | `SESSION_SECURE_COOKIE` enforced (`true`) in every environment beyond Local, requiring HTTPS |
| HTTP-only | `http_only=true` — framework default, retained; prevents JavaScript access to the session cookie |
| SameSite | `same_site=lax` — framework default, retained as CSRF mitigation |
| CSRF protection | Enforced for all state-changing Inertia/web requests — Laravel's default token mechanism, retained unchanged |
| Session rotation | Session ID regenerated on login and on privilege change (e.g., a new sensitive assignment becoming active) |
| Session expiry | Idle-timeout and absolute-timeout mechanisms are architecturally required; exact durations are an open decision, not invented here |
| Idle timeout readiness | A session inactive beyond a policy-defined window should expire — duration TBD |
| Absolute timeout readiness | A session should not persist indefinitely regardless of activity — duration TBD |
| Session invalidation | Revoking a user account, role, or sensitive assignment invalidates the affected session's authority on the next request, per [../01-architecture/access-review-and-revocation.md, Section 10](../01-architecture/access-review-and-revocation.md#10-session-invalidation) |
| Concurrent-session policy | Whether multiple simultaneous sessions per account are permitted, and whether privileged roles warrant stricter single-session enforcement, is an open decision |
| Privilege-change invalidation | A session's effective authority reflects the current, not the login-time, assignment state — restated from the "complete mediation" principle |
| Password-change invalidation | Changing a password invalidates other active sessions for that account, forcing re-authentication elsewhere |
| Device-loss response | Reported device loss triggers session/credential invalidation for that device, per [../01-architecture/device-and-service-identity-model.md, Section 4](../01-architecture/device-and-service-identity-model.md#4-device-loss) |
| Remember-me restrictions | If offered, excluded for high-integrity roles at minimum — an open decision on scope |
| Sensitive-action reauthentication | See Section 1 |
| Session metadata | Device, approximate origin, and creation time are candidate session-audit fields, per [audit-and-security-event-architecture.md](audit-and-security-event-architecture.md) |
| Public-computer behavior | Shared/public-computer usage (a named constraint per [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md)) warrants shorter default session duration guidance for kiosks/shared devices specifically |
| Kiosk-session isolation | A kiosk device's session is scoped to its read-only display purpose and never inherits an operator's broader web session |

No exact timeout value is invented — every duration above is marked TBD pending a security/policy decision.

## 4. Mobile Authentication and Token Security

| Control | Direction |
|---|---|
| Secure token storage | Tokens stored using the platform's secure-storage mechanism (Flutter secure storage), never plaintext shared preferences — detailed in [mobile-device-and-offline-security.md](mobile-device-and-offline-security.md) |
| Short-lived access tokens | Mobile access tokens expire on a short horizon, refreshed rather than long-lived |
| Refresh-token control | A distinct, more tightly controlled token than the access token, revocable independently |
| Token rotation | Refresh tokens rotate on use; a reused/stolen refresh token is detectable |
| Token revocation | Server-side revocation takes effect immediately for connected clients, per [../01-architecture/access-review-and-revocation.md, Section 11](../01-architecture/access-review-and-revocation.md#11-token-invalidation) |
| Device binding | A mobile token is bound to the specific device identity that requested it, not freely transferable |
| User-device relationship | The operator's authentication and the device's trust are independent, combined inputs — never one substituting for the other (per [../01-architecture/device-and-service-identity-model.md, Section 3](../01-architecture/device-and-service-identity-model.md#3-device-trust-principles)) |
| Rooted or compromised device risk | A candidate detection signal (Section, [mobile-device-and-offline-security.md](mobile-device-and-offline-security.md)); response policy (block vs. warn vs. elevated audit) is an open decision |
| Offline authentication window | Bounded per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) — an offline device's cached authorization has an expiry, never indefinite trust |
| Biometric unlock as local convenience only | Device-native biometric unlock (fingerprint/face) gates local app access only — it is never a substitute for the server-side authentication/authorization that governs actual data access |
| No server secrets in the mobile app | No API secret, encryption key, or administrative credential is ever embedded in the Flutter client binary |
| Certificate-pinning evaluation | A candidate control against man-in-the-middle interception on untrusted venue networks — evaluated, not committed, pending mobile-security-tooling decisions |
| App integrity evaluation | A candidate control (detecting a tampered/repackaged app build) — evaluated, not committed |
| Remote logout | Server-side capability to force-terminate a specific device/session's authority |
| Lost-device response | Per [../01-architecture/device-and-service-identity-model.md, Section 4](../01-architecture/device-and-service-identity-model.md#4-device-loss) |
| Minimum supported version policy readiness | Older, potentially insecure client versions should be capable of being denied server access — exact version-support policy is an operational, not architectural, decision |

## 5. Service-Account Security (Cross-Reference)

Service-identity security principles (narrow permissions, named ownership, rotation, no interactive login, no human-password reuse) are defined in full in [../01-architecture/device-and-service-identity-model.md, Sections 5–8](../01-architecture/device-and-service-identity-model.md#5-service-identity-categories) and restated for the security-architecture audience in [infrastructure-runtime-and-network-security.md, Section 4](infrastructure-runtime-and-network-security.md#4-service-account-security) rather than duplicated here.

## 6. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably session timeout values, MFA-enforcement scope for high-integrity roles, mandatory reauthentication scope, and account-lockout thresholds, all currently unresolved.
