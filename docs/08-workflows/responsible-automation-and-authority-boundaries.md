# PMMS Responsible Automation and Authority Boundaries

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../07-ai/human-in-the-loop-and-authority-model.md](../07-ai/human-in-the-loop-and-authority-model.md) · [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)

**No automation script, scheduled command, or AI agent is created here.** This document defines the authority boundaries any future automation must operate within.

---

## 1. Responsible Automation Principles (13)

| # | Principle |
|---|---|
| 1 | Automation uses explicit authority — never an implicit or assumed permission |
| 2 | Automation has a defined owner — a named role, never an orphaned script |
| 3 | Automation performs deterministic actions — condition and outcome are both fully specified in advance |
| 4 | Automation uses approved conditions — no automation triggers on an unvalidated heuristic |
| 5 | Automation is observable — every run is visible, per [workflow-audit-observability-metrics-and-support.md](workflow-audit-observability-metrics-and-support.md) |
| 6 | Automation is idempotent — a re-run produces the same end state |
| 7 | Automation can be disabled — a feature flag is the primary control, restated from [../07-ai/ai-observability-cost-quotas-and-operations.md, Section 5](../07-ai/ai-observability-cost-quotas-and-operations.md#5-ai-disablement) applied to non-AI automation as well |
| 8 | Automation has a manual fallback — every automated action has a documented manual equivalent |
| 9 | Automation does not hide accountability — every automated action attributes to its owning role and its enabling approval |
| 10 | Automation does not exceed user or service scope — restated from the intersection-not-union access model established in [../07-ai/ai-identity-authorization-scope-and-audit.md, Section 1](../07-ai/ai-identity-authorization-scope-and-audit.md#1-requesting-user-and-service-identity), applied to automation service identities generally |
| 11 | Automation does not convert an AI recommendation into an official action automatically — restated absolutely, Section 6 |
| 12 | Automation is tested before activation — per [workflow-testing-simulation-recovery-and-reconciliation.md](workflow-testing-simulation-recovery-and-reconciliation.md) |
| 13 | Automation changes are versioned — per [workflow-versioning-migration-and-active-instance-compatibility.md](workflow-versioning-migration-and-active-instance-compatibility.md) |

## 2. Automation Categories

| Category | Definition | Examples |
|---|---|---|
| Informational Automation | Generates information without altering business state | Generate reminders, update dashboards, create summaries |
| Operational Automation | Performs routine technical maintenance | Rebuild projections, process documents, expire temporary state, archive generated files |
| Workflow Automation | Advances a workflow along pre-approved rules | Assign task based on approved rules, escalate overdue task, trigger next approved step |
| High-Risk Automation | Touches a high-integrity domain's state directly | Credential expiration, result-publication scheduling, assignment expiration, access-restriction activation |

**High-risk automation requires strong controls and human override** — restated absolutely as this section's governing rule; every High-Risk Automation entry (Section 3) requires the full Automation Authority Model review before activation, and a human-accessible override/disablement path at all times.

## 3. Automation Authority Model

Every automation defines: Automation ID · owner · purpose · trigger · conditions · allowed actions · prohibited actions · service identity · permission · scope · affected records · audit · feature flag · retry behavior · failure behavior · manual override · disablement · version.

Automation entries use ID prefix `AU-` (a new, non-colliding scheme; distinct from `WF-`, `SOD-`, `BC-`, `OD-`, and every AI-phase prefix).

### Illustrative Automation Catalog (Candidate, None Enabled)

| ID | Name | Category | Owning Context | Trigger | Allowed Action | Prohibited Action |
|---|---|---|---|---|---|---|
| AU-01 | Credential Expiry | High-Risk | Accreditation (BC-19) | Scheduled, credential validity end reached | Mark credential Expired | Never revokes a still-valid credential; never denies access retroactively |
| AU-02 | Assignment Expiry | High-Risk | Identity and Access (BC-02) | Scheduled, assignment end date reached | Mark assignment Expired | Never revokes a role grant, only the time-bound assignment activating it |
| AU-03 | Reminder Dispatch | Informational | Notifications (BC-31) | Scheduled, per [scheduler-calendar-deadline-and-escalation-architecture.md](scheduler-calendar-deadline-and-escalation-architecture.md) | Send a reminder notification | Never marks the underlying task complete |
| AU-04 | Public Projection Rebuild | Operational | Public Information (BC-29) | Event-triggered (a source publication event) | Rebuild a read-only public projection | Never writes to any authoritative table |
| AU-05 | Overdue-Task Escalation | Workflow | Owning context of the overdue task | Scheduled, escalation timer elapsed | Notify the escalation owner | Never reassigns the task or overrides its authorization |
| AU-06 | Publication Activation | High-Risk | Owning publishing context (e.g., BC-16, BC-29) | Scheduled, approved release time reached | Change a pre-approved, already-certified record's visibility to Published | Never certifies, approves, or alters the underlying record's content |

**AU-06 is illustrative of the narrowest form of high-risk automation PMMS considers acceptable**: the record is already certified by a human before automation ever touches it; automation only changes *visibility timing* for an already-final decision, never the decision itself.

## 4. Rule-Based, Event-Triggered, Time-Triggered, User-Triggered, and Administrative Automation

| Trigger Type | Definition | Example |
|---|---|---|
| Rule-based | Fires when a defined condition over current state is true | A credential whose `valid_until` has passed |
| Event-triggered | Fires in reaction to a specific domain event | AU-04 firing on `ResultPublished` |
| Time-triggered | Fires on a schedule, independent of any specific event | AU-01, AU-02 checked daily |
| User-triggered | A human explicitly invokes an approved automated action | An operator manually triggers a projection rebuild |
| Administrative | Invoked by a Platform/Security Administrator for operational maintenance | A manual cache-refresh automation run |

## 5. AI-Assisted Automation Boundaries

AI may: suggest classification · suggest assignment · draft a message · recommend escalation · identify a conflict · propose a schedule — restated from [../07-ai/ai-vision-principles-and-governance.md, Section 5](../07-ai/ai-vision-principles-and-governance.md#5-human-accountability)'s advisory-only discipline, applied to workflow automation specifically.

**AI may not automatically:** approve eligibility · change scores · certify results · resolve protests · award medals · revoke permanent access · approve finances · issue medical decisions · publish protected data — restated absolutely, identical to the Phase 0.10 absolute prohibition list (working rules 15–16, 37–44).

**Any AI-assisted automation must require an authorized deterministic or human confirmation step** — restated absolutely as this section's single most important rule. An AI recommendation feeding an automation entry (e.g., "AI suggests this overdue task should escalate to X") still routes through AU-05's own deterministic trigger and human-visible audit trail — the AI's suggestion informs, it does not become AU-05's trigger condition by itself without human-approved rule definition.

## 6. Human Approval for Automation

Every automation entry (Section 3) requires human approval before activation — following the same Workflow-Governance Model approval process as any workflow-definition change, per [workflow-vision-principles-and-governance.md, Section 3](workflow-vision-principles-and-governance.md#3-workflow-governance-model). High-Risk Automation additionally requires security and quality review.

## 7. Automation Feature Flags, Disablement, and Rollback

Every automation entry defaults to a feature flag in the Off state pending explicit approval — restated from Phase 0.10's identical AI-feature-flag discipline, now applied to all automation, AI-assisted or not. Automation disablement is the primary, fastest incident-response mechanism, faster than a code-deployment rollback, per [workflow-incident-change-and-release-governance.md, Section 2](workflow-incident-change-and-release-governance.md#2-automation-incident-response).

## 8. Automation Audit, Observability, and Quotas

Every automated action produces an audit record identical in rigor to a human-initiated action's audit record — restated absolutely per working rule 50 ("every automated action must have an owner, purpose, authority, feature flag, audit trail, failure behavior, and disablement path"). Automation observability and quota concerns are detailed in [workflow-audit-observability-metrics-and-support.md](workflow-audit-observability-metrics-and-support.md).

## 9. Automation Conflict Handling and Escalation

Two automation entries that could plausibly act on the same record within the same window (e.g., AU-01 credential expiry and a manual revocation in progress) resolve via the record's own concurrency-control mechanism (per [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md)) — automation never silently "wins" a race against a human-initiated action; a conflict is surfaced for manual resolution.

## 10. Prohibited Autonomous Actions (Absolute, Restated)

Automation must not: approve eligibility (working rule 37) · alter or certify official scores or results (working rule 38) · resolve protests (working rule 39) · award medals outside approved deterministic calculation and certification workflows (working rule 40) · make medical decisions (working rule 41) · approve financial actions (working rule 42) · grant privileges or permanent access (working rule 43) · publish protected information (working rule 44).

## 11. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-20 (which, if any, automation entries beyond the illustrative catalog above are prioritized for the first pilot) and WD-21 (automation-conflict resolution mechanism specifics).
