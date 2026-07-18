# PMMS Reverb, WebSocket, and Real-Time Scaling

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../08-workflows/realtime-broadcast-and-reverb-message-architecture.md](../08-workflows/realtime-broadcast-and-reverb-message-architecture.md) · [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 3](../05-devops/application-worker-scheduler-and-realtime-deployment.md#3-reverb-deployment)

**No Reverb configuration or scaling infrastructure is created here.**

---

## 1. Connection Capacity

Public and private connection counts (live scoreboard viewers, administrative real-time clients, per [../01-architecture/realtime-architecture.md, Section 1](../01-architecture/realtime-architecture.md#1-use-cases)) are a named capacity dimension — no specific connection ceiling is invented; capacity evidence comes from pilot measurement, per [workload-capacity-and-scale-assumptions.md, Section 2](workload-capacity-and-scale-assumptions.md#2-scale-evidence-requirements).

## 2. Horizontal Scaling Readiness

Reverb's persistent-connection nature makes it a distinct scaling unit from stateless web/worker nodes — restated from [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 3](../05-devops/application-worker-scheduler-and-realtime-deployment.md#3-reverb-deployment), which already states "multi-instance Reverb scaling is a candidate future capability, not committed." This document adds: shared runtime coordination (multiple Reverb instances require a shared pub-sub backend, likely Redis, to coordinate broadcast delivery across instances) and sticky-session evaluation (whether a load balancer must pin a client to the same Reverb instance for the connection's lifetime, or whether the shared backend makes this unnecessary).

## 3. Broadcast Throughput and Fan-Out

Restated from [../08-workflows/realtime-broadcast-and-reverb-message-architecture.md, Section 7](../08-workflows/realtime-broadcast-and-reverb-message-architecture.md#7-event-fan-out-and-throttling): fan-out is explicit and bounded. High-volume public scoreboard updates require throttling/aggregation tuning at scale — restated unchanged as an open tuning concern from Phase 0.4/0.11.

## 4. Backpressure and Reconnect Storms

A Reverb outage or restart causes simultaneous client reconnection attempts ("reconnect storm") — mitigated by client-side reconnect backoff/jitter (a UX-layer concern, per [../06-design/status-feedback-error-offline-and-sync-patterns.md](../06-design/status-feedback-error-offline-and-sync-patterns.md)) and server-side connection-rate awareness. No specific reconnect-backoff algorithm is selected in this phase.

## 5. Tenant Channel Isolation

Every non-public Reverb channel's tenant-aware authorization (restated from [tenant-aware-runtime-workflow-event-and-ai-boundaries.md, Section 5](tenant-aware-runtime-workflow-event-and-ai-boundaries.md#5-tenant-aware-real-time-channels)) applies identically regardless of connection-capacity scale — scaling Reverb horizontally never weakens per-channel authorization, since authorization is evaluated per-connection against trusted server-side data, not against which Reverb instance happens to serve the connection.

## 6. Graceful Degradation to Polling

Restated absolutely, unchanged from [../08-workflows/realtime-broadcast-and-reverb-message-architecture.md, Section 10](../08-workflows/realtime-broadcast-and-reverb-message-architecture.md#10-fallback-behavior): if Reverb is unavailable or overloaded, pages degrade gracefully to polling or page-refresh-driven updates — restated as this document's primary scaling-failure mitigation, cheaper and more robust than attempting to scale Reverb capacity indefinitely under sudden load.

## 7. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-24 (multi-instance Reverb adoption trigger and shared pub-sub backend selection).
