# PMMS Observability and Error Handling

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [event-and-queue-architecture.md](event-and-queue-architecture.md) · [runtime-security-architecture.md](runtime-security-architecture.md) · [environment-and-configuration-model.md](environment-and-configuration-model.md)

This document defines error handling, logging, observability, and health-check architecture. **No monitoring vendor is selected and no logging/error-handling code is created here.**

---

## 1. Error Handling Architecture

### Error Categories
Validation error, authorization error, authentication error, conflict error, state-transition error, not-found error, rate-limit error, integration error, queue error, file-processing error, synchronization error, infrastructure error, unexpected application error.

### Requirements
- **Stable, user-facing messages** distinct from detailed internal diagnostics — a user sees "This result has already been certified," not a stack trace.
- **Correlation ID** attached to every error, surfaced to the user for support purposes and traceable through logs (Section 2).
- **No sensitive data leakage** in any user-facing error — an authorization-denial message never confirms or denies the existence of a record the user isn't authorized to know about (per [authorization-decision-model.md, Section 6](authorization-decision-model.md#6-user-facing-denial-behavior)).
- **Context-aware logging** — the internal log entry for an error carries full diagnostic context; the user-facing response does not.
- **Retry guidance where applicable** — a rate-limit or transient-integration error tells the user/client when retry is reasonable.
- **Conflict-resolution guidance** — a state-transition or sync conflict error (per [offline-sync-runtime-architecture.md, Section 4](offline-sync-runtime-architecture.md#4-conflict-detection-and-resolution)) explains what changed, not just that something failed.
- **Localized/accessible messaging readiness** — error message architecture anticipates future localization without assuming any specific language now (per [Phase 0.1 CON-03](../00-product/assumptions-constraints-risks.md#2-constraints), varied digital literacy).
- **Public errors reveal minimal information** — a public-portal error (e.g., requesting an unpublished result) never leaks the existence or state of pre-publication data.
- **High-integrity failures must not leave ambiguous state** — a failed `official-result.certify` attempt either fully succeeds or fully fails within its transaction (per [laravel-architecture.md, Section 4](laravel-architecture.md#4-transaction-boundaries)); there is no partially-certified state.
- **Partial failures are recorded** — e.g., a bulk import's per-row failures (per [event-and-queue-architecture.md, Section 1](event-and-queue-architecture.md#1-queue-categories), `imports` row) are individually logged, not collapsed into a single opaque "import failed" message.

## 2. Logging Architecture

### Log Categories
Application logs, security logs, audit logs, integration logs, queue logs, Reverb logs, synchronization logs, device logs, performance logs, public traffic logs.

### Rules
- **Audit events are distinct from operational logs** — an audit event (per [high-integrity-domain-rules.md](high-integrity-domain-rules.md)) is an immutable business record with its own retention and access rules; an operational log line is a technical diagnostic artifact with different (typically shorter, more permissive-to-purge) handling.
- **Medical, eligibility, finance, and authentication-secret content must never be logged** — a log line referencing a medical encounter carries its identifier, never its clinical content; a failed-login log line never logs the attempted password.
- **Correlation IDs flow across requests, jobs, and integrations** — a single user action that triggers a queued job that triggers a notification carries one traceable correlation ID through all three.
- **Log levels are standardized** across every module (Debug/Info/Warning/Error/Critical, matching Laravel's standard PSR-3 levels) — no module invents its own ad hoc severity scheme.
- **Logs support environment and service identification** — every log entry is tagged with environment (Section, [environment-and-configuration-model.md](environment-and-configuration-model.md)) and originating module/service.
- **Privileged access to logs is controlled** — logs, particularly security and audit-adjacent logs, are not broadly readable by every developer/administrator; access follows the same least-privilege discipline as any other Restricted-classified resource.
- **Retention is defined later** — specific durations are a later operational-policy decision (see [runtime-open-decisions.md](runtime-open-decisions.md)), not invented here.
- **High-volume events require sampling or aggregation** — e.g., routine successful Access Validation scans at a busy gate may be aggregated/sampled in verbose logs while still being fully recorded in the authoritative Access Scan record itself (the sampling applies to diagnostic logging, never to the business record).
- **Logs must not become a shadow database** — logs are for operational diagnosis and security monitoring, never a substitute source of truth for business data that belongs in the authoritative store.

## 3. Observability Architecture

Conceptual signals to observe (dashboards/alerts, not a specific vendor):

Health checks, readiness checks, queue latency, failed jobs, database health, Redis health, MinIO health, Reverb connection health, API latency, error rate, public-portal performance, synchronization success rate, device connectivity, cache hit rate, storage capacity, backup status, security events.

- **Dashboards** are organized around the same categories as [event-and-queue-architecture.md, Section 1](event-and-queue-architecture.md#1-queue-categories)'s "Operational Ownership" column — each owning role/committee has visibility into the signals relevant to their domain.
- **Alerts** are tiered by severity, with the `critical` queue category, high-integrity command failures, and security events warranting immediate alerting; lower-priority signals (analytics job delays, cache hit-rate drift) warrant periodic review rather than paging anyone.
- **No monitoring vendor is selected in this phase** — this is a later DevOps-phase/implementation decision.

## 4. Health Check Architecture

### Candidate Checks
Application boot, database connectivity, Redis connectivity, queue processing, Horizon supervisor, Reverb availability, MinIO availability, writable storage, scheduled-task heartbeat, critical integration availability, public projection freshness, backup freshness.

### Check Type Separation

| Check Type | Purpose |
|---|---|
| **Liveness** | Is the application process running at all? (Minimal — does not check dependencies) |
| **Readiness** | Is the application ready to serve traffic? (Checks critical dependencies: database, Redis) |
| **Dependency health** | Are individual dependencies (MinIO, Reverb, queue workers) healthy? (Granular, per-dependency) |
| **Business health** | Are business-level indicators normal? (e.g., "is the public projection for the active meet fresher than N minutes," "are queue backlogs within normal range") |
| **Degraded mode** | Is the application operating in a reduced-capability state? (e.g., Reverb unavailable, falling back to polling per [realtime-architecture.md, Section 5](realtime-architecture.md#5-fallback-behavior)) |

These distinctions matter operationally: a Liveness failure means "restart the process"; a Readiness failure means "stop routing traffic here, but don't restart"; a Dependency-health failure may mean "degrade gracefully, keep serving what you can" (per [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md)).

## 5. Open Questions

- Specific log/audit retention durations — deferred pending DepEd records-management policy (mirrors [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)).
- Monitoring/observability vendor or stack selection — later DevOps-phase decision.
- Specific alerting thresholds (queue depth, error rate) — require operational baseline data from a pilot meet, consistent with the approach already taken for KPI targets in [Phase 0.1 success-framework.md](../00-product/success-framework.md#11-baseline-requirements).

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
