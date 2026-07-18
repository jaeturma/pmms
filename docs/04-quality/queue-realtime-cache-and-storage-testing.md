# PMMS Queue, Real-Time, Cache, and Storage Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md) · [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md) · [../01-architecture/object-storage-and-document-runtime.md](../01-architecture/object-storage-and-document-runtime.md)

This document defines testing requirements for Queue/Horizon, Reverb, Redis, MinIO, file uploads, malware scanning, and notification delivery. **No test code, queue configuration, or scanning integration is created here.**

---

## 1. Queue and Horizon Testing

| Target | What to Verify |
|---|---|
| Dispatch | The correct job is dispatched to the correct queue category, per [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md) |
| Queue selection | High-integrity-adjacent work never lands on a bulk/low-priority queue category |
| Priority | The `critical` queue category is processed with isolation from bulk categories, restated from Phase 0.4 |
| Retry | A failed job retries per its configured policy, without duplicating its effect |
| Timeout | A job exceeding its timeout is correctly marked failed, not left indefinitely running |
| Failure | A permanently-failed job lands in a reviewable failed-job state |
| Idempotency | Restated — a retried job produces the same end state as a single successful execution |
| Duplicate delivery | A duplicate dispatch (e.g., from an at-least-once delivery guarantee) is correctly deduplicated |
| Poison message | A malformed job payload that would repeatedly fail is detected and does not endlessly retry |
| Failed-job handling | Failed jobs are reviewable and re-dispatchable through a controlled process |
| Supervisor restart | Horizon supervisor restart doesn't lose or duplicate in-flight work |
| Deployment restart | A deployment-triggered worker restart correctly drains or safely interrupts in-flight jobs |
| Queue isolation | A backlog in one queue category doesn't starve a `critical` category job |
| Sensitive-payload minimization | Queue payloads carry identifiers only, never large sensitive objects, per [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules) |
| Progress reporting | Long-running jobs (e.g., large imports) correctly report progress where the UI depends on it |
| Reconciliation | A periodic reconciliation job correctly identifies and surfaces discrepancies it's designed to catch |

**High-integrity approval must not depend on an unverified asynchronous outcome** — restated absolutely from the phase's working instructions: eligibility approval, result certification, protest resolution, and medal tally certification remain synchronous, in-transaction operations (per [../01-architecture/laravel-architecture.md, Section 7](../01-architecture/laravel-architecture.md#7-synchronous-versus-asynchronous-decisions)); testing must confirm no Critical-tier decision silently depends on a queued job succeeding after the user has already been told it succeeded.

## 2. Reverb and Real-Time Testing

