# PMMS Flutter Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) · [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md) · [offline-authorization-model.md](offline-authorization-model.md) · [api-and-client-boundaries.md](api-and-client-boundaries.md)

This document defines the mobile application's structure. **No Flutter project, feature, screen, provider, repository, or data source is created here.** No Flutter directory currently exists in the repository (confirmed by inspection); this document is a structural target for when mobile implementation begins.

---

## 1. Recommended Directory Structure

```text
mobile/lib/
├── app/            (app bootstrap, root navigation, environment wiring)
├── core/           (cross-cutting technical concerns: error handling, result types)
├── design_system/  (shared widgets, theme, accessibility)
├── features/       (feature-oriented modules, grouped by bounded context relevant to mobile)
├── infrastructure/ (API client, local database, secure storage, sync engine, device services)
├── routing/        (navigation configuration)
└── shared/         (genuinely cross-feature domain concepts — kept minimal, mirroring laravel-architecture.md's Shared/ discipline)
```

## 2. Layers

### Presentation
Screens, widgets, navigation, user interaction, local presentation state. Mirrors the Inertia Pages discipline in [react-inertia-architecture.md](react-inertia-architecture.md) — no domain logic, no independent authorization decisions.

### Application
Use cases, workflow coordination, authorization awareness (checking cached capability flags — see Section 5), synchronization commands. Mirrors the Laravel Application layer's role: this is where a mobile-initiated action becomes a well-formed request to the sync engine or API client, with local pre-validation.

### Domain
Mobile-relevant domain concepts, offline-safe rules, validation, local state transitions. **A strict subset** of the full Phase 0.2 domain model — Flutter only models the concepts genuinely needed for field operations (e.g., a `ScoreDraft`, an `AccessScanAttempt`), not the full Official Results or Eligibility domain.

### Infrastructure
API client, local database (device-local persistent store), secure storage (credentials, cached authorization snapshot), sync engine, device services (camera/QR scanner, biometrics where used for local reauthentication), push notifications, file transfer.

## 3. Mobile-Relevant Bounded Contexts

Per [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) and [offline-authorization-model.md](offline-authorization-model.md), Flutter's primary field-operation surface covers:

| Context | Mobile Relevance |
|---|---|
| Access Validation (BC-20) | **Critical** — primary offline scanning workflow |
| Scoring (BC-15) | **Critical** — primary offline score-capture workflow |
| Technical Officials (BC-13) | Medium — assignment/schedule reference, acceptance |
| Venue and Schedule (BC-14) | Medium — cached schedule reference |
| Medical Operations (BC-21) | High — incident logging, must work in emergencies |
| Transportation (BC-24) | Medium — dispatch/boarding tracking |
| Billeting (BC-22) | Medium — check-in/out |
| Food Services (BC-23) | Medium–High — meal distribution validation |
| ICT Service Operations (BC-27) | Medium — support ticket logging |
| Security Operations (BC-25) | High — incident reporting |

Contexts not listed here (Eligibility, Official Results certification, Medal Tally, Finance, etc.) are **not** mobile-first surfaces — they may have a read-only mobile view (e.g., "check my assignment," "view published schedule") but their write/decision workflows remain administrative-app/Inertia-first, per the "never final offline" list in [offline-authorization-model.md, Section 8](offline-authorization-model.md#8-actions-requiring-server-confirmation-never-final-offline).

## 4. Record States (Local)

Every mobile-captured record moves through a local state machine, consistent with the Provisional/Final classification in [offline-authorization-model.md, Section 6](offline-authorization-model.md#6-offline-action-classification):

```text
Local Draft → Pending Sync → Uploaded → Accepted / Rejected / Conflict → Superseded (if corrected)
```

- **Local Draft** — captured on-device, not yet queued for upload.
- **Pending Sync** — queued locally, awaiting connectivity.
- **Uploaded** — transmitted to the server, awaiting server-side validation.
- **Accepted** — server confirmed and persisted as authoritative.
- **Rejected** — server declined (e.g., stale assignment, resource-state conflict).
- **Conflict** — a genuine data conflict detected (per [offline-authorization-model.md, Section 11](offline-authorization-model.md#11-conflict-handling-and-idempotency)), surfaced for human reconciliation.
- **Superseded** — a later correction/revision replaced this record; the original remains in history.

## 5. Rules

- **Flutter must not reimplement official business rules independently.** Sport-specific scoring formulas, eligibility criteria, and medal rules live server-side; the mobile app captures facts and defers rule evaluation to the server, consistent with "rules must be source-backed" ([Phase 0.1 Section 8](../00-product/phase-0.1-product-foundation.md#8-product-principles)).
- **The server remains authoritative** — every mobile-captured record is Provisional until server-confirmed (Section 4).
- Offline validation (e.g., "is this score value plausible," "is this participant on today's roster") uses **versioned, approved rule snapshots** downloaded during the last sync — never a rule invented or hard-coded independently in the Flutter codebase.
- Local actions distinguish Draft/Pending-Sync/Uploaded/Accepted/Rejected/Conflicted states explicitly in the UI — a user scanning a credential offline sees clearly that the scan is provisional, not silently treated as final.
- Sensitive local data (cached credential-validity sets, medical incident drafts) is encrypted at rest on the device, consistent with [device-and-service-identity-model.md, "Data encryption concern"](device-and-service-identity-model.md#2-per-device-identity-fields-conceptual).
- **Both device and user identity are considered** for every action, per [offline-authorization-model.md, Section 4–5](offline-authorization-model.md#4-device-binding) — a shared venue device does not substitute for the specific operator's own authentication.
- Sync retries are idempotent — a retried upload after a dropped connection must not create a duplicate record server-side (see [event-and-queue-architecture.md](event-and-queue-architecture.md) for the idempotency-key mechanism this depends on).
- Mobile features are **selectively enabled by role and assignment** — a user with no Access Control Operator assignment does not see scanning functionality; a user with no Medical role does not see incident-recording functionality. This mirrors the capability-flag approach in [react-inertia-architecture.md, Section 6](react-inertia-architecture.md#6-authorization-signal-to-the-frontend).

## 6. Relationship to Offline Synchronization Runtime

The sync engine (Infrastructure layer) implements the conceptual components defined in [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md) — this document defines Flutter's internal structure; that document defines the sync protocol's conceptual shape (sync client, pending-action queue, conflict detection, server validation, etc.) shared between the mobile client and the server-side synchronization API.

## 7. Open Questions

- Specific local database technology for the device-local store (a later implementation-phase decision, not architecturally blocking).
- Whether biometric/PIN local reauthentication is required for shared venue devices — see [access-open-decisions.md, AD-18](access-open-decisions.md#ad-18--offline-reauthentication-mechanism-for-shared-devices).
- Scope of any future public-facing Flutter experience (vs. web-only public portal) — not assumed in this phase; current design targets field-operations staff only, consistent with [Phase 0.1 product-scope.md, Section 10](../00-product/product-scope.md#10-mobile-scope).
