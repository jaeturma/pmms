# PMMS Real-Time Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [event-and-queue-architecture.md](event-and-queue-architecture.md) · [runtime-security-architecture.md](runtime-security-architecture.md) · [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary)

This document defines Laravel Reverb's role in PMMS. **No Reverb configuration, broadcast event class, or channel definition is created here.**

---

## 1. Use Cases

| Use Case | Owning Context | Channel Type |
|---|---|---|
| Live scoreboard updates | Scoring (BC-15) — provisional/in-progress display only, never a substitute for the Official Result | Venue channel |
| Live result-board updates | Official Results (BC-16) — **published** results only | Meet channel, Public channel |
| Tournament progress | Tournament Management (BC-12) | Sport channel |
| Match-status updates | Tournament Management (BC-12) | Venue channel, Tournament channel |
| Medal tally updates | Medal Tally (BC-18) — **published** tally only | Meet channel, Public channel |
| Venue schedule changes | Venue and Schedule (BC-14) | Venue channel, Public channel |
| Operational readiness notifications | Meet Administration (BC-04), Committee Operations (BC-05) | Administrative channel |
| Committee alerts | Committee Operations (BC-05) and specialized committee contexts | Committee channel |
| Access-control alerts | Security Operations (BC-25), fed by Access Validation (BC-20) | Administrative channel (Security-scoped) |
| ICT monitoring | ICT Service Operations (BC-27) | Administrative channel (ICT-scoped) |
| Public announcement updates | Media and Communications (BC-28), via Public Information (BC-29) | Public channel |

## 2. Channel Types

| Channel Type | Authorization Basis | Example |
|---|---|---|
| **Public channels** | None (unauthenticated) — publishable projections only | Public result board, public schedule board |
| **Private user channels** | User Account identity | "My notifications" |
| **Meet channels** | Meet-scoped assignment or Meet Observer-level access | Meet-wide operational status |
| **Committee channels** | Committee assignment | Committee task alerts |
| **Delegation channels** | Delegation assignment | Delegation-scoped schedule/entry updates |
| **Sport channels** | Sport assignment (or public, for published tournament progress) | Tournament progress within one sport |
| **Venue channels** | Venue assignment (or public, for published schedule) | Venue-level match/schedule status |
| **Tournament channels** | Sport/Tournament assignment | Bracket progression for one tournament |
| **Administrative channels** | Committee/role-specific administrative assignment (ICT, Security) | Operational monitoring alerts |

## 3. Rules

- **Broadcasts must not contain protected data.** A venue-channel score update carries the minimum needed for a live display (e.g., current score value, competitor identifiers already public in the schedule) — never eligibility evidence, medical data, or full participant biographical records, per working rule 28.
- **Channel authorization must use server-side policy checks** — a private/presence channel's authorization callback evaluates the same [authorization-decision-model.md](authorization-decision-model.md) inputs (scope, assignment validity) as any other protected action, never a client-supplied claim.
- **Public channels use publishable projections only** — the payload broadcast to a public channel is drawn from BC-29's already-approved data, never a shortcut that broadcasts pre-publication working data because "it's just a live update."
- **Real-time messages are transient.** A Reverb broadcast is not a durable record — if a client misses a message (disconnected, reconnecting), there is no replay guarantee.
- **Clients must be able to recover current state through normal queries.** Every real-time-driven UI has a corresponding Inertia/API query that returns the same current state, so a client that missed broadcasts (or just loaded the page) is never stuck with stale data.
- **Broadcast events do not replace durable domain events.** A `ResultCertified` domain event still flows through the standard event/queue architecture (per [laravel-architecture.md, Section 5](laravel-architecture.md#5-domain-event-architecture)) for audit, projection, and notification purposes — the Reverb broadcast is an *additional*, best-effort, low-latency notification layered on top, not a substitute mechanism.
- **Reconnection behavior must be defined**: on reconnect, a client re-subscribes to its authorized channels and performs a normal state-recovery query (Section above) rather than assuming any missed messages will be backfilled by the broadcast channel itself.
- **Sequence or version metadata should be considered** for channels where message ordering matters (e.g., a tournament-progress channel), so a client can detect it has missed a message and needs to refetch, rather than silently rendering an out-of-order update.
- **High-volume public events may need throttling or aggregation** — e.g., a live scoreboard with many rapid score updates may batch/coalesce broadcasts rather than pushing every micro-update individually, to avoid overwhelming both the Reverb server and public clients.

## 4. Provisional vs. Published Distinction on Broadcast Channels

Consistent with [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary), live scoreboard/match-status broadcasts (Scoring, BC-15) are explicitly **provisional/in-progress** displays, distinguishable in the UI from the **Official, Published** result broadcasts (Official Results, BC-16). A public consumer must never be able to confuse an in-progress live score with a certified, published Official Result — the two use visually and semantically distinct presentation, sourced from different owning contexts.

## 5. Fallback Behavior

If Reverb is unavailable, pages must degrade gracefully to polling or simple page-refresh-driven updates rather than becoming non-functional — real-time delivery is an enhancement layered on top of the normal query-driven page load, never the only path to seeing current data (see [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md), "Reverb fallback to polling").

## 6. Open Questions

- Specific throttling/aggregation strategy for high-volume public scoreboard channels (Section 3) — implementation-phase tuning, not architecturally blocking.
- Whether presence channels (showing "who's currently viewing") are needed for any collaborative workflow (e.g., simultaneous eligibility review) — no evidenced need currently identified.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md) where they affect architecture, not merely tuning.
