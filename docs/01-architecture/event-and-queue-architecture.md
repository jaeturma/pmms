# PMMS Event and Queue Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [laravel-architecture.md, Sections 5–7](laravel-architecture.md#5-domain-event-architecture) · [internal-integration-architecture.md](internal-integration-architecture.md) · [domain-events-catalog.md](domain-events-catalog.md)

This document defines PMMS's background-processing (queue) architecture and Laravel Horizon's operational role. **No queue configuration, job class, or Horizon config file is created here.**

---

## 1. Queue Categories

Category names below are **conceptual and require repository/implementation-phase validation** before being encoded as literal Laravel queue names (per working rule, "do not finalize names without repository validation" — see [runtime-open-decisions.md](runtime-open-decisions.md)).

| Category | Purpose | Priority | Retry Policy Direction | Timeout Direction | Idempotency Requirement | Failure Handling | Sensitive-Data Concern | Dead-Letter/Failed-Job Handling | Operational Ownership | Monitoring |
|---|---|---|---|---|---|---|---|---|---|---|
| `critical` | Reliable delivery for events feeding high-integrity workflows (e.g., `ResultCertified` → Medal Tally recalculation) | Highest | Limited retries with alerting on exhaustion, not silent drop | Short, fails fast to surface problems quickly | **Mandatory** | Failure must be visibly surfaced to an operator, never silently swallowed | Payload carries identifiers only, never raw score/eligibility/medical content | Failed jobs require manual review before discard — never auto-purged | ICT Coordinator (ROLE-44), escalated to software architect | Continuous — queue depth and failure rate alerting |
| `default` | Routine application work not otherwise categorized | Normal | Standard Laravel retry/backoff | Standard | Recommended | Standard failed-job logging | Low, but still no raw sensitive payloads | Standard failed-jobs table review | ICT Coordinator | Periodic |
| `notifications` | Email/SMS/push/in-app notification delivery | Normal–Low | Retry with backoff; permanent failure logged, not blocking | Short per attempt | Recommended (avoid duplicate notices) | Failed delivery observable per [notification-architecture.md](notification-architecture.md) | Minimize sensitive content in notification body itself | Failed notifications reviewed periodically, not urgently | Notifications context owner | Periodic, elevated for security-notice failures |
| `documents` | Document/file processing (validation, classification, versioning) post-upload | Normal | Retry with backoff | Longer, proportionate to file size | Recommended | Failure flags the document as needing manual review | File content itself is sensitive — access-controlled processing, not payload-embedded | Failed documents flagged for Secretariat/Records review | Document and Records context owner | Periodic |
| `imports` | Bulk data import (e.g., historical delegation rosters) | Low | Limited retries; partial-failure reporting per chunk | Long, chunked (Section 6) | **Mandatory** (re-running an import must not duplicate records) | Row-level failure reporting, not all-or-nothing | Imported data may include Confidential-classified contact info | Failed rows reported to the initiating administrator | Secretariat / ICT | Per-job progress reporting |
| `exports` | Report/data export generation | Low–Normal | Retry with backoff | Long, chunked | Recommended | User notified of failure with retry option | Export content classification matches source data — access-controlled download (Section, [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md)) | Failed exports surfaced to the requesting user | Reporting and Analytics context owner | Per-job progress reporting |
| `media` | Image/media asset processing (e.g., profile photo resizing) | Low | Retry with backoff | Moderate | Recommended | Failure leaves prior asset intact, does not corrupt state | Public media vs. restricted media processed under separate policies | Failed media jobs logged, low urgency | Media and Communications context owner | Periodic |
| `projections` | Rebuilding/refreshing public and cross-context read models | Normal | Retry with backoff; scheduled rebuild as fallback | Moderate | **Mandatory** (rebuild must be safe to repeat) | Stale projection flagged with last-successful-refresh timestamp, never silently served as fresh | Public projections are already-approved data — low sensitivity by definition | Failed projection jobs trigger a rebuild retry, not silent staleness | Reporting and Analytics / Public Information context owners | Continuous — freshness monitoring |
| `analytics` | Historical/cross-meet analytics computation | Low | Retry with backoff | Long | Recommended | Failure does not block any operational workflow | Aggregate-level, but must still respect classification of underlying data | Failed analytics jobs retried on next scheduled run | Reporting and Analytics context owner | Periodic |
| `integrations` | Any future external-system integration calls | Normal | Retry with backoff, circuit-breaker-aware (Section, [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md)) | Bounded by the external system's own SLA | **Mandatory** | Failure must not silently lose the outbound intent — see the outbox pattern in Section 5 | Governed by the specific integration's data-classification agreement | Failed integration calls require reconciliation review | Software architect (no integrations currently approved — Section 4 of [internal-integration-architecture.md](internal-integration-architecture.md)) | Continuous once any integration exists |
| `maintenance` | Scheduled housekeeping (e.g., expiring stale assignments, cleaning temporary upload objects) | Low | Retry with backoff | Long | Recommended | Failure logged, addressed in next scheduled run | N/A | Failed maintenance jobs reviewed periodically | ICT Coordinator | Periodic |

