# PMMS Backpressure, Load Shedding, Graceful Degradation, and Brownout

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [workload-capacity-and-scale-assumptions.md, Section 3](workload-capacity-and-scale-assumptions.md#3-critical-workloads) · [../08-workflows/queue-routing-priority-retry-and-failure-architecture.md](../08-workflows/queue-routing-priority-retry-and-failure-architecture.md)

---

## 1. Backpressure

Defined for: queue submissions · imports · exports · reports · file uploads · notifications · Reverb broadcasts · AI requests (none active) · public API requests · mobile sync.

**Backpressure and overload behavior must be explicit** — restated absolutely per working rule 53; every workload above has a defined behavior under sustained overload, never an undefined "it just gets slow" outcome. Possible responses: reject with retry guidance · queue · defer · batch · limit · downgrade · disable optional work.

## 2. Load-Shedding Priority Order

Under overload, PMMS preserves, in strict priority order:

1. Authentication and authorization
2. Critical workflow state (per [workload-capacity-and-scale-assumptions.md, Section 3](workload-capacity-and-scale-assumptions.md#3-critical-workloads))
3. Score and result integrity
4. Accreditation and security actions
5. Essential mobile synchronization
6. Public official information

**Optional work that may be reduced under load:** AI (none active) · advanced analytics · large exports · non-urgent notifications · media processing · decorative dashboards.

**Public traffic must not starve critical scoring, accreditation, or administrative workloads** — restated absolutely per working rule 54, directly enforcing this priority order at the infrastructure/workload-isolation level established in [scalability-and-workload-isolation-architecture.md, Section 3](scalability-and-workload-isolation-architecture.md#3-workload-isolation-rules).

## 3. Graceful Degradation Modes

| Mode | Description |
|---|---|
| Public portal cached mode | Serves the last-known-good cached projection when live queries are overloaded, restated from [cache-cdn-static-asset-and-edge-delivery-architecture.md](cache-cdn-static-asset-and-edge-delivery-architecture.md) |
| Administrative read-only mode | A candidate emergency posture preventing new writes while preserving read access, distinct from the meet-day change freeze (per [../05-devops/meet-day-venue-and-offline-operations.md, Section 1](../05-devops/meet-day-venue-and-offline-operations.md#1-meet-day-operations)) |
| Polling instead of Reverb | Restated unchanged from [../08-workflows/realtime-broadcast-and-reverb-message-architecture.md, Section 10](../08-workflows/realtime-broadcast-and-reverb-message-architecture.md#10-fallback-behavior) |
| Delayed notifications | Non-mandatory notifications queue longer under load; mandatory notices (per [../08-workflows/notification-and-recipient-resolution-architecture.md, Section 6](../08-workflows/notification-and-recipient-resolution-architecture.md#6-mandatory-notifications)) are never delayed |
| Queued reports | Report generation defers to off-peak processing under load |
| Reduced image quality | A candidate mitigation for media-delivery bandwidth pressure |
| AI disabled | Restated from Phase 0.10's feature-flag-default-Off discipline, extended to "AI is the first candidate disabled under sustained platform overload" |
| Limited analytics | Analytics queries deprioritized behind operational workloads |
| Venue offline mode | Restated unchanged from [../05-devops/meet-day-venue-and-offline-operations.md, Section 3](../05-devops/meet-day-venue-and-offline-operations.md#3-offline-venue-operations) |
| Manual fallback | Full detail: [business-continuity-meet-day-continuity-and-manual-fallback.md](business-continuity-meet-day-continuity-and-manual-fallback.md) |

**Degraded state must be visible** — restated absolutely as this section's governing rule, directly extending Phase 0.9's status-vocabulary discipline; a user experiencing degraded service sees an explicit indicator, never a silently slower or partially-functioning interface presented as normal.

## 4. Brownout Architecture

A controlled, temporary disablement of optional features during extreme load. Brownout controls must be: feature-flagged · observable · approved (never triggered unilaterally without an incident-response decision) · tenant-aware (a brownout may target specific low-priority tenant workloads before degrading platform-wide) · reversible · communicated (to affected users, per [../08-workflows/notification-and-recipient-resolution-architecture.md](../08-workflows/notification-and-recipient-resolution-architecture.md)) · auditable where material.

## 5. Relationship to Automation and Incident Response

Brownout and load-shedding activation is itself governed by the same feature-flag-disablement discipline established in [../08-workflows/responsible-automation-and-authority-boundaries.md, Section 7](../08-workflows/responsible-automation-and-authority-boundaries.md#7-automation-feature-flags-disablement-and-rollback) and [../08-workflows/workflow-incident-change-and-release-governance.md](../08-workflows/workflow-incident-change-and-release-governance.md) — an overload-response action is an incident-response action, never an ungoverned automatic behavior with no audit trail.

## 6. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-29 (specific overload thresholds triggering each degradation mode, deferred pending pilot evidence).
