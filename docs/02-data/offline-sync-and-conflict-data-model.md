# PMMS Offline Replication, Synchronization, and Conflict Data Model

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md) · [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) · [transaction-concurrency-and-locking.md](transaction-concurrency-and-locking.md)

This document gives persistence-layer detail to the offline synchronization runtime established in Phase 0.4. **No physical table is created here.**

---

## 1. Offline Replication Data

### May Be Replicated Offline
User authorization snapshot, device assignment, meet identity, venue identity, sport and event references, assigned participants, accreditation credential validation set, schedule, assigned competition units, meal entitlements, billeting assignments, transport assignments, limited medical alerts where approved (e.g., "this athlete has a known allergy," never the full encounter history).

### Prohibited or Restricted Offline Replication
Full medical records, full eligibility evidence, full finance attachments, platform-wide user directory, unrelated delegations, privileged audit exports, authentication secrets, unrestricted personal information.

This mirrors, and does not alter, [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md)'s existing rules — the persistence-layer contribution here is that every replicable data category above corresponds to a **narrowly-scoped, purpose-built cache table or payload shape** on the device, never a general-purpose local mirror of the full server schema.

## 2. Synchronization Metadata

| Field | Purpose |
|---|---|
| Sync batch ID | Groups a set of changes uploaded together |
| Client ID | Identifies the mobile app instance |
| Device ID | Per [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md) |
| User ID | The operating User Account |
| Meet ID | Scopes the batch to a specific meet |
| Local record ID | The client-generated ULID (per [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)) |
| Server record ID | Assigned/confirmed on acceptance — for offline-originated records, this is the *same* ULID, not a renumbered value |
| Idempotency key | Per Section 4 below |
| Operation type | Create, update (correction), etc. |
| Client version | The mobile app's software version |
| Schema version | Which local-schema shape the payload uses |
| Rule-set version | Which cached Sports Catalog/reference-data version the client validated against locally |
| Client occurred time | Per [database-naming-and-design-standards.md, Section 4](database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards) |
| Server received time | Per same |
| Accepted time | When server-side validation completed successfully |
| Status | Per [../01-architecture/flutter-architecture.md, Section 4](../01-architecture/flutter-architecture.md#4-record-states-local) — Local Draft, Pending Sync, Uploaded, Accepted, Rejected, Conflict, Superseded |
| Conflict type | Per Section 3 below |
| Resolution | How a conflict was resolved |
| Retry count | For failed upload attempts |
| Error category | Structured, per [../01-architecture/observability-and-error-handling.md, Section 1](../01-architecture/observability-and-error-handling.md#1-error-handling-architecture) |
| Payload hash | For integrity verification and duplicate detection |
| Authorization snapshot version | Which cached authorization snapshot the client was operating under |

## 3. Conflict-Resolution Data

### Conflict Categories
Duplicate creation, stale update, assignment expiry, resource-state change, credential revocation, score already finalized, result held, schedule changed, participant reassigned, record deleted or archived, permission revoked, device revoked, rule-set version mismatch, concurrent correction.

### Resolution Outcomes
Accept, reject, merge, supersede, manual review, rebase, retry, convert to draft, escalate.

### Persistence Rules

- Every conflict is a **retained record**, not a transient in-memory decision — a `SyncConflict` entry names the batch/item, the conflict type, the detected discrepancy, and the eventual resolution outcome with actor and time.
- **High-integrity conflicts require server authority and may require human review** — a conflict touching a score, result, eligibility, or credential-revocation record is never auto-resolved by "last write wins" or any purely mechanical rule; it is flagged for the appropriate high-integrity role (per [../01-architecture/high-integrity-access-controls.md](../01-architecture/high-integrity-access-controls.md)) to resolve.
- **Low-stakes conflicts may use simpler mechanical resolution** (e.g., "accept" for a duplicate meal-distribution scan that's clearly the same event double-transmitted) — but the fact that a conflict occurred and was resolved is still recorded, never silently discarded.

## 4. Idempotency Data

### Operations Requiring Idempotency
Offline sync submissions, score submissions, access scans, accreditation issuance, credential revocation, registration imports, notification requests, external webhooks, report generation requests, payment/financial references if later integrated, AI analysis jobs, result publication, medal tally recalculation.

### Persistence Model

| Field | Purpose |
|---|---|
| Idempotency key | Client- or server-generated token uniquely identifying one logical operation attempt |
| Request hash | A hash of the request payload, to detect a key being reused for a *different* request (a conflict, not a legitimate retry) |
| Actor | Who/what initiated the operation |
| Scope | Which meet/context the operation applies to |
| Operation | Which command/action this key corresponds to |
| Created time | When the key was first used |
| Expiry | Idempotency keys are not retained forever — a bounded window after which the same key could theoretically be reused (implementation-phase tuning) |
| Response reference | What the original operation's outcome was, so a retry can return the same result without re-executing |
| Conflict behavior | What happens if the same key arrives with a different payload — rejected as an error, not silently processed as if it were the original request |
| Retention category | Per [retention-archival-and-disposal.md](retention-archival-and-disposal.md) — generally short-lived, since idempotency keys exist to cover the retry window, not for long-term reference |

**Redis is a candidate transient store for idempotency keys** (per [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 16](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#16-redis-usage-boundaries)) — consistent with Redis's approved role, since an idempotency key's value is time-bounded and its loss (in the rare case of a Redis restart during the key's short window) would at worst allow one duplicate processing, not a permanent data-integrity failure, provided the underlying business operation is itself designed to be safely retryable (per [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules)).

## 5. Open Questions

- Idempotency-key expiry window — implementation-phase tuning.
- Whether `SyncConflict` records for low-stakes categories are retained indefinitely or subject to a shorter retention window than high-integrity conflict records.

Tracked in [data-open-decisions.md](data-open-decisions.md).
