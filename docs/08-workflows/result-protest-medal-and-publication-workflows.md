# PMMS Result, Protest, Medal, and Publication Workflows

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-12, WF-13, WF-14, WF-15)

This document adds the event, notification, state-machine, and automation layer to the existing Phase 0.2 workflow definitions — it does not redefine WF-12 through WF-15's steps, actors, or preconditions.

---

## 1. Official Result Workflow (WF-12/WF-13, BC-16, High-Integrity)

`Generated → Pending Validation → Validated → Pending Certification → Certified → [Held ⇄ Certified] → Published → [Corrected | Superseded] → Archived` — restated from the illustrative state machine in [business-process-and-state-machine-architecture.md, Section 2](business-process-and-state-machine-architecture.md#2-state-machine-architecture).

**Document separation between validation, certification, and publication** — restated absolutely as this section's governing rule, per [human-tasks-approvals-reviews-and-certifications.md, Section 2](human-tasks-approvals-reviews-and-certifications.md#2-approval-review-and-certification-are-distinct). `ResultCertified` and `ResultPublished` are separate, notification-worthy domain events, never a single combined action.

- `CertifyResult` and `PublishResult` are separate commands with separate authorization, restated from [business-process-and-state-machine-architecture.md, Section 1](business-process-and-state-machine-architecture.md#1-command-architecture).
- Certified-Result-to-Medal-Tally-Recalculation is a named process manager, per [orchestration-choreography-and-process-manager-architecture.md, Section 1](orchestration-choreography-and-process-manager-architecture.md#1-process-managers) — reliability-critical, evaluated for outbox treatment.
- A result reaching `Held` blocks `Published` until the hold is released, per Section 2's Protest-Filing-to-Result-Hold process manager.

## 2. Protest and Appeal Workflow (WF-14, BC-17, High-Integrity)

Filing → completeness check → assignment → conflict declaration → result hold → evidence review → deliberation → decision → appeal where applicable → result correction → hold release → notification → audit.

**Do not invent filing periods or authorities** — restated absolutely per working rule 56; every protest-related timer and authority is a placeholder pending [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).

- `ProtestFiled`, `ResultPlacedOnHold`, `ProtestResolved` are the catalog's existing notification-worthy events.
- Protest-Filing-to-Result-Hold is synchronous/strong, never eventually consistent, restated from [orchestration-choreography-and-process-manager-architecture.md, Section 1](orchestration-choreography-and-process-manager-architecture.md#1-process-managers).
- SOD-03 applies: the adjudicator who certified the result may not resolve a protest against it — **blocked pending OD-09**. SOD-03b applies where a Tournament Manager also holds sole protest authority for their sport.
- Decision and appeal (where an appeal path exists) each require their own separate audit record, never collapsed into a single "resolved" entry.

## 3. Medal Tally Workflow (WF-15, BC-18, High-Integrity, Derived)

Recalculation request → certified-results input → derived tally → validation → discrepancy review → certification → publication → supersession → historical snapshot → audit.

- `MedalTallyRecalculated`, `MedalAwarded` are the catalog's existing notification-worthy events.
- **Medal Tally derives strictly from certified Official Results, never from raw Scoring data directly** — restated absolutely from [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md) Rule 6.
- SOD-04 applies: encoder and certifier of a tally snapshot must be different individuals — **blocked pending [OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules)**.
- No medal-tally rule (tie-breaking, ranking formula) is invented — restated absolutely per working rule 56, pending OD-12.
- A recalculation triggered by a later result correction produces a new tally snapshot, never a silent overwrite of the prior published tally, per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession).

## 4. Publication Workflow (Cross-Cutting)

Publication is a distinct, final-stage transition shared by Official Results, Medal Tally, Schedules, and Announcements — restated from [../06-design/high-integrity-approval-certification-and-publication-ux.md, Section 5](../06-design/high-integrity-approval-certification-and-publication-ux.md#5-publication-ux). Every publication transition:

- Requires the underlying record already be Certified (where certification applies) — publication never certifies as a side effect.
- Shows source state, certification status, privacy filtering, and public-field preview before the publish action, restated from the same UX reference.
- Feeds a public-projection rebuild (candidate automation AU-04, per [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model)) — the publication decision itself is always human, only the downstream projection rebuild is automatable.
- May use scheduled publication activation (candidate automation AU-06) for an already-certified record — restated from the same section.

## 5. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably the protest/appeal-authority and medal-tally-rules blockers (OD-09, OD-12), carried unchanged across every prior phase.
