# PMMS Offline Synchronization Runtime Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [offline-authorization-model.md](offline-authorization-model.md) (Phase 0.3 authorization rules) · [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) (Phase 0.2 domain rules) · [flutter-architecture.md](flutter-architecture.md) · [api-and-client-boundaries.md](api-and-client-boundaries.md)

This document gives runtime/technical shape to the offline authorization and domain boundaries already established in Phase 0.2/0.3. **No sync protocol implementation, database schema, or client code is created here.**

---

## 1. Conceptual Components

| Component | Responsibility |
|---|---|
| **Sync client** | Runs on the Flutter device; orchestrates download/upload cycles |
| **Local mobile store** | Device-local persistent storage for cached reference data, authorization snapshot, and the pending-action queue |
| **Device registration** | Server-side record of the device's identity and trust status (per [device-and-service-identity-model.md](device-and-service-identity-model.md)) |
| **Authorization snapshot** | The cached roles/assignments/scopes relevant to this device's operator, per [offline-authorization-model.md, Section 1](offline-authorization-model.md#1-offline-authorization-snapshot) |
| **Reference-data download** | Pull of versioned, cacheable reference data (Sports Catalog definitions, schedule, credential-validity sets) needed for offline operation |
| **Pending action queue** | Locally queued, not-yet-uploaded captured records (scores, scans, incidents) |
| **Change upload** | Batch transmission of the pending-action queue to the Synchronization API when connectivity is available |
| **Server validation** | Server-side revalidation of every uploaded change against current authoritative state (per [offline-authorization-model.md, Section 9](offline-authorization-model.md#9-server-revalidation)) |
| **Conflict detection** | Identifying uploaded changes that conflict with server-side state changed in the interim |
| **Conflict resolution** | Human-reviewed reconciliation for genuine conflicts (never auto-resolved for anything above Low risk tier) |
| **Sync acknowledgment** | Server confirmation that a specific uploaded change was accepted, rejected, or flagged as conflicted |
| **Retry** | Client-side re-attempt of failed uploads, using the same idempotency key |
| **Audit consolidation** | Reconciling locally-buffered audit-relevant events (per [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md)) into the authoritative audit log on sync |
| **Device revocation** | Server-side invalidation of a device's trust, propagated on next sync attempt (per [device-and-service-identity-model.md, Section 4](device-and-service-identity-model.md#4-device-loss)) |
| **Data cleanup** | Local removal of synced-and-confirmed data past its useful local retention (to bound device storage and reduce exposure of stale sensitive data) |

## 2. Record States

```text
Local Draft → Pending Sync → Uploaded → Accepted / Rejected / Conflict → Superseded
```

Identical to [flutter-architecture.md, Section 4](flutter-architecture.md#4-record-states-local) — this document defines the server-side counterpart of the same state machine, ensuring both sides of the sync protocol share one vocabulary.

## 3. Runtime Rules

- **Server remains authoritative.** Every uploaded change is revalidated against current server-side state (Section 1, "Server validation") — a client's local Accepted-looking state is provisional until the server confirms it.
- **Client-generated IDs require a collision-safe strategy** — e.g., UUIDs generated client-side, so a device can create a locally-identified draft record without needing a server round-trip first, while guaranteeing no collision with another device's concurrently-created record.
- **Idempotency keys are required** for every uploaded change — a retried upload (after a dropped connection mid-transmission) must be recognized as the same logical change, not duplicated.
- **Clock timestamps are not trusted as the sole ordering authority** — a device's local clock may drift during extended offline periods (per [offline-authorization-model.md, Section 12](offline-authorization-model.md#12-time-drift)); server-received-order and/or explicit sequence metadata (per [realtime-architecture.md, Section 3](realtime-architecture.md)) supplement local timestamps for anything where ordering matters.
- **High-integrity finality remains server-controlled** — the sync protocol has no code path that allows a client-originated change to become final for eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, or high-risk overrides (identical list to [offline-authorization-model.md, Section 8](offline-authorization-model.md#8-actions-requiring-server-confirmation-never-final-offline)).
- **Offline payloads are encrypted** at rest on the device and in transit during upload.
- **Lost-device revocation is supported** — a revoked device's next sync attempt is rejected outright, and any of its still-pending local changes are flagged for manual review before being trusted (per [device-and-service-identity-model.md, Section 4](device-and-service-identity-model.md#4-device-loss)).
- **Sync errors are understandable to field users** — a rejected or conflicted upload surfaces a clear, actionable message on-device (e.g., "this match was rescheduled while you were offline — please review"), not a generic failure code.
- **Partial sync is recoverable** — if a batch upload is interrupted partway through, already-acknowledged items are not re-uploaded, and the remaining pending items resume from where the batch left off.

## 4. Conflict Detection and Resolution

A conflict occurs when an uploaded change's assumed prior state (e.g., "this match had no score yet") no longer matches the server's actual current state (e.g., another device already uploaded a score for the same match). Per [offline-authorization-model.md, Section 11](offline-authorization-model.md#11-conflict-handling-and-idempotency) and [high-integrity-domain-rules.md](high-integrity-domain-rules.md)'s "no silent mutation" principle:

- A genuine conflict is **never auto-resolved** by picking one value over another.
- The conflicting records are both preserved and surfaced to an authorized human reviewer (e.g., the Result Validator or Technical Delegate) for reconciliation.
- A duplicate (the *same* change re-uploaded, identified via idempotency key) is **not** a conflict — it is silently deduplicated.

## 5. Sync Priority Ordering

On reconnection, sync order follows [offline-authorization-model.md, Section 15](offline-authorization-model.md#15-recovery-behavior):

1. Revocations (credential and device) — highest priority, minimizing the stale-cache exposure window.
2. Pending provisional actions, oldest first, with duplicate detection.
3. Authorization snapshot refresh.
4. Reference-data refresh (Sports Catalog, schedule).

## 6. Relationship to the Synchronization API

This runtime architecture is the conceptual protocol the Synchronization API (per [api-and-client-boundaries.md, "Synchronization API"](api-and-client-boundaries.md#synchronization-api)) implements. The API category document defines the client-facing contract shape (versioning, idempotency support, batch limits); this document defines the end-to-end behavior that contract must support.

## 7. Open Questions

- Specific conflict-resolution UI/workflow ownership (which role reviews which conflict type) — depends on the same blocked role-authority decisions as other high-integrity workflows (per [role-catalog.md](role-catalog.md)).
- Local data retention duration before cleanup (Section 1) — balances device storage constraints against offline-resilience needs; requires pilot-meet data (mirrors the reasoning in [access-open-decisions.md, AD-17](access-open-decisions.md#ad-17--offline-snapshot-validity-durations)).
- Batch size limits for change upload — implementation-phase tuning.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
