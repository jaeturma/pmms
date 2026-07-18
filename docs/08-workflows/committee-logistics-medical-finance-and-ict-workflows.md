# PMMS Committee, Logistics, Medical, Finance, and ICT Workflows

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-17, WF-18, WF-19, WF-20)

This document adds the event, notification, state-machine, and automation layer to the existing Phase 0.2 workflow definitions for WF-17 through WF-20, and defines Finance and ICT workflows not yet numbered in that catalog (continuing the sequence at WF-24 onward, per [workflow-vision-principles-and-governance.md, Section 4](workflow-vision-principles-and-governance.md#4-workflow-ownership)).

---

## 1. Medical Workflow (WF-17, BC-21)

Incident creation → triage classification placeholder → medical-team assignment → encounter → escalation → transfer or referral record → closure → restricted reporting → audit.

**No clinical decision or protocol is defined here** — restated absolutely per this section's governing instruction, consistent with [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)'s "Medical Information" domain treatment.

- `MedicalIncidentRecorded` is an internal, restricted event per the existing catalog — never broadcast beyond its authorized medical/security audience.
- SOD-09 applies: Medical Officer/Staff roles must never combine with Public Information Publisher/Media Coordinator roles — **blocked pending [OD-15](../00-product/open-decisions.md#od-15--medical-data-handling)**.
- This is PMMS's strongest privacy boundary at the workflow layer, restated from [../06-design/committee-logistics-medical-finance-and-support-experience.md, Section 2](../06-design/committee-logistics-medical-finance-and-support-experience.md#2-medical-experience) — summary versus detailed views are architecturally distinct.
- Medical events are excluded from general AI use by default, restated from [../07-ai/ai-security-privacy-and-data-minimization.md, Section 5](../07-ai/ai-security-privacy-and-data-minimization.md#5-sensitive-document-handling).

## 2. Logistics Workflows (WF-18/WF-19/WF-20, BC-22/BC-23/BC-24)

Billeting assignment · room occupancy · meal entitlement · meal distribution · transport scheduling · trip assignment · passenger confirmation · issue escalation · closure · reconciliation.

- These are Moderate-Risk workflows (per [workflow-classification-and-risk-model.md, Section 2](workflow-classification-and-risk-model.md#2-workflow-risk-classification)) — standard authorization/audit, without the synchronous/high-integrity rigor of Scoring or Eligibility.
- Reconciliation (e.g., confirming meal counts match distribution records) is a candidate scheduled reconciliation task, per [workflow-testing-simulation-recovery-and-reconciliation.md, Section 3](workflow-testing-simulation-recovery-and-reconciliation.md#3-workflow-reconciliation).
- Committee handoffs (a billeting shift change) follow [human-tasks-approvals-reviews-and-certifications.md, Section 8](human-tasks-approvals-reviews-and-certifications.md#8-handoff-workflows).

## 3. Finance Workflow (WF-24, BC-26, High-Integrity — New Numbering)

Draft → submission → review → return for correction → approval → rejection → adjustment → reversal → reconciliation → closure → restricted export → audit.

**Preserve encoder and approver separation** — restated absolutely per SOD-06: Encoder (Finance staff) and Approver (Finance Coordinator/head) must be different individuals for the same transaction, restated from [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md).

- Finance is a High-Risk workflow category per [workflow-classification-and-risk-model.md, Section 2](workflow-classification-and-risk-model.md#2-workflow-risk-classification) — financial approval is never automated (working rule 42).
- Export of finance data follows [../03-security/data-sharing-export-and-public-disclosure-controls.md](../03-security/data-sharing-export-and-public-disclosure-controls.md) with no public display except approved summaries, restated from [../06-design/committee-logistics-medical-finance-and-support-experience.md, Section 3](../06-design/committee-logistics-medical-finance-and-support-experience.md#3-finance-experience).
- Reversal/adjustment use the correction-supersedes-never-overwrites pattern, never a destructive edit.

## 4. ICT Workflow (WF-25, BC-27, New Numbering)

Ticket → classification → assignment → priority → response → workaround → resolution → reopen → major incident → root cause → closure → knowledge-article draft.

- ICT monitoring alerts route to the Administrative channel (ICT-scoped), per [realtime-broadcast-and-reverb-message-architecture.md, Section 2](realtime-broadcast-and-reverb-message-architecture.md#2-channel-taxonomy-restated-and-extended).
- SOD-10 applies: ICT Coordinator (device provisioning) and Security Coordinator (security review) must remain distinct roles.
- A "major incident" classification within this workflow is a Moderate-to-High Risk escalation, feeding the platform-wide incident process in [workflow-incident-change-and-release-governance.md](workflow-incident-change-and-release-governance.md) where it also constitutes a workflow/automation incident.
- A knowledge-article draft produced from ticket resolution is a candidate future input to Phase 0.10's Committee Knowledge Assistant ([../07-ai/helpdesk-and-committee-knowledge-assistants.md](../07-ai/helpdesk-and-committee-knowledge-assistants.md)) once that capability is ever approved — not an active integration today.

## 5. Committee Coordination (Cross-Reference)

Full committee experience architecture: [../06-design/committee-logistics-medical-finance-and-support-experience.md, Section 1](../06-design/committee-logistics-medical-finance-and-support-experience.md#1-committee-experience-architecture). Committee-scoped messaging boundaries: [internal-messaging-announcements-and-communication-boundaries.md, Section 3](internal-messaging-announcements-and-communication-boundaries.md#3-committee-and-delegation-communications).

## 6. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-29 (whether Finance/ICT workflows are formally added to [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) as WF-24/WF-25 in a future Phase 0.2 revision, or remain defined only in this package).
