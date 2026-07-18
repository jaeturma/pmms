# PMMS Scheduler, Calendar, Deadline, and Escalation Architecture

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 4](../05-devops/application-worker-scheduler-and-realtime-deployment.md#4-scheduler-deployment) · [../00-product/open-decisions.md](../00-product/open-decisions.md)

**No scheduled command, cron configuration, or scheduler code is created here.**

---

## 1. Scheduled-Job Architecture

Candidate scheduled tasks: reminder dispatch · assignment expiry · credential expiry · publication activation · task escalation · stale-workflow detection · reconciliation · projection rebuild · archive preparation · backup verification · health heartbeat.

Scheduled tasks must be: idempotent · observable · overlap-safe (a task must not start a second concurrent run while a prior run is still executing) · time-zone-aware · scope-aware (meet-specific where applicable) · owner-assigned · recoverable.

## 2. Laravel Scheduler Boundaries

Restated from [../05-devops/application-worker-scheduler-and-realtime-deployment.md, Section 4](../05-devops/application-worker-scheduler-and-realtime-deployment.md#4-scheduler-deployment): exactly one scheduler instance runs per environment, singleton, never horizontally scaled with the web tier. A scheduled task dispatches a queued job for any non-trivial work — the scheduler itself triggers, it does not perform business logic inline.

## 3. Event-Triggered Scheduling

Distinguished from time-triggered scheduling: a timer may be *set* by a domain event (e.g., `EligibilityRequirementsSubmitted` sets a review-due-date timer) but *fires* on a schedule check, not at the moment the event occurs. This distinction keeps timer-firing logic centralized and observable rather than scattered across every event handler that happens to need a future action.

## 4. Delayed Jobs, Deferred Workflows, and Recurring Workflows

| Category | Definition | Example |
|---|---|---|
| Delayed job | A single job scheduled to run at (or after) a specific future time | Send a reminder 3 days before a computed deadline |
| Deferred workflow | A workflow step intentionally held pending a future condition | Publication activation held until a scheduled release time |
| Recurring workflow | A workflow that re-triggers on a fixed cadence | Daily operational summary generation |

## 5. Business Calendar Architecture (Readiness)

Readiness is defined for: meet time zone · calendar days · working days · weekends · holidays · event-specific operating hours · deadline pause · emergency extensions · policy-driven deadline rules.

**Do not invent official deadline calculations** — restated absolutely per working rule 56, directly extending working rule 10 from Phase 0.1 ("rules must be source-backed"). No specific holiday list, working-day definition, or deadline formula is defined in this document; every such rule is a placeholder pending an approved DepEd/sports-governing-body source.

## 6. Deadline Computation and Working-Day Computation

Deadline computation is a candidate service (not implemented) that would take: a start reference (e.g., filing date) · a policy-defined offset (e.g., "3 working days") · the applicable business calendar · time-zone context — and produce a computed due date. **No specific offset value is defined** — every policy-dependent timer is marked for validation (Section 8).

## 7. Time-Zone Behavior

All meet-related timers use the meet's configured time zone, not the server's default time zone or an individual user's browser time zone — restated as a readiness principle; no specific time-zone-handling library or mechanism is selected in this phase.

## 8. Timers and Escalations

Timer types: due date · reminder date · escalation date · expiry date · publication date · archival date · inactivity timeout.

Each timer defines: source rule · time zone · pause behavior · recalculation · override authority · audit · missed-timer recovery.

**Policy-dependent timers and authorities are explicitly marked for validation** — restated per working rule 57. Notably:

| Timer/Authority | Depends On | Status |
|---|---|---|
| Eligibility review due date | An approved review-turnaround policy | Not yet defined — placeholder, pending [OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) |
| Protest filing window | An approved protest-filing period | Not yet defined — placeholder, pending [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) |
| Appeal window | An approved appeal period | Not yet defined — placeholder, pending [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) |
| Result-certification escalation window | An approved certification-turnaround expectation | Not yet defined — no source identified |

## 9. Pause and Resume

A timer may be paused (e.g., a protest under active deliberation pauses a related publication timer) and resumed — pause/resume is itself an audited action, never a silent gap in the timer's history.

## 10. Escalation Timers

An overdue human task (Section, [human-tasks-approvals-reviews-and-certifications.md, Section 1](human-tasks-approvals-reviews-and-certifications.md#1-human-task-architecture)) triggers an escalation timer — escalating to the task's defined escalation owner (e.g., a committee lead) rather than silently remaining overdue indefinitely. Escalation itself is Notification-Class "Escalation" (per [notification-and-recipient-resolution-architecture.md, Section 5](notification-and-recipient-resolution-architecture.md#5-notification-classes)), never a workflow-state change on its own — escalation informs, it does not reassign or override authority automatically.

## 11. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-11 (business-calendar/holiday-reference source and timing) and WD-12 (every policy-dependent deadline value in the table above, collectively blocked on OD-07/OD-09).
