# PMMS Flutter, Mobile, Device, and Offline Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../01-architecture/flutter-architecture.md](../01-architecture/flutter-architecture.md) · [../03-security/mobile-device-and-offline-security.md](../03-security/mobile-device-and-offline-security.md) · [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md)

This document defines Flutter, mobile, offline-synchronization, and device/scanner testing requirements. **No Flutter test file is created here** — the `mobile/` directory does not yet exist in this repository (confirmed during this phase's inspection); this documentation anticipates its future structure.

---

## 1. Flutter Testing

| Target | What to Verify |
|---|---|
| Widget tests | Individual Flutter widgets render and respond to interaction correctly |
| Application use-case tests | Mobile-side use-case orchestration (mirroring the Application layer pattern) behaves correctly |
| Domain tests | Mobile-side domain logic (where the app has any, e.g., local validation) behaves correctly |
| Repository tests | Data-access abstraction layers correctly mediate between local storage and the API client |
| API-client tests | The mobile API client correctly constructs requests and handles responses/errors |
| Secure-storage tests | Tokens and cached authorization snapshots are correctly written to and read from secure storage, per [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security) |
| Local-database tests | The offline local store correctly persists and queries the narrow, approved replication categories per [../02-data/offline-sync-and-conflict-data-model.md, Section 1](../02-data/offline-sync-and-conflict-data-model.md#1-offline-replication-data) |
| Navigation tests | App navigation correctly reflects the user's current authorization/assignment context |
| Offline queue tests | Actions captured offline correctly queue for later sync |
| Sync tests | Queued actions correctly sync when connectivity returns |
| Conflict tests | Sync conflicts are correctly detected and, for high-integrity data, routed to human review rather than auto-resolved, per [../02-data/offline-sync-and-conflict-data-model.md, Section 3](../02-data/offline-sync-and-conflict-data-model.md#3-conflict-resolution-data) |
| Device-binding tests | The app correctly identifies itself with its bound device credential |
| QR scanning tests | QR capture and validation correctly interpret and reject malformed/malicious payloads, per [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security) |
| Push-notification tests | Push receipt and display correctly avoid rendering Restricted/Highly Restricted content in the notification body |
| Lost-device behavior | The app correctly handles a remote-logout/revocation signal |
| Role and assignment gating | The app correctly restricts available actions to the current user's active assignment |

## 2. Mobile and Offline Testing

| Scenario | What to Verify |
|---|---|
| First login | Initial authentication and authorization-snapshot download succeed |
| Token refresh | Access-token renewal happens transparently before expiry |
| Offline login window | A user can operate within the bounded offline-authorization window, per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) |
| Cached authorization | The cached snapshot correctly gates locally-available actions |
| Expired assignment | An assignment that expires while offline correctly blocks the now-unauthorized action on next validation |
| Revoked device | A revoked device correctly loses access on its next server contact |
| Lost device | Per [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security) |
| Local encryption | Locally-stored data is confirmed encrypted at rest |
| Pending sync | Records awaiting sync are correctly tracked and retried |
| Partial sync | A sync interrupted partway through leaves both device and server in a recoverable, non-corrupted state |
| Retry | A failed sync attempt correctly retries without duplicating already-synced records |
| Duplicate upload | A retried upload is correctly deduplicated via idempotency key, per [../02-data/offline-sync-and-conflict-data-model.md, Section 4](../02-data/offline-sync-and-conflict-data-model.md#4-idempotency-data) |
| Stale record | A record superseded server-side before sync completes is correctly flagged, not silently overwritten |
| Conflict | Per "Conflict tests" above |
| Rejection | A server-rejected sync submission (failed re-validation) is correctly surfaced to the user, not silently dropped |
| Server supersession | A high-integrity record superseded by a server-side action is correctly reflected on the next device sync |
| Local cleanup | Data for a closed meet or deprovisioned device is correctly cleared |
| Clock drift | A significant device-clock discrepancy is correctly flagged, per [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security) |
| Network interruption | The app correctly detects and recovers from a mid-operation connectivity loss |
| Recovery after app restart | Pending offline data survives an app restart/crash and resumes syncing correctly |

## 3. Device Testing

| Target | What to Verify |
|---|---|
| Device registration | Only a properly enrolled, approved device can authenticate |
| Credential issuance | Device credentials are correctly scoped to their declared purpose |
| Meet assignment | A device correctly operates only within its assigned meet |
| Venue assignment | A device correctly operates only within its assigned venue, where applicable |
| Operator sign-in | Every operator authenticates individually on a shared device — device trust never substitutes for operator identity |
| Shared-device accountability | Actions on a shared device are correctly attributed to the currently signed-in operator |
| Offline behavior | Per Section 2 |
| Heartbeat | Device check-in/last-seen tracking behaves correctly |
| Time synchronization | Per "Clock drift" above |
| Revocation | Per "Revoked device" above |
| Version compatibility | An outdated/unsupported client version is correctly identified and, where policy requires, denied |
| Recovery | A device recovers correctly after a connectivity loss or app crash |
| Duplicate scan | A repeated scan of the same credential is correctly handled (accepted/rejected per the specific workflow's rules, e.g., single-entry gate passes) |
| Replay | A previously-used, single-use token/scan is correctly rejected on reuse |
| Tampering | A tamper-detection signal (where implemented) correctly flags a compromised device |
| Lost device | Per Section 2 |

## 4. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably the Flutter test-framework/tooling setup (a Phase 0.8+ decision, once `mobile/` is scaffolded), and whether device/offline testing requires physical hardware or can rely entirely on emulated/simulated environments for the initial test suite.
