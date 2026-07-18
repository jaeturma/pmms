# PMMS Real-Time Broadcast and Reverb Message Architecture

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md) · [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 3](../05-devops/application-worker-scheduler-and-realtime-deployment.md#3-reverb-deployment)

**No Reverb channel, broadcast event class, or authorization callback is created here.** This document extends the existing Phase 0.4 [realtime-architecture.md](../01-architecture/realtime-architecture.md) with Phase 0.11's message-reliability and workflow-boundary detail.

---

## 1. Reverb Use Cases (Restated)

Live scoreboard updates (BC-15, provisional/in-progress only) · live result-board updates (BC-16, **published only**) · tournament progress (BC-12) · match-status updates (BC-12) · medal-tally updates (BC-18, **published only**) · venue-schedule changes (BC-14) · operational-readiness notifications (BC-04, BC-05) · committee alerts (BC-05) · access-control alerts (BC-25, fed by BC-20) · ICT monitoring (BC-27) · public-announcement updates (BC-28, via BC-29). Full source table: [../01-architecture/realtime-architecture.md, Section 1](../01-architecture/realtime-architecture.md#1-use-cases).

## 2. Channel Taxonomy (Restated and Extended)

| Channel Type | Authorization Basis |
|---|---|
| Public channels | None (unauthenticated) — publishable projections only |
| Private user channels | User Account identity |
| Meet channels | Meet-scoped assignment or Meet Observer-level access |
| Committee channels | Committee assignment |
| Delegation channels | Delegation assignment |
| Sport channels | Sport assignment (or public, for published tournament progress) |
| Venue channels | Venue assignment (or public, for published schedule) |
| Tournament channels | Sport/Tournament assignment |
| Administrative channels | Committee/role-specific administrative assignment (ICT, Security) |
| Device-operations channels (Phase 0.11 addition) | Device Identity plus its bound operator's assignment, per [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md) |

**All non-public channels require server-side authorization** — restated absolutely; channel authorization uses the same `authorization-decision-model.md` inputs (scope, assignment validity) as any other protected action, never a client-supplied claim.

## 3. Presence-Channel Readiness

No presence channel (showing "who else is currently viewing/connected") is implemented or evidenced as needed in this phase — restated from [../01-architecture/realtime-architecture.md, Section 6](../01-architecture/realtime-architecture.md#6-open-questions). If a future use case (e.g., collaborative bracket editing) demonstrates a need, presence-channel authorization would follow the same server-side model as any other private channel.

## 4. Public Channel Restrictions

Public channels draw only from BC-29's already-approved projections — restated absolutely, directly extending [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md)'s public-projection model into the real-time layer. A public channel never broadcasts a provisional, held, restricted, or superseded record, restated from working rule 36 (Phase 0.9) applied to real-time delivery specifically.

## 5. Real-Time Payload Rules

Payloads should include: event type · version · public-or-private scope · safe record identifier · current display state · freshness · sequence or version · occurred time.

**Do not broadcast protected documents, medical details, eligibility evidence, authentication data, or financial attachments** — restated absolutely, extending [event-taxonomy-ownership-and-contracts.md, Section 3](event-taxonomy-ownership-and-contracts.md#3-event-payload-rules)'s minimal-payload rule to the real-time layer specifically, where the transient, unauthenticated-reachable nature of some channels raises the stakes of an over-broad payload.

## 6. Provisional Versus Published Distinction

Restated absolutely from [../01-architecture/realtime-architecture.md, Section 4](../01-architecture/realtime-architecture.md#4-provisional-vs-published-distinction-on-broadcast-channels): a live scoreboard (BC-15) broadcast must be visually/semantically distinct from an Official/Published (BC-16) broadcast — "a public consumer must never be able to confuse an in-progress live score with a certified, published Official Result."

## 7. Event Fan-Out and Throttling

A single domain event (e.g., `MatchScheduled`) may fan out to multiple channels (Venue, Sport, Tournament, Public) — fan-out is explicit and bounded, never an unbounded broadcast storm. High-volume public scoreboard updates require throttling/aggregation tuning, restated as an open, non-blocking tuning concern from [../01-architecture/realtime-architecture.md, Section 6](../01-architecture/realtime-architecture.md#6-open-questions).

## 8. Reconnection and Missed-Message Recovery

Clients must: detect disconnect · show connection state · reconnect · reauthorize · fetch current authoritative state via an ordinary query · reconcile versions · avoid applying stale updates · tolerate duplicate events.

**Real-time is explicitly non-durable — "no replay guarantee."** Restated absolutely from [../01-architecture/realtime-architecture.md, Section 3](../01-architecture/realtime-architecture.md#3-rules); clients recover authoritative state through ordinary Inertia/API queries after a reconnect, never by assuming Reverb itself replays missed messages.

## 9. State Resynchronization

On reconnect, a client re-fetches current state rather than attempting to "catch up" purely from broadcast messages — this mirrors [../01-architecture/offline-sync-runtime-architecture.md, Section 5](../01-architecture/offline-sync-runtime-architecture.md#5-sync-priority-ordering)'s reconnection-priority ordering conceptually (revocations and authorization state first), applied to the always-online real-time context.

## 10. Fallback Behavior

Restated from [../01-architecture/realtime-architecture.md, Section 5](../01-architecture/realtime-architecture.md#5-fallback-behavior): if Reverb is unavailable, pages must degrade gracefully to polling or simple page-refresh-driven updates. **Broadcast events do not replace durable domain events** — restated absolutely; `ResultCertified` still flows through the standard event/queue architecture regardless of Reverb's availability, per [../01-architecture/realtime-architecture.md, Section 3](../01-architecture/realtime-architecture.md#3-rules).

## 11. Reverb Deployment Readiness (Cross-Reference)

Restated from [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 3](../05-devops/application-worker-scheduler-and-realtime-deployment.md#3-reverb-deployment): a dedicated persistent-connection process; a restart drops active connections (acceptable, transient); restarts are scheduled relative to meet-day usage per [../05-devops/meet-day-venue-and-offline-operations.md](../05-devops/meet-day-venue-and-offline-operations.md); multi-instance Reverb scaling remains a candidate future capability, not committed.

## 12. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-17 (public-scoreboard throttling/aggregation strategy) and WD-18 (presence-channel need, carried unchanged from Phase 0.4).
