# PMMS Accreditation, QR, Device, and Shared-Station Experience

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md) · [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security) · [../02-data/identifier-and-reference-strategy.md](../02-data/identifier-and-reference-strategy.md)

This document defines the accreditation card, QR scanning, and shared-device/station interface patterns — the platform's highest-throughput, highest-time-pressure, most safety-relevant interaction surfaces. **No credential design, QR encoding scheme, or component is created here.**

---

## 1. Accreditation Card Experience

Name · role or participant category · delegation or organization · meet · credential number (a public identifier, never an internal sequential ID, per [../02-data/identifier-and-reference-strategy.md, Section 3](../02-data/identifier-and-reference-strategy.md#3-public-identifier-rules)) · photo where approved (per [typography-iconography-and-content-style.md, Section 3](typography-iconography-and-content-style.md#3-photography-and-media)) · QR code · validity · access category · status · replacement or revocation indication.

**The QR code never exposes internal IDs or sensitive data directly** — restated absolutely per [../02-data/identifier-and-reference-strategy.md, Section 3](../02-data/identifier-and-reference-strategy.md#3-public-identifier-rules); the QR payload is an opaque, non-guessable reference, resolved server-side against current credential status, never a container for the credential holder's actual data.

## 2. QR Scanner Experience

A deliberately minimal, five-step flow:

1. **Ready state** — the scanner is visibly waiting.
2. **Scan** — the operator presents/scans the credential.
3. **Processing** — a brief, clearly-indicated in-progress state.
4. **Result** — Granted, Denied, Warning, or Offline result, unambiguously distinct (color + icon + text, restated absolutely from working rule 28).
5. **Automatic reset** — the scanner returns to Ready without requiring a manual step, maximizing throughput at high-volume gates.

### Requirements

Large status display (readable at a glance, at distance, under time pressure) · strong audio or vibration feedback where appropriate (a distinct signal for Granted vs. Denied, mattering especially in bright outdoor conditions where visual-only feedback may be missed) · clear color plus icon plus text (restated absolutely) · operator identity (always visible, per Section 3) · device status · offline status (per [flutter-mobile-experience-architecture.md, Section 2](flutter-mobile-experience-architecture.md#2-offline-mobile-experience)) · duplicate-scan indication (a repeat scan of an already-validated credential is flagged, not silently re-granted) · manual lookup fallback (for a damaged/unreadable credential) · privacy-limited display (the operator sees only what's needed to make the access decision — restated from [privacy-security-and-sensitive-data-experience.md](privacy-security-and-sensitive-data-experience.md), never the credential holder's full profile) · fast reset · accessible feedback (per [accessibility-architecture.md, Section 2](accessibility-architecture.md#2-screen-reader-architecture), "QR scan result announcements").

## 3. Shared-Device and Station Experience

Restated absolutely from [flutter-mobile-experience-architecture.md, Section 3](flutter-mobile-experience-architecture.md#3-shared-device-experience) and [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security): every shift/operator change on a shared scanning or score-entry station requires individual re-authentication — device trust is never a substitute for operator identity. The interface always displays the current signed-in operator prominently, and provides a fast, low-friction handoff flow between shifts that clears any operator-specific UI state without requiring a full device restart.

## 4. Device Status and Health Indication

Every device-bound interface (scanner, score-entry terminal) surfaces: connectivity state · battery level where applicable · last-successful-sync time · assigned venue/purpose · software version — extending [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md)'s offline-state vocabulary with device-specific context an operator needs to trust what they're seeing.

## 5. Field-Condition Considerations

Per working rule 32 and the outdoor/field experience contexts named in [experience-vision-and-design-principles.md, Section 5](experience-vision-and-design-principles.md#5-experience-contexts): scanner and device interfaces assume direct sunlight glare, one-handed or gloved operation, and unreliable connectivity as normal operating conditions — informing the large-target, high-contrast, minimal-step design in Sections 1–2, not treated as edge-case hardening applied afterward.

## 6. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably specific audio/haptic feedback patterns per result type and whether dedicated scanner hardware (versus general-purpose mobile devices running the Flutter app) is the confirmed device strategy.
