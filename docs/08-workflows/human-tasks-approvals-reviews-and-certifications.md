# PMMS Human Tasks, Approvals, Reviews, and Certifications

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) · [../06-design/high-integrity-approval-certification-and-publication-ux.md](../06-design/high-integrity-approval-certification-and-publication-ux.md)

---

## 1. Human Task Architecture

Every human task defines: task type · assignee · assignment basis · role · scope · priority · due date · required evidence · available actions · delegated authority · reassignment · escalation · completion · cancellation · audit.

**A human task must not remain valid after the user's assignment or authority expires** — restated as this section's governing rule; a task list is regenerated from current assignment state, never cached indefinitely against a stale authority snapshot.

## 2. Approval, Review, and Certification Are Distinct

PMMS distinguishes: Review · Recommendation · Validation · Approval · Certification · Publication · Acknowledgment · Acceptance · Override. **Each has separate permissions and transitions — PMMS does not use a generic approval workflow for all domains**, restated absolutely, directly extending [../06-design/high-integrity-approval-certification-and-publication-ux.md, Section 2](../06-design/high-integrity-approval-certification-and-publication-ux.md#2-approval-and-certification-interfaces)'s "review, recommend, approve, certify, publish, and override are never conflated into one generic button" from the interface layer down into the workflow layer itself.

| Action | Definition | Example |
|---|---|---|
| Review | Examine evidence without deciding | An Eligibility Reviewer examines submitted documents |
| Recommendation | A non-binding suggested disposition | A Reviewer recommends approval to the Eligibility Approver |
| Validation | Confirm a specific technical/data correctness condition | A Result Validator confirms a score entry is internally consistent |
| Approval | A binding decision to accept a request | An Eligibility Approver approves a case |
| Certification | A binding, elevated attestation of correctness for an official record | A Technical Delegate certifies an Official Result |
| Publication | Making a certified record visible to its intended audience | Publishing a certified Official Result to the public portal |
| Acknowledgment | Confirming receipt or awareness, without approving | A recipient acknowledges a mandatory security notice |
| Acceptance | Agreeing to a proposed change or assignment | An Official accepts a duty assignment |
| Override | An elevated, audited exception to a normal rule | A Security Coordinator overrides an access-scan denial |

## 3. Separation of Duties Applied to Workflows

Restated from [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) (SOD-01 through SOD-11), applied specifically at the workflow-transition level:

- A reviewer may not be the final approver where separation is required (SOD-01, blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)).
- A score encoder may not certify the same result where prohibited (SOD-02, SOD-03).
- A finance encoder may not approve the same transaction (SOD-06).
- An emergency-access reviewer must be different from the user invoking it (SOD-05).
- Support staff cannot approve business actions while impersonating users (SOD-11, absolute, no exception).

Every workflow state machine (per [business-process-and-state-machine-architecture.md, Section 2](business-process-and-state-machine-architecture.md#2-state-machine-architecture)) includes a "Separation-of-duties rule" field naming the applicable SOD-XX entry, or explicitly stating "None" if no conflict applies.

## 4. Conflict of Interest

Beyond structural SOD rules, a workflow participant may declare a conflict of interest for a specific instance (e.g., a Technical Official related to a competing athlete) — restated from [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md)'s WF-08 "conflict declaration" step and WF-14's protest-resolution conflict handling (SOD-03b). Conflict declaration triggers reassignment, never a silent continuation.

## 5. Assignment Validity for Task Ownership

A task assigned to a role/assignment that has since expired, been revoked, or reassigned is not completable by the original holder — restated from [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md)'s time-bound assignment model, applied to human-task ownership specifically.

## 6. Escalation, Reminder, and Deadline Workflows (Cross-Reference)

Full detail: [scheduler-calendar-deadline-and-escalation-architecture.md](scheduler-calendar-deadline-and-escalation-architecture.md).

## 7. Reversal, Correction, and Supersession Workflows

Restated from [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession): a correction, reversal, or supersession is itself a workflow transition — never a silent data edit. Each requires: reason capture · evidence reference · the same authorization rigor as the original action · an audit record linking the new version to the one it corrects or supersedes.

## 8. Handoff Workflows

A handoff (e.g., a committee shift change, a venue-operations handoff between meet days) transfers active task ownership and context — restated from [../06-design/committee-logistics-medical-finance-and-support-experience.md](../06-design/committee-logistics-medical-finance-and-support-experience.md)'s committee experience architecture. A handoff requires explicit acknowledgment from the receiving party; an unacknowledged handoff leaves the prior assignee still notionally responsible until acknowledgment or timeout escalation.

## 9. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-04 (formal conflict-of-interest declaration workflow, beyond the structural SOD matrix).
