# PMMS Flutter Mobile Experience Architecture

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/flutter-architecture.md](../01-architecture/flutter-architecture.md) · [../03-security/mobile-device-and-offline-security.md](../03-security/mobile-device-and-offline-security.md) · [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md)

This document defines the Flutter mobile, offline, shared-device, and low-bandwidth experience direction. **No Flutter widget, screen, or route is created here** — `mobile/` does not yet exist in this repository.

---

## 1. Flutter Experience Direction

Task-focused mobile flows (each screen serves one clear task, per [../01-architecture/flutter-architecture.md](../01-architecture/flutter-architecture.md)'s mobile-relevant-context scoping) · offline-first indicators (connectivity/sync state is always visible, per Section 3 below) · bottom navigation or contextual routing where appropriate (a mobile-native pattern, not a port of the web sidebar) · platform-native interaction (respecting iOS/Android conventions rather than forcing web-derived patterns) · secure local state (per [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security)) · simple scanning flows (per [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md)) · limited high-density data (a mobile screen never attempts the same information density as the administrative web portal) · sync center (a dedicated, visible place to review pending/failed sync items) · device status (battery, connectivity, last-sync) · assignment-aware home screen (a technical official's home screen surfaces their specific assignments, not a generic dashboard) · push-notification privacy (per Section 4 below).

## 2. Offline Mobile Experience

Extends [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md) and [../03-security/mobile-device-and-offline-security.md, Section 3](../03-security/mobile-device-and-offline-security.md#3-offline-security) into interface behavior:

| State | Interface Behavior |
|---|---|
| Online | Standard interaction, real-time-capable where relevant |
| Offline | Clearly indicated (never ambiguous), restricted to the narrow offline-capable action set per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) |
| Limited connectivity | A distinct state from fully offline — some sync may succeed, some may queue |
| Pending sync | Every locally-captured, not-yet-synced record is visibly marked Pending, never displayed identically to a confirmed server-accepted record |
| Synchronizing | An active, visible progress state |
| Accepted | The record's local marker updates to reflect server confirmation |
| Rejected | Clearly explained, with a path to correct and resubmit |
| Conflict | Routed to human review per [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md) — never silently auto-resolved for high-integrity data |
| Superseded | The local record is marked as superseded by a later server-side version, not silently discarded |
| Device revoked | The app clearly communicates revocation and stops accepting new offline actions |
| Authorization expired | The cached authorization snapshot's expiry is visibly communicated before it becomes a blocking surprise |

**Local draft is never displayed as officially accepted** — restated absolutely, directly extending working rule 33's requirement for explicit state visibility in official score entry, eligibility decisions, and result certification.

## 3. Shared-Device Experience

Where a Flutter device is shared across shift rotations (e.g., a venue-assigned scanner tablet): every session requires the current operator to authenticate individually — restated from [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security); device trust never substitutes for operator identity. The interface makes the current signed-in operator visible at all times and provides a fast, clear sign-out/handoff flow between shifts, minimizing privacy exposure to the next operator per working rule 40.

## 4. Low-Bandwidth Experience

Minimal payload sizes for mobile API responses · progressive/lazy loading of non-essential content · aggressive caching of reference data (sports catalog, schedules) that changes infrequently · image optimization (per [typography-iconography-and-content-style.md, Section 3](typography-iconography-and-content-style.md#3-photography-and-media)) · graceful degradation when a request times out, rather than an indefinite spinner · push-notification content kept minimal, both for bandwidth and for the privacy reason in Section 1 above (no Restricted/Highly Restricted-tier data in a notification preview, restated from [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security)).

## 5. Cross-Platform Consistency (Cross-Reference)

Per [cross-platform-design-system-architecture.md, Section 3](cross-platform-design-system-architecture.md#3-platform-specific-implementations) — Flutter shares terminology, semantic colors, and status vocabulary with React, while using Flutter-native navigation, touch patterns, and device-integration conventions where they genuinely differ from web conventions.

## 6. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably `mobile/` scaffolding timing (a Phase 0.9+ implementation decision, entirely outside this documentation-only phase) and whether offline event-signing (per [../03-security/security-open-decisions.md, SD-19](../03-security/security-open-decisions.md#sd-19--offline-event-signing-adoption)) affects the offline-state interface vocabulary once resolved.