| Target | What to Verify |
|---|---|
| Channel authorization | A subscription to a private/presence channel is correctly authorized server-side |
| Public channels | Public channels carry only Public-tier data, never leak Restricted content |
| Private channels | Private-channel content respects the subscriber's authorization |
| Scope isolation | A subscriber scoped to Meet A never receives Meet B's broadcast events |
| Reconnection | A client that reconnects after a drop correctly re-authorizes and catches up to current state |
| Event ordering | Events are processed in a way that doesn't produce an incorrect intermediate UI state, even if delivery order varies |
| Versioning | A broadcast event's payload version is handled correctly by clients expecting different schema versions during a rollout |
| Duplicate messages | A duplicate broadcast doesn't cause a duplicated UI effect |
| Missing messages | A client that misses a broadcast entirely still reaches correct state through its normal query fallback, per [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md) |
| Throttling | High-frequency broadcast-triggering events are correctly throttled/batched where designed to be |
| High fan-out | A channel with many subscribers (e.g., a popular meet's public scoreboard) doesn't degrade broadcast delivery for other channels |
| Fallback polling | The documented normal-query fallback correctly returns current, correct state |
| State recovery | A client's UI correctly reconciles after an extended disconnection |
| Sensitive-data exclusion | No Restricted/Highly Restricted content ever appears in any broadcast payload, public or private |
| Public scoreboard load | The public scoreboard channel sustains its expected subscriber volume without degrading administrative/scoring-critical Reverb capacity |

## 3. Redis Testing

| Target | What to Verify |
|---|---|
| Cache hit and miss | Cache reads correctly distinguish hit/miss and fall back to the authoritative source on miss |
| Invalidation | A cache entry is correctly invalidated when its source data changes |
| Lock behavior | Distributed locks correctly serialize the operations they protect |
| Lock expiry | A lock that isn't released is correctly expired, preventing indefinite blocking |
| Session behavior if selected | If Redis is ever configured as the session driver, session read/write/expiry behave correctly |
| Rate limits | Rate-limiting counters behave correctly under concurrent load |
| Idempotency keys | Idempotency-key storage and expiry behave correctly |
| Redis outage | The application degrades gracefully — cache misses fall through to MySQL, queued jobs queue elsewhere or delay, nothing authoritative is lost |
| Partial outage | A degraded (slow, not fully down) Redis doesn't cause cascading application-level failures |
| Eviction | Cache eviction under memory pressure doesn't cause incorrect application behavior, only a performance cost |
| Queue continuity | If Redis backs the queue connection, a Redis interruption's effect on queued jobs is understood and tested |
| Recovery | The application correctly resumes normal Redis-dependent behavior once Redis recovers |
| Fallback behavior | Every Redis-dependent feature has a defined, tested fallback |

**Redis failure must not corrupt authoritative state** — restated absolutely; every test scenario above exists specifically to prove this property, consistent with [../02-data/logical-data-architecture.md, Section 2](../02-data/logical-data-architecture.md#2-source-of-truth-model).

## 4. MySQL Testing

Cross-referenced in full detail in [data-database-migration-and-quality-testing.md](data-database-migration-and-quality-testing.md) — constraints, transactions, locking, and historical-record behavior are tested there rather than duplicated here.

## 5. MinIO and File Testing

| Target | What to Verify |
|---|---|
| Upload authorization | An upload is rejected if the requester isn't authorized for the target context/aggregate |
| Temporary upload | The object correctly lands in a quarantine/temporary state before permanent placement |
| Metadata creation | The metadata record (per [../02-data/object-metadata-and-file-lifecycle.md](../02-data/object-metadata-and-file-lifecycle.md)) is correctly created alongside the object |
| Checksum | A checksum is computed and stored at upload time |
| MIME validation | Declared and actual (sniffed) content type are both checked; a mismatch is rejected |
| Size validation | Oversized uploads are rejected |
| Quarantine | The object remains inaccessible to ordinary consumers until scanning clears it |
| Scan status | The scan-status field correctly reflects Pending/Clean/Infected/Failed |
| Permanent placement | A clean, scanned object correctly moves to its permanent, classification-appropriate location |
| Signed URL | Download access uses a correctly-scoped, time-boxed signed URL |
| Expiry | An expired signed URL is correctly rejected |
| Download authorization | Every download re-checks authorization at request time |
| Versioning | Where object versioning is used, prior versions remain retrievable per the versioning policy |
| Replacement | A document replacement correctly creates a new version rather than silently overwriting |
| Retention | Retention-category assignment behaves correctly (though actual periods remain placeholders per Phase 0.5/0.6) |
| Missing object | A metadata record whose object is missing from MinIO is correctly detected by reconciliation |
| Orphan object | An object in MinIO with no corresponding metadata record is correctly detected |
| Backup | MinIO backup captures objects consistently with their metadata |
| Restore | A restored object set correctly reconciles with its metadata |
| Public versus restricted asset policy | Public-tier and Restricted-tier objects are correctly isolated by bucket/policy |

## 6. Malware-Flow Testing

| Scenario | What to Verify |
|---|---|
| Clean file | A clean file correctly passes scanning and becomes accessible |
| Infected file | An infected file is correctly blocked from ever leaving quarantine |
| Scanner timeout | A scan that times out is treated as failed, never as "assume clean" |
| Scanner outage | Uploads correctly queue in quarantine rather than bypassing scanning when the scanner is unavailable |
| False positive | A flagged-but-clean file has a documented, auditable override/re-review path |
| Retry | A failed/timed-out scan is retried per its configured policy |
| Manual review | Files requiring manual review are correctly surfaced to a reviewer |
| Blocked download | An infected or unscanned file is never downloadable by any role, including administrative roles, outside a narrow security-review capability |
| Blocked processing | An infected or unscanned file is never processed (e.g., thumbnail generation) |
| Re-scan | A previously-scanned file can be re-scanned on demand |
| Scan-definition update | (An operational, not test-architecture, concern — noted for completeness) |
| Quarantine cleanup | Quarantined files that fail scanning and exceed a retention window are correctly disposed of |
| Audit trail | Every scan outcome produces an audit-relevant event, per [../03-security/audit-and-security-event-architecture.md, Section 2](../03-security/audit-and-security-event-architecture.md#2-audit-event-categories) |

**No malware-scanning vendor is tested against here** — per [../03-security/file-object-storage-and-malware-security.md, Section 3](../03-security/file-object-storage-and-malware-security.md#3-malware-scanning-architecture), the scanning integration is vendor-neutral; these scenarios are tested against a simulated scanner boundary (per [test-environment-and-service-virtualization.md](test-environment-and-service-virtualization.md)) until a specific vendor is selected.

## 7. Notification Testing

| Target | What to Verify |
|---|---|
| Recipient resolution | The correct recipient(s) are resolved for a given notification trigger |
| Role and scope | Notification targeting respects role/scope boundaries |
| User preference | Where notification preferences exist, they're correctly respected |
| Mandatory notice | A notification marked mandatory (e.g., a security-relevant notice) cannot be silently suppressed by preference |
| In-app notification | In-app notification creation and read-state behave correctly |
| Email | Simulated (never real, in any lower environment) — per [test-environment-and-service-virtualization.md](test-environment-and-service-virtualization.md) |
| SMS | Simulated, same discipline |
| Push | Simulated, same discipline |
| Real-time alert | A Reverb-delivered alert correctly reaches its intended, authorized recipient only |
| Retry | A failed delivery attempt retries per policy |
| Duplicate prevention | A retried notification doesn't duplicate delivery to the recipient |
| Delivery failure | A permanently-failed delivery is surfaced for review, not silently dropped |
| Template version | The correct notification template version is used |
| Sensitive-content minimization | No Restricted/Highly Restricted content appears in a notification body sent through a less-secure channel (e.g., SMS/push), per [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security) |
| Bulk throttling | Bulk notification sends are correctly throttled to avoid provider rate-limit violations |
| Escalation | An escalation-triggering notification (e.g., a medical alert) is correctly prioritized over routine notifications |
| Read state | Read/unread state is correctly tracked |

## 8. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably whether queue/Reverb/Redis integration tests run against real local infrastructure or a lightweight in-memory equivalent during the Feature-test layer, deferring real-infrastructure tests to the Integration layer specifically.
