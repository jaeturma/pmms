# PMMS Event-Driven Workflow, Notification, Messaging, and Responsible Automation Documentation — `docs/08-workflows/`

This directory contains the Phase 0.11 (event-driven workflows, notifications, messaging, and responsible automation architecture) documentation for the **Provincial Meet Management System (PMMS)**. It builds directly on the bounded contexts and domain-events catalog (Phase 0.2), authorization model (Phase 0.3), application/event/queue architecture (Phase 0.4), data/persistence architecture (Phase 0.5), security/privacy/audit/governance architecture (Phase 0.6), quality-engineering architecture (Phase 0.7), DevOps/operations architecture (Phase 0.8), design/UX architecture (Phase 0.9), and AI-assisted platform architecture (Phase 0.10) to define how PMMS's business processes actually coordinate — through explicit, observable, recoverable, and versioned events, workflows, notifications, messaging, and controlled automation.

**No Laravel event, listener, job, notification, mailable, broadcast, command, or scheduled task is contained in this directory.** No workflow-engine, messaging, queue, or notification package is installed. No migration or workflow table, queue configuration, Reverb channel, notification template for production use, provider integration, automation script, or AI agent is created. It is workflow architecture documentation only, per the Phase 0.11 working rules.

## Purpose

Phase 0.11 exists to define, once and consistently, how PMMS's 25 named workflows (WF-01–WF-25), 30+ cataloged domain events, and every future notification, message, and automation coordinate — before each is implemented independently and inconsistently. See [phase-0.11-event-workflow-notification-automation-architecture.md, Section 2](phase-0.11-event-workflow-notification-automation-architecture.md#2-executive-summary) for the full rationale.

## Document Index

