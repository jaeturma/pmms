# PMMS Workflow Audit, Observability, Metrics, and Support

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md) · [../05-devops/observability-logging-metrics-tracing-and-alerting.md](../05-devops/observability-logging-metrics-tracing-and-alerting.md)

---

## 1. Workflow Audit

Every workflow audit record captures: workflow · instance · definition version · actor · effective role · assignment · scope · command · previous state · new state · reason · evidence reference · event · automated-or-human action · service identity · device · correlation ID · time · result · exception · override.

This extends, and does not redefine, the 27 audit-event categories in [../03-security/audit-and-security-event-architecture.md, Section 2](../03-security/audit-and-security-event-architecture.md#2-audit-event-categories) — every workflow transition audit record is one instance of an existing category, never a parallel audit mechanism.

## 2. Workflow Observability

Monitored: active instances · completed instances · failed instances · stalled instances · overdue tasks · timer backlog · queue latency · failed jobs · retry rate · manual interventions · automation disablements · notification failure · event-delivery delay · duplicate events · reconciliation issues.

Extends [../05-devops/observability-logging-metrics-tracing-and-alerting.md](../05-devops/observability-logging-metrics-tracing-and-alerting.md) with workflow-instance-level visibility beyond aggregate infrastructure metrics.

## 3. Workflow Metrics

Candidate metrics: cycle time · wait time · completion rate · rejection rate · return-for-correction rate · escalation rate · overdue-task count · retry rate · failure rate · manual-intervention rate · notification-delivery rate · workflow abandonment · automation-override rate · queue delay · projection delay.

**Numeric targets remain placeholders** — restated per this phase's own working instruction and every prior phase's "no invented numbers" discipline; no specific cycle-time, completion-rate, or SLA value is defined here.

## 4. Workflow Dashboards

A candidate operational dashboard surfaces Section 2's observability signals per workflow category and per risk tier (per [workflow-classification-and-risk-model.md, Section 2](workflow-classification-and-risk-model.md#2-workflow-risk-classification)) — no specific dashboard implementation is created in this phase; this is a requirements statement for a future implementation phase.

## 5. Workflow Support Tools

Support tools may need: view workflow instance · view event history · view task assignments · view timers · inspect failed step · retry safe step · reassign task · pause workflow · resume workflow · cancel workflow · execute approved repair · reconcile projections.

**All support actions require authorization and audit** — restated absolutely; a support tool is not a bypass of the ordinary authorization model, it is simply a specialized, privileged application surface subject to the same authorization decision sequence as any other action, per [workflow-identity-authorization-scope-and-separation-of-duties.md, Section 1](workflow-identity-authorization-scope-and-separation-of-duties.md#1-workflow-authorization). This restates and extends [../05-devops/production-support-access-and-data-repair-operations.md](../05-devops/production-support-access-and-data-repair-operations.md)'s existing support-access governance to workflow-specific tooling.

## 6. Pause, Resume, Cancellation, Withdrawal, Reopen, and Manual Intervention

| Action | Definition | Authorization |
|---|---|---|
| Pause | Temporarily halts a workflow instance's timers and automated progression | Requires the workflow's defined pause authority, always audited |
| Resume | Restarts a paused instance | Same authority as pause |
| Hold | A targeted block on a specific transition (e.g., a result hold from a filed protest) rather than a full-instance pause | Owning context's defined hold authority |
| Cancellation | Terminates an instance before completion, never a silent deletion | Requires an explicit reason, recorded per Section 1 |
| Withdrawal | An actor-initiated cancellation of their own submission (e.g., registration withdrawal) | The submitting actor's own authority, subject to any applicable lock state |
| Reopen | Returns a completed or terminal-state instance to an active state | Requires elevated authorization — never available to the same authority level that completed it, where SOD applies |
| Compensation | An explicit corrective action for a partially-completed cross-context workflow | Defined per process manager, per [orchestration-choreography-and-process-manager-architecture.md, Section 4](orchestration-choreography-and-process-manager-architecture.md#4-compensation) |
| Manual intervention | Any of the above, performed by an authorized human outside the workflow's normal automated path | Always supported for exceptional cases, restated per working rule 53 |
| Exceptional processing | A documented, authorized deviation from the standard workflow path for a specific instance | Requires the same rigor as the standard path it deviates from — never a lower bar |
| Break-glass workflow | An emergency-access path, per [../03-security/authorization-and-privileged-access-assurance.md, Section 5](../03-security/authorization-and-privileged-access-assurance.md#5-support-impersonation-governance) | Remains genuinely undecided, restated unchanged from every prior phase — not a default capability to build toward |

## 7. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-23 (workflow-metric numeric targets, deliberately undefined) and WD-24 (whether a dedicated workflow-support dashboard is built or existing Horizon/admin tooling suffices initially).
