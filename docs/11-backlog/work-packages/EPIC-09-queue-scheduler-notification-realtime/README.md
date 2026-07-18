# EPIC-09 — Queue, Scheduler, Notification, and Real-Time Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release D
**Status:** Planned — Not Started

## Purpose

Establish controlled asynchronous and real-time infrastructure (Redis, Horizon, Reverb) while preserving MySQL-backed authoritative state — Redis and Reverb remain transient, per ADR-0004/ADR-0012.

## Architecture Sources

[../../../../08-workflows/](../../../../08-workflows/), ADR-0004, ADR-0011.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-09-01](WP-09-01-redis-connection-and-runtime-verification.md) | Redis Connection and Runtime Verification | Small | P1 |
| [WP-09-02](WP-09-02-queue-and-horizon-baseline.md) | Queue and Horizon Baseline | Medium | P1 |
| [WP-09-03](WP-09-03-queue-naming-and-routing-conventions.md) | Queue Naming and Routing Conventions | Small | P1 |
| [WP-09-04](WP-09-04-job-idempotency-and-safe-payload-conventions.md) | Job Idempotency and Safe Payload Conventions | Medium | P1 |
| [WP-09-05](WP-09-05-failed-job-and-replay-governance-foundation.md) | Failed Job and Replay Governance Foundation | Medium | P2 |
| [WP-09-06](WP-09-06-scheduler-and-heartbeat-foundation.md) | Scheduler and Heartbeat Foundation | Small | P1 |
| [WP-09-07](WP-09-07-notification-intent-and-in-app-notification-foundation.md) | Notification Intent and In-App Notification Foundation | Medium | P1 |
| [WP-09-08](WP-09-08-email-delivery-development-baseline.md) | Email Delivery Development Baseline | Small | P1 |
| [WP-09-09](WP-09-09-reverb-and-broadcast-baseline.md) | Reverb and Broadcast Baseline | Medium | P1 |
| [WP-09-10](WP-09-10-private-channel-authorization-foundation.md) | Private Channel Authorization Foundation | Medium | P1 |
| [WP-09-11](WP-09-11-reconnection-and-state-refresh-foundation.md) | Reconnection and State Refresh Foundation | Medium | P2 |
| [WP-09-12](WP-09-12-queue-scheduler-notification-and-reverb-tests.md) | Queue, Scheduler, Notification, and Reverb Tests | Large | P2 |

## Dependencies

WP-01-01 (Hard), WP-06-01 (Hard, for notifications).

## Completion Outcome

Redis/Queue/Horizon baseline, naming/routing/idempotency conventions, failed-job governance, scheduler, in-app/email notification foundation, Reverb/broadcast baseline with private-channel authorization, and reconnection/state-refresh behavior.

## Deferred Items

SMS and push delivery — no provider selected, explicitly not approved for Phase 1.

## Risks

RISK-EPIC09-01 — Redis being treated as authoritative for anything beyond transient/queue/cache state would violate ADR-0004/ADR-0012.
