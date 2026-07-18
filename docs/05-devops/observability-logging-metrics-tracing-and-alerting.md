# PMMS Observability, Logging, Metrics, Tracing, and Alerting

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) · [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md) · [../03-security/security-metrics-monitoring-and-reporting.md](../03-security/security-metrics-monitoring-and-reporting.md)

This document defines PMMS's observability architecture: health/readiness/liveness checks, logging, metrics, tracing readiness, alerting, dashboards, and synthetic monitoring. **No monitoring platform, dashboard, or alerting configuration is created here.**

---

## 1. Health Checks

Distinguished, never conflated:

| Check Type | Question | Answer Determines |
|---|---|---|
| Liveness | Can the process run at all? | Whether the process should be restarted |
| Readiness | Can it serve traffic safely right now? | Whether the load balancer/orchestrator should route traffic to it |
| Dependency health | Are MySQL, Redis, MinIO, queues, and any approved external service available? | Whether readiness should reflect a downstream dependency's state |
| Business health | Are public projections fresh, queues moving, schedules running, and critical operations succeeding? | Whether the platform is functionally, not just technically, healthy |
| Degraded mode | Can PMMS continue with limited capability (e.g., Redis down but MySQL/application otherwise healthy)? | Whether a partial outage should present as fully down or as degraded |

A liveness check failing triggers a restart; a readiness check failing removes the instance from traffic without restarting it; a dependency-health or business-health signal informs alerting (Section 6) and dashboards (Section 7) without necessarily affecting traffic routing directly.

## 2. Observability Architecture

Extends [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) (Phase 0.4) with the DevOps-operational pillars: **logs, metrics, traces/correlation readiness, events, health checks, synthetic checks, and user-impact indicators.** Observability exists to support: detection (something is wrong) · diagnosis (why) · recovery (fixing it) · capacity planning · security (per [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md)) · audit · release verification (per [ci-cd-and-release-pipeline-architecture.md](ci-cd-and-release-pipeline-architecture.md)) · and meet-day operations (per [meet-day-venue-and-offline-operations.md](meet-day-venue-and-offline-operations.md)).

## 3. Logging Architecture

Every structured log entry carries: environment · application version · service · correlation ID · request ID · user or service identity reference where safe · meet and organization context where safe · severity · event category · error code · duration · dependency status.

### Rules

