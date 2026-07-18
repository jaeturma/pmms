# PMMS Operational Readiness, Handover, and Training

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../04-quality/pilot-operational-and-stakeholder-validation.md, Section 2](../04-quality/pilot-operational-and-stakeholder-validation.md#2-operational-readiness-testing) · [runbooks-playbooks-and-standard-operating-procedures.md](runbooks-playbooks-and-standard-operating-procedures.md)

This document defines operational documentation requirements and the training/handover model connecting engineering delivery to sustained operations. **No training material or handover schedule is created here.**

---

## 1. Operational Documentation

Every operational capability this `docs/05-devops/` package defines is expected to produce, once implemented: the runbooks identified in [runbooks-playbooks-and-standard-operating-procedures.md, Section 2](runbooks-playbooks-and-standard-operating-procedures.md#2-required-future-runbooks) · environment-specific setup/access documentation for every environment in [environment-architecture.md](environment-architecture.md) · a current architecture diagram set (extending the Mermaid diagrams already present in [../03-security/trust-boundaries-and-attack-surface.md](../03-security/trust-boundaries-and-attack-surface.md)) · a current configuration-variable reference (extending `.env.example`'s documentation role) · a current dependency inventory · release notes history (per [incident-problem-change-and-release-management.md, Section 7](incident-problem-change-and-release-management.md#7-release-management)) · an incident/problem log · a decision log (this project's own `.ai/decisions/` ADR pattern, already established and continued by this phase's ADR-0008).

**Documentation is part of operations** — restated absolutely from [devops-and-platform-operations-strategy.md, Section 2](devops-and-platform-operations-strategy.md#2-devops-principles), Principle 21; a capability without corresponding documentation is not considered operationally complete.

## 2. Training and Handover

| Element | Direction |
|---|---|
| Audience | Engineering team, DevOps/Infrastructure staff, Support tiers (per [production-support-access-and-data-repair-operations.md, Section 3](production-support-access-and-data-repair-operations.md#3-support-model)), and committee staff (operational, not technical, training — restated from [../04-quality/pilot-operational-and-stakeholder-validation.md, Section 3](../04-quality/pilot-operational-and-stakeholder-validation.md#3-committee-workflow-validation)) |
| Scope | What each audience needs to operate their portion of the platform — a Tier 1 support agent needs different training than an Infrastructure owner |
| Timing | Ahead of each meaningful operational milestone (pilot entry, general availability), not a one-time event |
| Format | Not fixed in this phase — candidate formats include written runbooks/SOPs (already the primary documentation form throughout this package), hands-on walkthroughs, and recorded sessions |
| Handover evidence | A completed handover produces a record of what was covered and confirmed understood, not merely "a meeting happened" |
| Ongoing knowledge transfer | As the platform evolves, documentation and training material are kept current — restated from Section 1's "documentation is part of operations" |
| Bus-factor mitigation | No single individual is the sole holder of operationally-critical knowledge — the documentation-first discipline throughout this entire six-phase architecture effort (Phases 0.1–0.8) exists specifically to make this possible |

## 3. Relationship to This Documentation Package

`docs/05-devops/` itself, once validated and kept current, *is* a substantial part of the operational-readiness and training foundation — a new team member or support-tier hire can be onboarded against this package rather than solely against institutional memory. This document's role is to name the *process* (training, handover, documentation maintenance) that keeps this package trustworthy over time, not to duplicate the package's own content.

## 4. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably the specific training format/schedule for committee staff ahead of the first pilot, and who owns keeping `docs/05-devops/` current as implementation actually begins.