## 2. Job Rules

- **High-integrity approval must not depend solely on an unconfirmed background job.** Eligibility approval, result certification, protest resolution, medal tally certification, and credential revocation are synchronous, in-request operations (per [laravel-architecture.md, Section 7](laravel-architecture.md#7-synchronous-versus-asynchronous-decisions)) — a queue may carry the *consequences* of these actions (notifications, projection updates) but never the decision itself.
- **Queue payloads carry identifiers, not large sensitive objects** — a job references a Score Record ID and reloads the current record from the authoritative store at execution time, rather than carrying a serialized snapshot that could go stale or leak sensitive data into the queue transport (Redis).
- **Jobs must reload authoritative state** at execution time — never trust a payload's embedded data as still current, since queued jobs may execute minutes or hours after being dispatched.
- **Jobs must recheck applicable authorization or execution authority** where the action still requires it — a job executing on behalf of a user whose assignment was revoked between dispatch and execution must not proceed as if that assignment were still valid (mirrors [authorization-decision-model.md, Section 9](authorization-decision-model.md#9-revocation-considerations)).
- **Jobs must be safe to retry** — every job is designed assuming it may execute more than once for the same logical unit of work (network retry, worker crash-and-restart), and must not produce a duplicate side effect when it does.
- **Jobs must record failure context without exposing sensitive data** — a failed-job log entry says *what* failed and *why* at a technical level, never embedding the sensitive payload itself in a way that widens its exposure (per working rule 28).
- **Bulk work must be chunked** — a large import/export is broken into bounded-size units, never a single monolithic job holding an entire dataset in memory or a single long-running transaction.
- **Long-running jobs must report progress where user-facing** — an export or import a user is waiting on shows progress, not a silent "processing" state with no feedback.

## 3. Laravel Horizon Architecture

- Horizon is the **operational control plane** for Redis-backed queues — the place operators observe queue health, not a business-logic component.
- **Environment-specific worker groups**: Local/Development may run a single worker pool; Production separates worker pools per queue-category priority tier (`critical` isolated from `default`, `imports`/`exports`/`media` isolated from time-sensitive categories) so a burst of bulk import work cannot starve critical event processing.
- **Queue priorities**: `critical` processed ahead of `default`, which is processed ahead of low-priority categories (`analytics`, `maintenance`).
- **Worker balancing**: Horizon's auto-balancing strategy is used to shift capacity toward whichever queue category has the deepest backlog, within the isolation boundaries above.
- **Process isolation for critical queues**: the `critical` queue's workers are never shared with long-running `imports`/`media`/`exports` jobs, so a critical event is never stuck behind a multi-minute bulk job.
- **Retry and timeout visibility, failed-job monitoring, queue latency monitoring, supervisor health**: all surfaced through Horizon's dashboard and fed into the broader observability architecture (see [observability-and-error-handling.md](observability-and-error-handling.md)).
- **Deployment restart behavior**: worker restarts (on deploy) must gracefully finish in-flight jobs where feasible, or safely requeue them — never silently drop an in-flight critical job.
- **Maintenance procedures and sensitive-job visibility restrictions**: Horizon's dashboard access itself is a privileged, audited surface (per [runtime-security-architecture.md](runtime-security-architecture.md)) — visibility into job payloads is restricted consistent with the sensitive-data handling rules above.

**No Horizon configuration is created during this phase.**

## 4. Domain Event Type Distinctions (Cross-Reference)

The distinction among domain event, application event, integration event, real-time broadcast event, audit event, and notification event is defined in [laravel-architecture.md, Section 5](laravel-architecture.md#5-domain-event-architecture) — this document's queue categories are the *transport* for the asynchronous subset of those event types (integration events feeding `integrations`/`projections`/`analytics`, notification events feeding `notifications`), while synchronous domain-event handling (Section 7 of [laravel-architecture.md](laravel-architecture.md)) bypasses the queue entirely.

## 5. Reliable Delivery (Outbox Consideration)

For domain events feeding high-integrity workflows (e.g., `ResultCertified` reliably reaching Medal Tally, `AccreditationRevoked` reliably reaching the offline-sync revocation-priority mechanism in [offline-authorization-model.md](offline-authorization-model.md)), an **outbox-style pattern** should be evaluated: the event is persisted transactionally alongside the state change that produced it, and a separate reliable dispatcher publishes it to the queue — this prevents the failure mode where a state change commits but the corresponding event is lost due to a crash between commit and dispatch. **Whether PMMS implements a formal outbox table/mechanism, or relies on Laravel's `after_commit` queue dispatch semantics as a lighter-weight equivalent, is deferred to the implementation phase** — see [runtime-open-decisions.md](runtime-open-decisions.md).

## 6. Chunking and Bulk Work

Bulk imports/exports are processed in bounded chunks (e.g., N records per job), each independently retryable, with aggregate progress tracked across the full batch (via Laravel's job batching, per the existing `job_batches` table already present in the default Laravel 13 scaffolding's queue configuration). Chunk failures are reported per-chunk, not as an all-or-nothing batch failure, consistent with the `imports` row in Section 1.