| Document | Purpose |
|---|---|
| [phase-0.11-event-workflow-notification-automation-architecture.md](phase-0.11-event-workflow-notification-automation-architecture.md) | Primary Phase 0.11 document: vision/principles/governance, classification/risk, state machines, orchestration, human tasks, event architecture, message reliability, queues, scheduling, notifications, real-time, messaging, responsible automation, identity/SOD, audit/observability, versioning, offline/mobile/AI boundaries, domain-specific workflows, testing, incident governance, open decisions, acceptance criteria |
| [workflow-vision-principles-and-governance.md](workflow-vision-principles-and-governance.md) | Workflow vision, 20 event-driven principles, governance model, ownership structure |
| [workflow-classification-and-risk-model.md](workflow-classification-and-risk-model.md) | 7 workflow categories, 3-tier risk model, risk-to-control mapping |
| [business-process-and-state-machine-architecture.md](business-process-and-state-machine-architecture.md) | Command architecture, state-machine architecture, long-running workflow architecture |
| [orchestration-choreography-and-process-manager-architecture.md](orchestration-choreography-and-process-manager-architecture.md) | 6 named process managers, orchestration vs. choreography, saga readiness |
| [human-tasks-approvals-reviews-and-certifications.md](human-tasks-approvals-reviews-and-certifications.md) | Human task architecture, 9 distinct approval-family actions, SOD application, conflict of interest, handoff |
| [event-taxonomy-ownership-and-contracts.md](event-taxonomy-ownership-and-contracts.md) | 6-type event taxonomy, domain event ownership, payload rules, naming conventions |
| [event-metadata-versioning-ordering-and-correlation.md](event-metadata-versioning-ordering-and-correlation.md) | Event metadata, schema versioning, correlation/causation, ordering, retention, sensitive-event restrictions |
| [outbox-inbox-idempotency-and-message-reliability.md](outbox-inbox-idempotency-and-message-reliability.md) | Transactional outbox evaluation, inbox/deduplication, idempotency requirements |
| [queue-routing-priority-retry-and-failure-architecture.md](queue-routing-priority-retry-and-failure-architecture.md) | Queue-category validation, priority/isolation, retry/backoff, failed-job and dead-letter readiness |
| [scheduler-calendar-deadline-and-escalation-architecture.md](scheduler-calendar-deadline-and-escalation-architecture.md) | Scheduled-job architecture, business calendar readiness, timers and escalations |
| [notification-and-recipient-resolution-architecture.md](notification-and-recipient-resolution-architecture.md) | Notification lifecycle, recipient resolution, mandatory notices, delivery status, throttling |
| [email-sms-push-and-in-app-delivery-architecture.md](email-sms-push-and-in-app-delivery-architecture.md) | Delivery-channel architecture (in-app, email, SMS, push, real-time) |
| [realtime-broadcast-and-reverb-message-architecture.md](realtime-broadcast-and-reverb-message-architecture.md) | Reverb channel taxonomy, authorization, payload rules, reconnection/recovery, fallback |
| [internal-messaging-announcements-and-communication-boundaries.md](internal-messaging-announcements-and-communication-boundaries.md) | Internal messaging boundaries (evaluated, not committed), announcements, attachment controls |
| [responsible-automation-and-authority-boundaries.md](responsible-automation-and-authority-boundaries.md) | 13 automation principles, 4 categories, Automation Authority Model, AI-assisted automation boundaries |
| [workflow-identity-authorization-scope-and-separation-of-duties.md](workflow-identity-authorization-scope-and-separation-of-duties.md) | Workflow authorization/scope, SOD/conflict-of-interest cross-reference, device trust, service identities |
| [workflow-audit-observability-metrics-and-support.md](workflow-audit-observability-metrics-and-support.md) | Workflow audit fields, observability signals, metrics, dashboards, support tools, pause/resume/reopen |
| [workflow-versioning-migration-and-active-instance-compatibility.md](workflow-versioning-migration-and-active-instance-compatibility.md) | Workflow-definition versioning, active-instance migration, workflow configuration |
| [offline-mobile-device-public-and-ai-workflow-boundaries.md](offline-mobile-device-public-and-ai-workflow-boundaries.md) | Offline/mobile/shared-device/public/AI workflow boundaries, never-final-offline list |
| [meet-registration-eligibility-and-entry-workflows.md](meet-registration-eligibility-and-entry-workflows.md) | WF-01, WF-03, WF-04, WF-06 — event/notification/automation layer |
| [tournament-official-schedule-and-scoring-workflows.md](tournament-official-schedule-and-scoring-workflows.md) | WF-07, WF-08, WF-09, WF-10, WF-11 — event/notification/automation layer |
| [result-protest-medal-and-publication-workflows.md](result-protest-medal-and-publication-workflows.md) | WF-12, WF-13, WF-14, WF-15 — event/notification/automation layer |
| [accreditation-access-validation-and-security-workflows.md](accreditation-access-validation-and-security-workflows.md) | WF-05, WF-16, WF-21 — event/notification/automation layer |
| [committee-logistics-medical-finance-and-ict-workflows.md](committee-logistics-medical-finance-and-ict-workflows.md) | WF-17, WF-18, WF-19, WF-20, plus new WF-24 (Finance), WF-25 (ICT) |
| [document-reporting-audit-and-meet-closure-workflows.md](document-reporting-audit-and-meet-closure-workflows.md) | Document, media/publication, reporting, audit, and WF-23 meet-closure workflows |
| [workflow-testing-simulation-recovery-and-reconciliation.md](workflow-testing-simulation-recovery-and-reconciliation.md) | Workflow testing types, simulation, recovery, 8-category reconciliation |
| [workflow-incident-change-and-release-governance.md](workflow-incident-change-and-release-governance.md) | Workflow/automation incident categories, release gates, change management, rollback |
| [workflow-open-decisions.md](workflow-open-decisions.md) | 32 unresolved workflow decisions (WD-01–WD-32), cross-referenced against Phase 0.1–0.10 open decisions |

## Reading Order