No secrets · no protected evidence · no full medical, eligibility, finance, or guardian data — restated absolutely from [../03-security/audit-and-security-event-architecture.md, Section 9](../03-security/audit-and-security-event-architecture.md#9-logging-and-monitoring-boundaries). Access restricted (log access is itself a privileged-access category, per [../03-security/authorization-and-privileged-access-assurance.md](../03-security/authorization-and-privileged-access-assurance.md)). Retention governed (per [../02-data/retention-archival-and-disposal.md, Section 1](../02-data/retention-archival-and-disposal.md#1-retention-categories), "logs" category, a placeholder). **Logs are separated from audit records** — restated absolutely; an application log is never a substitute for a formal audit event, and vice versa. Clock synchronization required across every log-producing instance, since correlating events across services depends on consistent timestamps.

## 4. Metrics Architecture

Candidate categories: request rate · error rate · latency · saturation · queue depth · queue latency · failed jobs · Reverb connections · broadcast rate · Redis memory · cache hit rate · MySQL connections · query latency · lock waits · MinIO capacity · object errors · sync success · device heartbeat · QR validation rate · result publication delay · public projection freshness · backup status · certificate expiry.

**No monitoring vendor is selected** — restated from [../01-architecture/runtime-open-decisions.md, RD-14](../01-architecture/runtime-open-decisions.md#rd-14--monitoringobservability-stack-selection); this list names what must eventually be measured, not the tool measuring it.

## 5. Tracing and Correlation Readiness

Correlation IDs propagate across HTTP requests, API calls, queue jobs, integrations, and AI calls, per [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) — extended here with: parent-child operation references (so a queued job traces back to the request that dispatched it) · queue propagation (a correlation ID survives a job being queued and later executed) · external-call timing (how long an outbound call to any approved service took) · sensitive-data minimization (a trace/correlation record never carries Restricted/Highly Restricted payload content, only identifiers) · sampling (not every request needs full trace detail at high volume) · trace retention (governed, not indefinite) · future distributed-tracing evaluation (a candidate tool, e.g., an OpenTelemetry-compatible collector, once the deployment topology has more than one service worth tracing across). **No tracing vendor is selected in this phase.**

## 6. Alerting Architecture

Severity levels · ownership (every alert has a named owning role) · routing (the right alert reaches the right on-call role) · deduplication (repeated identical alerts don't flood the channel) · suppression (during a known maintenance window) · maintenance windows (per [deployment-strategies-rollbacks-and-maintenance.md, Section 3](deployment-strategies-rollbacks-and-maintenance.md#3-maintenance-mode-strategy)) · escalation (per [incident-problem-change-and-release-management.md](incident-problem-change-and-release-management.md)) · acknowledgment · resolution · runbook link (every alert links to its corresponding runbook, per [runbooks-playbooks-and-standard-operating-procedures.md](runbooks-playbooks-and-standard-operating-procedures.md)) · post-incident review · alert-quality review (a periodic check that alerts remain actionable, not noise). **Avoid alerts without actionable response** — restated absolutely; an alert nobody can or should act on is quality debt, not observability.

## 7. Dashboards

Candidate dashboards: executive operations · meet-day command center · application health · public portal · queue and Horizon · Reverb · MySQL · Redis · MinIO · mobile synchronization · device fleet · security · backup and recovery · capacity · release health. No dashboard tool is selected — these are candidate views a future observability platform must support.

## 8. Synthetic Monitoring

Candidate checks: public portal availability · login page · authentication readiness · API health · published schedule · published result · QR-validation test credential in a controlled environment · file upload in non-production · queue processing · Reverb connectivity · backup freshness · certificate validity.

**Synthetic checks must not modify official production data unless explicitly designed and isolated** — restated absolutely; a synthetic QR-validation check, for example, uses a dedicated, clearly-labeled test credential that never represents a real participant, and never writes a real access-scan record indistinguishable from genuine venue activity.

## 9. Real-User Monitoring Readiness

A candidate future capability (aggregate, privacy-respecting measurement of actual user-experienced performance on the public portal and administrative application) — evaluated once the platform has real traffic to measure, never built speculatively ahead of that need. Any real-user monitoring respects the same data-minimization and classification rules as every other PMMS telemetry, per [../03-security/privacy-by-design-architecture.md](../03-security/privacy-by-design-architecture.md).

## 10. Component-Specific Monitoring

| Component | Monitoring Focus |
|---|---|
| Public portal | Availability, latency, error rate, traffic volume — isolated capacity from administrative monitoring |
| API | Per-endpoint latency/error rate, rate-limit trigger frequency |
| Queue | Depth, latency, failed-job count, per queue category |
| Horizon | Worker health, supervisor status |
| Reverb | Connection count, broadcast rate, reconnection frequency |
| Redis | Memory usage, eviction rate, command latency |
| MySQL | Connection count, query latency, lock waits, replication lag if a replica exists |
| MinIO | Capacity utilization, object error rate, reconciliation-detected discrepancies |
| Mobile synchronization | Sync success rate, sync latency, conflict rate |
| Device | Heartbeat/last-seen freshness, per [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security) |
| Backup | Completion status, duration, verification outcome |
| Certificate | Expiry countdown, renewal success |
| Domain | DNS resolution health, registration expiry |

## 11. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably monitoring/observability platform selection (mirrors [../01-architecture/runtime-open-decisions.md, RD-14](../01-architecture/runtime-open-decisions.md#rd-14--monitoringobservability-stack-selection)) and alerting-tool/on-call-tool selection.
