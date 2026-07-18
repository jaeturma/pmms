# PMMS Mobile, Device, and Offline Security

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/flutter-architecture.md](../01-architecture/flutter-architecture.md) · [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md) · [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) · [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md) · [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md)

This document defines Flutter, device/scanner, and offline-operation security requirements. **No Flutter code, secure-storage configuration, or platform-specific hardening implementation is created here.**

---

## 1. Flutter Security

| Control | Direction |
|---|---|
| Secure local storage | Tokens and any locally-cached authorization snapshot use the platform's secure-storage mechanism (Keychain on iOS, Keystore-backed storage on Android), never plaintext shared preferences |
| Local-database encryption | The offline local store (per [../01-architecture/flutter-architecture.md](../01-architecture/flutter-architecture.md)) is encrypted at rest on the device |
| Token protection | Restated from [identity-authentication-and-session-security.md, Section 4](identity-authentication-and-session-security.md#4-mobile-authentication-and-token-security) |
| Device binding | Restated from Section 2 below |
| Root or jailbreak risk handling | A rooted/jailbroken device carries elevated risk (weakened OS-level sandboxing); detection is a candidate control, response policy (block/warn/elevated audit) is an open decision |
| Screenshot restrictions where justified | Screens displaying Highly Restricted data (e.g., medical detail) are candidates for OS-level screenshot prevention where the platform supports it |
| Clipboard restrictions where justified | Sensitive fields (credentials, medical detail) avoid enabling copy-to-clipboard where practical |
| Debug logging restrictions | Debug/verbose logging is disabled in release builds; no sensitive data is ever written to device logs |
| Release build hardening | Release builds strip debug symbols and disable development-only tooling |
| API certificate validation | The app validates the server's TLS certificate through the OS's standard trust chain at minimum; certificate pinning is a candidate enhancement (Section, [identity-authentication-and-session-security.md](identity-authentication-and-session-security.md)) |
| Offline data minimization | Only the narrow, explicitly-approved categories from [../02-data/offline-sync-and-conflict-data-model.md, Section 1](../02-data/offline-sync-and-conflict-data-model.md#1-offline-replication-data) are ever stored locally |
| Remote logout | Server-side capability to invalidate a specific device's session/token |
| Lost-device handling | Per [../01-architecture/device-and-service-identity-model.md, Section 4](../01-architecture/device-and-service-identity-model.md#4-device-loss) |
| App integrity checks | A candidate control (detecting a repackaged/tampered app build) — evaluated, not committed |
| Safe QR scanning | QR payload content is validated and never treated as executable or as a trusted URL to auto-navigate to without confirmation |
| Malicious deep-link handling | Deep links are validated against an expected scheme/host before triggering any action |
| Push-notification privacy | Push notification content avoids including Restricted/Highly Restricted-tier data in the notification body itself (which may be visible on a locked screen) |
| No embedded privileged secrets | Restated as absolute — no API secret, encryption key, or administrative credential is ever embedded in the Flutter client binary |

## 2. Device and Scanner Security

Extends [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md) with security-specific detail:

| Control | Direction |
|---|---|
| Device registration | Formal enrollment required before a device can authenticate, per [../01-architecture/device-and-service-identity-model.md, Section 2](../01-architecture/device-and-service-identity-model.md#2-per-device-identity-fields-conceptual) |
| Device identity | Every device has its own credential, distinct from every operator's credential |
| Credential issuance | Issued only to a registered, approved device — never a self-service enrollment for high-trust device categories (scanners, encoders) |
| Meet assignment | A device's operational authority is scoped to its assigned meet |
| Venue assignment | Further scoped to its assigned venue where applicable |
| Purpose restriction | A device is trusted only for its declared operational purpose (scan, score, display) — never broader, restated from [../01-architecture/device-and-service-identity-model.md, Section 3](../01-architecture/device-and-service-identity-model.md#3-device-trust-principles) |
| User sign-in on shared devices | Every operator authenticates individually at the start of their session on a shared device — device trust never substitutes for operator identity |
| Operator accountability | Every action taken on a device is attributed to the currently signed-in operator, not merely "the device" |
| Device health | A candidate monitoring signal (battery, storage, connectivity) informing operational readiness, not a security control per se |
| Clock synchronization | Device clocks are checked against server time; significant drift is a candidate security-event trigger, since accurate timestamps matter for offline-record ordering (per [../02-data/database-naming-and-design-standards.md, Section 4](../02-data/database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards)) |
| Software version | The server can identify and, where necessary, deny access to outdated/unsupported client software versions |
| Revocation | Immediate server-side device-credential revocation, propagating to offline caches as a high-priority sync item |
| Lost device | Per Section 1 and [../01-architecture/device-and-service-identity-model.md, Section 4](../01-architecture/device-and-service-identity-model.md#4-device-loss) |
| Offline validity window | Bounded per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) — never indefinite |
| Tamper detection readiness | A candidate control for high-value device categories (scanners, encoders) — evaluated, not committed to a specific mechanism |
| Physical security | An operational (not software) control — device custody procedures are a DepEd/venue operational concern, acknowledged here as a dependency, not designed |
| Local-data encryption | Restated from Section 1 |
| Remote disablement | A registered device can be remotely disabled independent of whether it is currently online (taking effect on its next check-in) |
| Scan replay prevention | A previously-used access-validation scan/token is not accepted a second time where single-use semantics apply (e.g., single-entry gate passes), per Section, [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md) |
| QR validation integrity | QR credential validation checks the credential's current server-side status (or last-synced cached status while offline), never trusts the QR payload's claimed validity alone |

## 3. Offline Security

| Control | Direction |
|---|---|
| Minimum offline dataset | Restated from [../02-data/offline-sync-and-conflict-data-model.md, Section 1](../02-data/offline-sync-and-conflict-data-model.md#1-offline-replication-data) — only the narrow, explicitly-approved categories replicate |
| Encrypted local storage | Restated from Section 1 |
| Authorization snapshot expiry | An offline device's cached authorization snapshot has a bounded validity window, per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) |
| Device trust | Restated from Section 2 |
| User reauthentication | An offline session requires the operator to have authenticated within the snapshot's validity window — an indefinitely-cached login is not acceptable |
| Revocation lag | A disclosed, bounded, accepted risk (RSK-08 per [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md)) — mitigated, never eliminated, for a genuinely offline-capable design |
| Sync validation | Every synced record is re-validated against current server-side business rules on arrival — offline capture is provisional, never pre-trusted as final |
| Replay protection | Restated from Section 2 |
| Idempotency | Per [../02-data/offline-sync-and-conflict-data-model.md, Section 4](../02-data/offline-sync-and-conflict-data-model.md#4-idempotency-data) |
| Local tampering risk | A device's local store could theoretically be tampered with by its holder — mitigated by treating every offline-originated record as Provisional pending server validation, never as pre-authoritative |
| Clock manipulation risk | A manipulated device clock could misorder offline records — server-received time is always recorded alongside device-claimed time, per [../02-data/database-naming-and-design-standards.md, Section 4](../02-data/database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards), and significant discrepancies are flagged for review |
| Offline event signing evaluation | A candidate future control (cryptographically signing offline-captured records at the point of capture) — evaluated, not committed, pending [../02-data/data-open-decisions.md](../02-data/data-open-decisions.md) and this package's open decisions |
| Lost-device response | Per Section 2 |
| Data cleanup | Local device data for a closed meet or a deprovisioned device is cleared, consistent with data-minimization principles |
| Partial-sync safety | A sync interrupted partway through leaves both the device and server in a recoverable, non-corrupted state — no partial-write data corruption |
| Conflict review | High-integrity sync conflicts require human review, never mechanical auto-resolution, per [../02-data/offline-sync-and-conflict-data-model.md, Section 3](../02-data/offline-sync-and-conflict-data-model.md#3-conflict-resolution-data) |
| Prohibited offline final actions | Restated absolutely from [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md): eligibility approval, official result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, and high-risk overrides are **never final while offline**, with no exception |

## 4. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably rooted/jailbroken-device response policy, offline event-signing adoption, and certificate-pinning commitment, all currently unresolved.