1. [phase-0.11-event-workflow-notification-automation-architecture.md](phase-0.11-event-workflow-notification-automation-architecture.md) — read first; establishes vision and cross-references every supporting document.
2. [workflow-vision-principles-and-governance.md](workflow-vision-principles-and-governance.md), [workflow-classification-and-risk-model.md](workflow-classification-and-risk-model.md) — why workflows are governed this way, and how risk is classified.
3. [business-process-and-state-machine-architecture.md](business-process-and-state-machine-architecture.md), [orchestration-choreography-and-process-manager-architecture.md](orchestration-choreography-and-process-manager-architecture.md), [human-tasks-approvals-reviews-and-certifications.md](human-tasks-approvals-reviews-and-certifications.md) — the process-coordination core.
4. [event-taxonomy-ownership-and-contracts.md](event-taxonomy-ownership-and-contracts.md), [event-metadata-versioning-ordering-and-correlation.md](event-metadata-versioning-ordering-and-correlation.md), [outbox-inbox-idempotency-and-message-reliability.md](outbox-inbox-idempotency-and-message-reliability.md), [queue-routing-priority-retry-and-failure-architecture.md](queue-routing-priority-retry-and-failure-architecture.md), [scheduler-calendar-deadline-and-escalation-architecture.md](scheduler-calendar-deadline-and-escalation-architecture.md) — events, reliability, queues, and scheduling.
5. [notification-and-recipient-resolution-architecture.md](notification-and-recipient-resolution-architecture.md), [email-sms-push-and-in-app-delivery-architecture.md](email-sms-push-and-in-app-delivery-architecture.md), [realtime-broadcast-and-reverb-message-architecture.md](realtime-broadcast-and-reverb-message-architecture.md), [internal-messaging-announcements-and-communication-boundaries.md](internal-messaging-announcements-and-communication-boundaries.md) — communication surfaces.
6. [responsible-automation-and-authority-boundaries.md](responsible-automation-and-authority-boundaries.md), [workflow-identity-authorization-scope-and-separation-of-duties.md](workflow-identity-authorization-scope-and-separation-of-duties.md) — automation authority and identity.
7. [workflow-audit-observability-metrics-and-support.md](workflow-audit-observability-metrics-and-support.md), [workflow-versioning-migration-and-active-instance-compatibility.md](workflow-versioning-migration-and-active-instance-compatibility.md), [offline-mobile-device-public-and-ai-workflow-boundaries.md](offline-mobile-device-public-and-ai-workflow-boundaries.md) — operational and boundary concerns.
8. The six domain-specific workflow documents ([meet-registration-eligibility-and-entry-workflows.md](meet-registration-eligibility-and-entry-workflows.md) through [document-reporting-audit-and-meet-closure-workflows.md](document-reporting-audit-and-meet-closure-workflows.md)) — the 25 named workflows in detail.
9. [workflow-testing-simulation-recovery-and-reconciliation.md](workflow-testing-simulation-recovery-and-reconciliation.md), [workflow-incident-change-and-release-governance.md](workflow-incident-change-and-release-governance.md) — quality and operational governance.
10. [workflow-open-decisions.md](workflow-open-decisions.md) — read last; everything still unresolved.

## Status Legend

| Status | Meaning |
|---|---|
| Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation | Phase 0.11 status: content complete, no formal sign-off yet |
| Validated | Reviewed and confirmed by identified stakeholders (not yet reached for any document) |
| Superseded | Replaced by a later-phase document (not yet applicable) |

## Ownership and Review Expectations

A document owner (workflow governance owner) and reviewer set (Security owner, Privacy owner, Domain reviewers, Sports reviewers, Quality owner, Operations owner, DepEd Leadership) are to be identified — see [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md). Until formal review occurs, this documentation should be treated as a structured, defensible proposal built from the approved Phase 0.1–0.10 foundation, not as an approved specification.

## Relationship to Phase 0.1 Through 0.10

