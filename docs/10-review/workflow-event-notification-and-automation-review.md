# PMMS Workflow, Event, Notification, and Automation Review

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** [../08-workflows/phase-0.11-event-workflow-notification-automation-architecture.md](../08-workflows/phase-0.11-event-workflow-notification-automation-architecture.md)

---

## 1. States and Commands

State-machine architecture deliberately avoids one universal status vocabulary — confirmed consistent with, and never contradicting, Phase 0.5's per-domain versioning model and Phase 0.9's high-integrity state-visibility UX rule.

## 2. Events

Six-type taxonomy (domain/application/integration/broadcast/audit/notification) is Phase 0.4's own architecture, correctly restated rather than redefined by Phase 0.11 — confirmed zero contradiction.

## 3. Human Tasks

Nine distinct approval-family actions (Review, Recommendation, Validation, Approval, Certification, Publication, Acknowledgment, Acceptance, Override) is an original Phase 0.11 contribution with no prior-phase precedent to contradict — internally consistent.

## 4. Process Managers

Six named cross-context process managers are a direct, faithful implementation of Phase 0.4's own orchestration table (Section 6, "Workflow Orchestration") — confirmed as a restatement-and-formalization, not a new invention contradicting Phase 0.4.

## 5. Queues and Scheduler

Fully consistent with Phase 0.4/0.8's existing queue and scheduler architecture — no redefinition found.

## 6. Notifications

"Notification delivery is never workflow state" is the direct extension of Phase 0.4's original notification-architecture principle ("notifications must not become the only record of a business decision") — confirmed identical principle, never weakened.

## 7. Reverb

Consistent with Phase 0.4's real-time architecture; Phase 0.11 adds message-reliability and workflow-boundary detail without redefining channel taxonomy or authorization model.

## 8. Messaging

Internal messaging is explicitly evaluated, not committed — "avoid building a general-purpose social chat platform unless justified" is a self-aware constraint against scope creep, not a gap.

## 9. Automation

The Responsible Automation Authority Model (13 principles, four categories, per-entry authority fields) is the architecture's most detailed governance mechanism for a capability that does not yet exist — zero automation entry is enabled, six illustrative candidates documented at Documented evidence level only.

## 10. Versioning

Workflow-definition versioning and active-instance-compatibility rules directly extend Phase 0.5's temporal-history model and Phase 0.8's phased-migration pattern — confirmed non-contradictory, a successful cross-phase pattern reuse.

## 11. Reconciliation

Eight-category reconciliation model always surfaces discrepancies for human review, never silently auto-resolves — consistent with the "no silent correction" principle restated since Phase 0.5.

## 12. Overly Generic or Under-Specified Workflows

**No workflow was found to be overly generic.** This review specifically checked for the risk named in the required structure (a workflow so generic it fails to capture actual domain-specific rules) — every one of the 25 named workflows (WF-01–WF-25) traces to a specific bounded context and a specific set of domain events, never a placeholder "generic approval workflow." The one area flagged for attention is that WF-24 (Finance) and WF-25 (ICT), introduced in Phase 0.11, have not yet been formally back-ported into the Phase 0.2 workflow-and-command-catalog — tracked as [WD-29](../08-workflows/workflow-open-decisions.md#wd-29--financeict-workflow-catalog-formalization), a low-severity documentation-consistency item, not a workflow-design defect.

## 13. Risks

The outbox-versus-`after_commit` decision (WD-08, carried unresolved across three phases now) remains the workflow architecture's single largest reliability risk — restated from [application-runtime-api-and-integration-review.md, Section 10](application-runtime-api-and-integration-review.md#10-unclear-boundaries).

## 14. Recommendation

Workflow architecture is mature and internally consistent. Its primary blocker is WD-08 (outbox pattern) and WD-12 (eligibility/protest/appeal deadlines, blocked on OD-07/OD-09) — both requiring resolution before the affected workflows can be implemented with confidence.

## 15. Open Questions

WD-08, WD-12, and WD-29 remain the highest-priority workflow decisions — see [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md).
