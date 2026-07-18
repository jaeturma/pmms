# PMMS Performance Budget and Service-Level Architecture

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [../05-devops/service-level-capacity-and-performance-management.md, Section 1](../05-devops/service-level-capacity-and-performance-management.md#1-service-level-management)

---

## 1. Performance Budget Architecture (Conceptual)

Performance budgets are defined conceptually for: page response · API response · public portal load · QR validation · score submission · search · dashboard load · report generation · export generation · file upload · mobile sync · Reverb update delivery · queue delay · result publication · medal-tally update.

**No numeric budget is set** — restated per this phase's own working instruction. Each budget instead defines:

| Field | Meaning |
|---|---|
| Owner | The role accountable for the workload meeting its eventual budget |
| Measurement method | How the budget will be measured once evidence exists (e.g., server-timing, RUM, synthetic probe) |
| Environment | Which environment (Pilot, Staging, Production) the measurement applies to |
| Load profile | The concurrency/volume condition the measurement assumes |
| Validation process | How the budget is confirmed met before being treated as a commitment |

## 2. Service-Level Architecture

Restated and extended from [../05-devops/service-level-capacity-and-performance-management.md, Section 1](../05-devops/service-level-capacity-and-performance-management.md#1-service-level-management) — this document does not redefine that section's SLI list, it applies the same indicators to the enterprise/multi-tenant context specifically.

### Service-Level Indicators (Restated)

Availability · request latency · error rate · QR-validation success · score-submission success · result-publication delay · queue latency · sync success · backup success · restore success · **public-data freshness (new, Phase 0.12 addition — the staleness of a public projection relative to its source certification)**.

### Service-Level Objectives

**Placeholders pending pilot and stakeholder validation** — restated absolutely, no percentage or duration target is set for any indicator, unchanged from Phase 0.8's identical discipline.

### Operational-Level Objectives

Internal commitments supporting service objectives (e.g., an internal target for queue-worker deployment time) — distinct from externally-facing SLOs, evaluated only once SLOs themselves exist.

### Error Budgets

**Evaluated only after reliable metrics and agreed service objectives exist** — restated as this section's governing rule; no error budget is defined prematurely against unmeasured targets.

## 3. Per-Capability Availability Differentiation

Availability is defined per capability, never as one platform-wide assumption — restated from the required structure's own governing instruction:

| Capability | Candidate Availability Posture |
|---|---|
| Critical administrative operations (score entry, credential validation, security actions) | Highest priority for availability investment |
| Public portal | High priority, but tolerant of degraded/cached-mode delivery under load |
| Live scoreboards | Best-effort, transient — restated from [../08-workflows/realtime-broadcast-and-reverb-message-architecture.md](../08-workflows/realtime-broadcast-and-reverb-message-architecture.md) |
| Reports | Lower priority — asynchronous generation is acceptable |
| AI (candidate, none active) | Lowest priority — advisory only, per Phase 0.10 |
| Historical archive | Lowest priority — read-only, infrequently accessed |

All specific percentage/duration targets remain placeholders.

## 4. Commercial Service-Level Readiness (Not a Commitment)

A future contractual service level (per [tenant-metering-licensing-subscription-and-billing-readiness.md](tenant-metering-licensing-subscription-and-billing-readiness.md)) would be derived from measured internal SLOs once they exist — restated absolutely, PMMS does not offer or imply any contractual service level in this phase.

## 5. Open Questions

See [enterprise-open-decisions.md](enterprise-open-decisions.md) — notably ED-03 (first SLO-setting exercise timing, mirroring [DV-15](../05-devops/devops-open-decisions.md#dv-15--first-slo-setting-exercise-timing)).