This directory preserves, and never redefines: Phase 0.2's bounded-context ownership and existing domain-events/workflow-and-command catalogs (WF-01–WF-23), Phase 0.3's authorization/assignment/separation-of-duties rules, Phase 0.4's application/event/queue/real-time runtime boundaries, Phase 0.5's history/concurrency/source-of-truth/idempotency rules, Phase 0.6's security/privacy/audit/governance controls, Phase 0.7's test and quality requirements, Phase 0.8's operational and deployment rules, Phase 0.9's UX and accessibility rules, and Phase 0.10's AI advisory-only boundaries. Every document in this directory adds event/workflow/notification/automation governance around those foundations — none of them is altered. **MySQL remains authoritative for workflow and business state; Redis and Reverb messages remain transient**, restated absolutely throughout this package.

## Relationship to Phase 0.12

**Phase 0.12 — Performance, Scalability, Multi-Tenancy, Disaster Recovery, and Enterprise Readiness Architecture is now complete** — see [../09-enterprise/README.md](../09-enterprise/README.md), superseding this section's earlier expectation. It consumed this directory's event/queue/notification/automation architecture to define tenant-aware workflows, events, queues, notifications, and real-time channels — see [../09-enterprise/tenant-aware-runtime-workflow-event-and-ai-boundaries.md](../09-enterprise/tenant-aware-runtime-workflow-event-and-ai-boundaries.md). It also extended [queue-routing-priority-retry-and-failure-architecture.md](queue-routing-priority-retry-and-failure-architecture.md) with queue-scaling and tenant-fairness readiness — see [../09-enterprise/horizon-queue-worker-and-background-processing-scaling.md](../09-enterprise/horizon-queue-worker-and-background-processing-scaling.md) and [../09-enterprise/noisy-neighbor-fair-use-and-resource-governance.md](../09-enterprise/noisy-neighbor-fair-use-and-resource-governance.md). No workflow, event, queue, or automation rule defined in this directory was altered by Phase 0.12's work — queue scaling preserves idempotency and ordering requirements unchanged.

## Relationship to Phase 0.13

**Phase 0.13 — Architecture Validation, Gap Analysis, Technical Debt, and Final Architecture Review is now complete** — see [../10-review/README.md](../10-review/README.md), superseding this section's earlier expectation. It confirmed no workflow in this directory is overly generic — every one of the 25 named workflows traces to a specific bounded context and domain-event set — see [../10-review/workflow-event-notification-and-automation-review.md](../10-review/workflow-event-notification-and-automation-review.md). It identified the outbox-versus-`after_commit` decision ([WD-08](workflow-open-decisions.md#wd-08--outbox-table-versus-after_commit-dispatch)) as this directory's single most consequential open item, unresolved across three phases now. No workflow, event, or automation rule defined in this directory was altered by Phase 0.13's work.

## Relationship to Phase 0.14

The next phase is not yet started, per working rule 8 of Phase 0.13. See [../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md, Section 47](../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md#47-next-phase).

## Update Rules

1. Resolving an item in [workflow-open-decisions.md](workflow-open-decisions.md) (especially **WD-12** eligibility/protest/appeal deadlines or **WD-08** outbox timing) should update its `Status` field and, where it changes a rule, be reflected back into the relevant document.
2. Any new event, workflow, or automation entry added to this package must be cross-checked against [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md) and [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) first — extending the existing catalogs, never silently duplicating or contradicting them.
3. An automation entry moving from "candidate" to "approved for pilot" status updates its row in [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model) — never without passing the full release-gate list in [workflow-incident-change-and-release-governance.md, Section 3](workflow-incident-change-and-release-governance.md#3-workflow-and-automation-release-gates).
4. Keep `.ai/project-context.md`, `.ai/architecture.md`, `.ai/workflow-rules.md`, `.ai/event-rules.md`, `.ai/queue-rules.md`, `.ai/notification-rules.md`, `.ai/messaging-rules.md`, `.ai/automation-rules.md`, and `.ai/scheduler-rules.md` (see [../../.ai/](../../.ai/)) in sync with material changes to this directory, since those files are the primary context AI-assisted development tools use for this project.
