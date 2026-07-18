# PMMS Meet, Registration, Eligibility, and Entry Workflows

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-01, WF-02, WF-03, WF-04, WF-06) · [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md)

This document adds the event, notification, state-machine, and automation layer to the existing Phase 0.2 workflow definitions — it does not redefine WF-01 through WF-06's steps, actors, or preconditions, per working rule 4.

---

## 1. Meet Lifecycle Workflow (WF-01, BC-04)

Conceptual states (not finalized without Phase 0.2 validation): `Draft → Configured → Registration Open → Registration Closed → Validation → Competition Active → Closing → Closed → Archived`.

| Element | Direction |
|---|---|
| Authority | Meet Administrator, per the existing WF-01 actor definition |
| Transitions | Each state change is a distinct command, never a bare status-field update |
| Timers | Registration-close deadline, competition-start reminder — per [scheduler-calendar-deadline-and-escalation-architecture.md](scheduler-calendar-deadline-and-escalation-architecture.md) |
| Dependent workflows | Registration (Section 2), Eligibility (Section 3), Entry (Section 4) all gate on Meet state |
| Restrictions | `Registration Open` is a precondition for WF-03; `Competition Active` is a precondition for scoring workflows |
| Notifications | `MeetCreated`, `MeetActivated` (both notification-worthy, per [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md)) |
| Closure checks | Full detail: [document-reporting-audit-and-meet-closure-workflows.md, Section 5](document-reporting-audit-and-meet-closure-workflows.md#5-meet-closure-workflow-wf-23-bc-04-coordinating-bc-30) |
| Reopen rules | Reopening a `Closed` meet is High Risk (per [workflow-classification-and-risk-model.md, Section 2](workflow-classification-and-risk-model.md#2-workflow-risk-classification)) and requires elevated authorization, never a routine action |
| Audit | Full lifecycle audit per [workflow-audit-observability-metrics-and-support.md, Section 1](workflow-audit-observability-metrics-and-support.md#1-workflow-audit) |

## 2. Registration Workflow (WF-03, BC-08)

Draft registration → submission → completeness check → return for correction → reviewer assignment → review → recommendation where applicable → approval or rejection → reopen → withdrawal → lock → audit → notification → accreditation readiness.

- Draft is never submission — restated from [../06-design/form-validation-draft-and-workflow-experience.md, Section 4](../06-design/form-validation-draft-and-workflow-experience.md#4-draft-and-autosave-experience).
- `RegistrationSubmitted`, `RegistrationWithdrawn`, `AthleteRegistered` are the catalog's existing domain events for this workflow.
- Return-for-correction is a named workflow state, never a silent rejection — the submitter receives an actionable notice per [notification-and-recipient-resolution-architecture.md, Section 7](notification-and-recipient-resolution-architecture.md#7-notification-content-rules).
- Withdrawal is the submitting actor's own authority, subject to any applicable lock state, per [workflow-audit-observability-metrics-and-support.md, Section 6](workflow-audit-observability-metrics-and-support.md#6-pause-resume-cancellation-withdrawal-reopen-and-manual-intervention).
- Accreditation readiness is a gate condition Registration approval feeds into, per the Eligibility-to-Accreditation-Readiness process manager in [orchestration-choreography-and-process-manager-architecture.md, Section 1](orchestration-choreography-and-process-manager-architecture.md#1-process-managers).

## 3. Eligibility Workflow (WF-04, BC-09, High-Integrity)

`EligibilityRequirementsSubmitted → (Reviewer Assignment) → Review → Recommendation → ApproveEligibility / RejectEligibility → (Reopen where authorized)`.

- SOD-01 applies: the submitting/reviewing individual is never also the approver for the same case, restated from [human-tasks-approvals-reviews-and-certifications.md, Section 3](human-tasks-approvals-reviews-and-certifications.md#3-separation-of-duties-applied-to-workflows) — **blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)**.
- `EligibilityApproved` and `EligibilityRejected` are notification-worthy domain events.
- This is a Human Approval Workflow, High Risk tier (per [workflow-classification-and-risk-model.md, Section 2](workflow-classification-and-risk-model.md#2-workflow-risk-classification)) — synchronous approval, never dependent solely on an unconfirmed background job, restated from [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules).
- **AI assistance remains advisory only** — restated absolutely from [../07-ai/athlete-eligibility-document-review-assistance.md](../07-ai/athlete-eligibility-document-review-assistance.md); AI may extract and compare, never approve or reject.
- Review-due-date timer is a policy-dependent timer marked for validation, per [scheduler-calendar-deadline-and-escalation-architecture.md, Section 8](scheduler-calendar-deadline-and-escalation-architecture.md#8-timers-and-escalations).

## 4. Competition Entry Workflow (WF-06, BC-11)

Eligible-athlete selection → entry creation → validation → conflict detection → submission → approval if required → locking → withdrawal → substitution where approved → tournament inclusion → audit.

- `CompetitionEntrySubmitted`, `CompetitionEntryConfirmed`, `EntryLocked` are the catalog's existing events.
- Entry locking is a strongly-consistent, synchronous transition (per [../01-architecture/phase-0.2-domain-architecture.md, Section 9](../01-architecture/phase-0.2-domain-architecture.md#9-transaction-and-consistency-boundaries)) — never eventually consistent.
- Conflict detection (a scheduling or eligibility conflict at entry time) surfaces to the submitting committee for resolution; it does not auto-resolve, restated from [responsible-automation-and-authority-boundaries.md, Section 9](responsible-automation-and-authority-boundaries.md#9-automation-conflict-handling-and-escalation).

## 5. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-01/WD-12 (eligibility review-due-date policy, blocked on OD-07) and this document's shared dependency on every prior phase's identical eligibility-authority blocker.
