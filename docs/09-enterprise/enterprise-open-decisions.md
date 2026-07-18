# PMMS Enterprise Performance-Scalability-Multitenancy-DR Open Decisions

**Status:** Draft Complete — Pending Product, Architecture, Security, Data, Platform, Operations, and Engineering Validation
**Related:** [enterprise-readiness-vision-principles-and-maturity-model.md](enterprise-readiness-vision-principles-and-maturity-model.md) · [../08-workflows/workflow-open-decisions.md](../08-workflows/workflow-open-decisions.md)

This document tracks every unresolved Phase 0.12 decision using Decision ID prefix `ED-` (Enterprise Decision), distinct from every prior phase's prefix (`OD-`, `DD-`, `AD-`, `RD-`, `PD-`, `SD-`, `QD-`, `DV-`, `WD-`, and Phase 0.10's `AX-`). Each entry follows the established format: Question / Why It Matters / Recommended Direction / Decision Owner / Target Phase / Status. Entries are grouped by topic for readability; numbering is sequential across groups.

---

## Capacity, Performance, and Scalability (ED-01 – ED-04, ED-17 – ED-32)

- **ED-01** — First capacity-evidence-gathering exercise timing. *Why:* every numeric capacity/performance decision in this package depends on it. *Direction:* begin at first controlled pilot (Stage 2). *Owner:* Quality owner + Infrastructure owner. *Status:* Open.
- **ED-02** — Multi-meet concurrency target. *Why:* contingent on [OD-03](../00-product/open-decisions.md#od-03--single-meet-versus-multi-meet-launch). *Status:* Open — mirrors OD-03.
- **ED-03** — First SLO-setting exercise timing, mirroring [DV-15](../05-devops/devops-open-decisions.md#dv-15--first-slo-setting-exercise-timing). *Status:* Open — mirrors DV-15.
- **ED-04** — Specific scaling trigger thresholds per workload. *Direction:* deferred to pilot evidence. *Status:* Open.
- **ED-17** — Horizontal-scaling trigger threshold. *Status:* Open, pending pilot evidence.
- **ED-18** — Read-replica adoption trigger, continuing [../01-architecture/reporting-search-and-read-model-runtime.md, Section 6](../01-architecture/reporting-search-and-read-model-runtime.md#6-isolation-from-transactional-write-load)'s deferred isolation-mechanism decision. *Status:* Open.
- **ED-19** — Redis clustering/replication adoption trigger. *Status:* Open.
- **ED-20** — Specific rate-limit numeric values per endpoint category. *Status:* Open.
- **ED-21** — Bucket-per-tenant adoption trigger. *Status:* Open.
- **ED-22** — Horizon autoscaling adoption trigger. *Status:* Open.
- **ED-23** — Per-category worker-fleet baseline sizing. *Status:* Open, pending pilot evidence.
- **ED-24** — Multi-instance Reverb adoption trigger and shared pub-sub backend selection. *Status:* Open.
- **ED-25** — Mobile-sync batch-size and pagination defaults. *Status:* Open, pending pilot evidence.
- **ED-26** — CDN provider selection. *Why:* contingent on [DV-01](../05-devops/devops-open-decisions.md#dv-01--deployment-topology-and-cloud-versus-on-premise). *Status:* Open — mirrors DV-01.
- **ED-27** — Edge-cache TTL policy for public projections. *Status:* Open.
- **ED-28** — External search-engine adoption trigger, restating [../01-architecture/reporting-search-and-read-model-runtime.md, Section 3](../01-architecture/reporting-search-and-read-model-runtime.md#3-search-architecture)'s staged approach. *Status:* Open.
- **ED-29** — Specific overload thresholds triggering each degradation mode. *Status:* Open, pending pilot evidence.
- **ED-30** — Meet peak allocation mechanism, if ever adopted. *Status:* Open.
- **ED-31** — Queue fair-scheduling algorithm selection. *Status:* Open.
- **ED-32** — Circuit-breaker library/pattern adoption timing. *Status:* Open.

## Multi-Tenancy and Data Isolation (ED-05 – ED-16, ED-21)

- **ED-05** — Whether one tenant may span multiple organizations' hierarchies versus one tenant per top-level organization. *Owner:* Product owner. *Status:* Open, requires product validation.
- **ED-06** — Cross-tenant meet collaboration model. *Status:* Open, requires product validation.
- **ED-07** — Tenant-selection mechanism for accounts holding multiple tenant memberships. *Status:* Open.
- **ED-08** — Whether database-per-tenant is ever adopted for regulated/large tenants. *Status:* Open, Stage 5 candidate.
- **ED-09** — Per-tenant encryption-key readiness timing. *Status:* Open.
- **ED-10** — Whether a dedicated cross-tenant auditor role is formally cataloged in a future Phase 0.3 revision. *Status:* Open.
- **ED-11** — Whether tenant-specific AI provider/model selection is ever supported. *Status:* Open, contingent on Phase 0.10 AX-02 (AI provider evaluation).
- **ED-12** — Specific tenant-quota numeric values per tier. *Status:* Open, pending evidence.
- **ED-13** — Custom-domain/white-label adoption timing. *Status:* Open, Stage 5 candidate.
- **ED-14** — Tenant-suspension grace-period duration. *Status:* Open.
- **ED-15** — Licensing-model decision. *Why:* tied directly to [OD-22](../00-product/open-decisions.md#od-22--licensing-model). *Status:* Open — mirrors OD-22.
- **ED-16** — Metering-to-billing integration timing. *Status:* Open, contingent on ED-15.

## Disaster Recovery, Availability, and Continuity (ED-33 – ED-37)

- **ED-33** — RPO/RTO numeric targets. *Why:* **Blocking** — continues the longest-carried open decision in this entire architecture effort: [RD-18](../01-architecture/runtime-open-decisions.md) → [PD-23](../02-data/data-open-decisions.md#pd-23--rporto-numeric-targets) → [SD-24](../03-security/security-open-decisions.md) → [DV-17](../05-devops/devops-open-decisions.md#dv-17--numeric-rporto-targets) → **ED-33**. *Owner:* Infrastructure owner + Security owner + DepEd Leadership. *Status:* Open — blocking, mirrors DV-17.
- **ED-34** — DR topology selection (active-passive/warm/cold standby/multi-region). *Why:* contingent on [DV-01](../05-devops/devops-open-decisions.md#dv-01--deployment-topology-and-cloud-versus-on-premise). *Status:* Open — mirrors DV-01, and continues [DV-16](../05-devops/devops-open-decisions.md#dv-16--disaster-recovery-environment-provisioning-timing).
- **ED-35** — Failback process design. *Status:* Open, not yet designed at any prior phase.
- **ED-36** — Meet-day incident-command structure. *Status:* Open.
- **ED-37** — Local venue server adoption trigger, mirroring the existing Hybrid Venue Topology open item. *Status:* Open.

## Enterprise Identity, Compliance, and Commercial Readiness (ED-38 – ED-47)

- **ED-38** — SSO protocol selection. *Status:* Open, Stage 5 candidate.
- **ED-39** — SCIM adoption evaluation timing. *Status:* Open.
- **ED-40** — Data-residency commitment timing. *Why:* contingent on DV-01 and multi-region readiness. *Status:* Open.
- **ED-41** — Records-management capability scope. *Status:* Open.
- **ED-42** — Integration marketplace adoption timing. *Why:* contingent on [OD-25](../00-product/open-decisions.md#od-25--integration-requirements). *Status:* Open — mirrors OD-25.
- **ED-43** — Webhook-product adoption trigger. *Status:* Open.
- **ED-44** — Staged/canary tenant-cohort upgrade adoption. *Status:* Open.
- **ED-45** — Architecture fitness-function tooling selection. *Status:* Open.
- **ED-46** — Multi-tenant load-testing tooling selection. *Status:* Open.
- **ED-47** — First enterprise-readiness-gate exercise timing. *Status:* Open.

---

## Summary of Blocking / High-Priority Enterprise Decisions

| Decision | Why It Blocks |
|---|---|
| **ED-33** | RPO/RTO numeric targets — the single longest-carried open decision across this entire architecture effort (RD-18 → PD-23 → SD-24 → DV-17 → ED-33), blocking any concrete backup-frequency or DR-topology commitment |
| **ED-34** | DR topology selection is blocked directly on the still-unresolved Phase 0.8 DV-01 (deployment topology/cloud provider), itself the single largest unresolved DevOps question |
| **ED-05 / ED-06** | The tenant-hierarchy structural questions block finalizing any concrete multi-tenancy data model beyond the already-recommended nullable `organization_id` (PD-01) |
| **ED-15** | The licensing-model decision (mirroring OD-22) blocks every commercial-readiness decision in this package (metering-to-billing integration, subscription plans, contractual service levels) |
