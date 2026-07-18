# PMMS Phase 0.11 — Event-Driven Workflows, Notifications, Messaging, and Responsible Automation Architecture

## 1. Document Control

| Field | Value |
|---|---|
| Document title | PMMS Phase 0.11 — Event-Driven Workflows, Notifications, Messaging, and Responsible Automation Architecture |
| Project | Provincial Meet Management System (PMMS) |
| Phase | Phase 0.11 — Event-Driven Workflows, Notifications, Messaging, and Responsible Automation Architecture |
| Version | 0.11.0 |
| Status | Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation |
| Date | 2026-07-15 |
| Intended audience | Software architects, Laravel developers, React developers, Flutter developers, domain specialists, workflow analysts, QA engineers, security engineers, privacy stakeholders, DevOps engineers, committee heads, tournament managers, technical officials, operations staff, project leadership |
| Document owner | To be identified (workflow governance owner) |
| Review roles | To be identified — Workflow governance owner, Security owner, Privacy owner, Domain reviewers, Sports reviewers, Quality owner, Operations owner, DepEd Leadership |
| Related documents | All 28 supporting documents in this directory (see [README.md](README.md)); [../00-product/](../00-product/) through [../07-ai/](../07-ai/); [../../.ai/decisions/ADR-0011-event-workflow-notification-messaging-and-automation.md](../../.ai/decisions/ADR-0011-event-workflow-notification-messaging-and-automation.md) |

### Change History

| Version | Date | Description |
|---|---|---|
| 0.11.0 | 2026-07-15 | Initial Phase 0.11 draft: workflow vision/principles/governance, workflow classification and risk model, business-process/state-machine architecture, orchestration/choreography/process managers, human tasks/approvals/certifications, event taxonomy/metadata/versioning/ordering/correlation, outbox/inbox/idempotency/message reliability, queue routing/priority/retry/failure architecture, scheduler/calendar/deadline/escalation architecture, notification and recipient-resolution architecture, delivery-channel architecture, real-time/Reverb architecture, internal messaging/announcements, responsible automation and authority boundaries, workflow identity/authorization/SOD, workflow audit/observability/metrics/support, workflow versioning/migration/compatibility, offline/mobile/device/public/AI workflow boundaries, six domain-specific workflow documents (registration/eligibility/entry, tournament/official/scoring, result/protest/medal/publication, accreditation/access-validation/security, committee/logistics/medical/finance/ICT, document/reporting/audit/media/closure), workflow testing/simulation/recovery/reconciliation, workflow incident/change/release governance, and 32 open decisions — built from the approved Phase 0.1–0.10 foundation with no workflow implementation, event class, listener, job, notification, package installation, or automation script created. |

---

## 2. Executive Summary

Phase 0.4 defined PMMS's application, event, and queue boundaries. Phase 0.11 defines how those boundaries actually coordinate a real business process end to end — from a delegation's registration submission through the full meet lifecycle to closure and archival — without hiding business rules inside an unlabeled chain of listeners, weakening auditability, bypassing authorization, or giving an asynchronous process inappropriate authority.

**Why PMMS needs explicit workflow architecture before implementation.** Without one, an implementation team building 25 named workflows (WF-01 through WF-25) independently would invent its own state vocabulary, retry behavior, and notification pattern per workflow — reproducing exactly the fragmentation every prior phase's centralization effort exists to prevent, now at the process-coordination layer specifically.

**Why event-driven design must not hide business rules.** Restated absolutely per working rule 18/19: an event listener is a reaction to a completed fact, never a disguised place to smuggle in business logic that belongs in a Command's Application-layer handler. A workflow's rules must be discoverable by reading its state machine, not by tracing an unbounded event-listener chain.

**Why long-running meet processes require durable state.** Athlete registration, meet preparation, accreditation issuance, protest resolution, and meet closure can each span hours to weeks — their progress must survive a server restart, a failed job, and a queue outage, living in MySQL as the authoritative store, never only in Redis or a queue payload.

**Why queues and Reverb cannot become authoritative.** Restated absolutely per working rules 22–25: MySQL remains authoritative for workflow and business state; Redis and Reverb messages remain transient. A live scoreboard tick is not a substitute for the certified Official Result it eventually reflects.

**Why notifications are not workflow state.** Restated absolutely per working rule 45 and [notification-and-recipient-resolution-architecture.md, Section 14](notification-and-recipient-resolution-architecture.md#14-relationship-to-high-integrity-domains): a delivered notification is a courtesy copy of a decision, never the decision's own durable record, and its delivery failure never blocks or reverses the underlying decision.

**Why retries, idempotency, ordering, recovery, and reconciliation are essential.** PMMS operates across unreliable venue connectivity, shared devices, and queue-based asynchronous processing — every retryable action must be idempotent (working rule 49), every consumer must tolerate delayed or duplicate messages, and every discrepancy must be reconcilable rather than silently drifting.

**Why automated actions require controlled authority.** Restated absolutely per working rules 36–44: automation must never approve eligibility, alter or certify official scores or results, resolve protests, award medals outside approved deterministic workflows, make medical decisions, approve financial actions, grant privileges, or publish protected information — every automation entry operates within an explicitly bounded, human-approved, auditable, and disablable authority.

**Why human review remains necessary for consequential actions.** Every high-integrity workflow's approval, certification, and publication transitions remain human actions, restated unchanged from every prior phase and now given the full event/notification/automation architecture that surrounds — but never replaces — that human judgment.

**Why workflow definitions and event contracts must be versioned.** Restated absolutely per working rules 51–52: a workflow-definition change never silently alters an in-flight instance, and an event-contract change never silently changes an existing event's meaning.

**Why PMMS needs a balance of synchronous transactions, asynchronous processing, and real-time delivery.** Restated from [../01-architecture/laravel-architecture.md, Section 7](../01-architecture/laravel-architecture.md#7-synchronous-versus-asynchronous-decisions): a high-integrity state transition is synchronous and in-transaction; non-critical or expensive work is asynchronous; a live UI update is a best-effort, non-durable broadcast layered on top of both — never a substitute for either.

---

## 3. Workflow Vision

> PMMS workflows will coordinate authorized human tasks, deterministic business rules, durable state transitions, asynchronous processing, notifications, and real-time updates through explicit, observable, recoverable, and versioned processes that preserve human accountability and bounded-context ownership.

Full detail: [workflow-vision-principles-and-governance.md, Section 1](workflow-vision-principles-and-governance.md#1-workflow-vision).

## 4. Workflow Principles

Twenty principles — business state is durable, events describe completed facts, commands express intent, notifications communicate but do not define state, broadcasts are transient, queues transport work but do not own business truth, every workflow has an explicit owner, high-integrity transitions remain controlled, human accountability remains visible, every retryable step is idempotent, failures are recoverable, long-running processes have explicit state, cross-context actions use contracts, workflow definitions are versioned, active instances remain compatible, manual intervention is supported, automation is limited by authority, event payloads use minimum necessary data, workflow actions are auditable, workflow health is observable. Full detail: [workflow-vision-principles-and-governance.md, Section 2](workflow-vision-principles-and-governance.md#2-event-driven-architecture-principles-20).

## 5. Workflow Categories

Seven categories — Transactional, Human Approval, Long-Running Business Process, Asynchronous Processing, Scheduled, Real-Time Update, Responsible Automation — a single business process may combine several. Full detail: [workflow-classification-and-risk-model.md, Section 1](workflow-classification-and-risk-model.md#1-workflow-categories).

## 6. Workflow Risk Classification

Low / Moderate / High risk tiers, mapped directly onto the 13 high-integrity domains from [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md). High-risk workflows require stronger authorization, audit, idempotency, history, testing, manual recovery, and observability. Full detail: [workflow-classification-and-risk-model.md, Sections 2–3](workflow-classification-and-risk-model.md#2-workflow-risk-classification).

## 7. Workflow Governance and Ownership

A workflow-governance model defining definition ownership, change approval, and automation approval, plus the fixed workflow-ownership field structure (Workflow ID, name, owning context, business/technical owner, trigger, participants, authoritative state, preconditions, states, transitions, commands, events, notifications, timers, failure modes, recovery, audit, data classification, offline allowance, automation allowance, version, status) applied consistently across every workflow in this package. **This phase does not redefine the 23 workflows already cataloged in [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-01–WF-23)** — it adds the event, notification, state-machine, and automation layer on top. Full detail: [workflow-vision-principles-and-governance.md, Sections 3–4](workflow-vision-principles-and-governance.md#3-workflow-governance-model).

## 8. Domain Workflow Boundaries

Every workflow's boundary is exactly its owning bounded context's boundary, per [../01-architecture/context-map.md](../01-architecture/context-map.md) — never expanding to make a decision belonging to another context's authoritative data. Full detail: [workflow-classification-and-risk-model.md, Section 5](workflow-classification-and-risk-model.md#5-domain-workflow-boundaries).

## 9. Command Architecture

Commands represent authorized intent to change state — one owning context, explicit actor, explicit scope, validated input, authorization, idempotency where required, transaction boundary, business-rule enforcement, audit outcome, domain events after successful completion. Full detail: [business-process-and-state-machine-architecture.md, Section 1](business-process-and-state-machine-architecture.md#1-command-architecture).

## 10. State-Machine Architecture

Explicit state models for high-integrity workflows — valid states, initial/terminal states, allowed transitions, required permission/assignment/SOD rule, preconditions, side effects, events, notifications, reasons, reopen/cancellation rules, history, invalid-transition behavior. **PMMS deliberately avoids one universal workflow status vocabulary.** Full detail: [business-process-and-state-machine-architecture.md, Section 2](business-process-and-state-machine-architecture.md#2-state-machine-architecture).

## 11. Long-Running Workflow Architecture

Durable process-instance state (process instance, definition version, current state, completed steps, pending tasks/timers, correlation ID, retry/error/hold/cancellation/completion state, manual-intervention status) — **never kept only in Redis or a queue.** Full detail: [business-process-and-state-machine-architecture.md, Section 3](business-process-and-state-machine-architecture.md#3-long-running-workflow-architecture).

## 12. Process Managers

Six named cross-context process managers (Eligibility-to-Accreditation Readiness, Certified-Result-to-Medal-Tally Recalculation, Protest-Filing-to-Result-Hold, Credential-Revocation-to-Access-Denial Propagation, Meet-Closure-to-Assignment-and-Credential-Expiration, Registration-Acceptance-to-Competition-Entry-Readiness) — restated and extended from [../01-architecture/laravel-architecture.md, Section 6](../01-architecture/laravel-architecture.md#6-workflow-orchestration). Full detail: [orchestration-choreography-and-process-manager-architecture.md, Section 1](orchestration-choreography-and-process-manager-architecture.md#1-process-managers).

## 13. Orchestration Versus Choreography

Orchestration preferred where sequence, completion tracking, or high-integrity coordination matters; choreography acceptable for independent reactions (analytics, notifications, projections, search indexing). **Avoid long chains of listeners that create invisible workflow logic.** Full detail: [orchestration-choreography-and-process-manager-architecture.md, Section 2](orchestration-choreography-and-process-manager-architecture.md#2-orchestration-versus-choreography).

## 14. Saga Readiness

Evaluated for multi-context, compensation-requiring workflows; **no saga framework is introduced in this phase** — the six process managers are sufficiently bounded for hand-rolled orchestration. Full detail: [orchestration-choreography-and-process-manager-architecture.md, Section 3](orchestration-choreography-and-process-manager-architecture.md#3-saga-readiness).

## 15. Human Task Architecture

Task type, assignee, assignment basis, role, scope, priority, due date, required evidence, available actions, delegated authority, reassignment, escalation, completion, cancellation, audit. **A human task must not remain valid after the user's assignment or authority expires.** Full detail: [human-tasks-approvals-reviews-and-certifications.md, Section 1](human-tasks-approvals-reviews-and-certifications.md#1-human-task-architecture).

## 16. Approval, Review, and Certification

Nine distinct actions (Review, Recommendation, Validation, Approval, Certification, Publication, Acknowledgment, Acceptance, Override), each with separate permissions and transitions — **PMMS does not use a generic approval workflow for all domains.** Full detail: [human-tasks-approvals-reviews-and-certifications.md, Section 2](human-tasks-approvals-reviews-and-certifications.md#2-approval-review-and-certification-are-distinct).

## 17. Separation of Duties Applied to Workflows

SOD-01 through SOD-11 applied at the workflow-transition level, each named in its applicable state machine's "Separation-of-duties rule" field. Full detail: [human-tasks-approvals-reviews-and-certifications.md, Section 3](human-tasks-approvals-reviews-and-certifications.md#3-separation-of-duties-applied-to-workflows).

## 18. Escalation, Reminder, Deadline, Assignment, and Handoff Workflows

Cross-referenced across [scheduler-calendar-deadline-and-escalation-architecture.md](scheduler-calendar-deadline-and-escalation-architecture.md) (escalation/reminder/deadline) and [human-tasks-approvals-reviews-and-certifications.md, Sections 5, 8](human-tasks-approvals-reviews-and-certifications.md#5-assignment-validity-for-task-ownership) (assignment validity, handoff).

## 19. Domain Event Architecture and Event Taxonomy

Six event types (domain, application, integration, broadcast, audit, notification), plus security events as a specialized audit-event classification — these categories must not be used interchangeably. Full detail: [event-taxonomy-ownership-and-contracts.md, Sections 1–2](event-taxonomy-ownership-and-contracts.md#1-event-taxonomy-six-types).

## 20. Event Naming, Ownership, and Payload Rules

Past-tense, PascalCase naming; one authoritative owning context; minimum-necessary payload — identifiers and safe references, never large sensitive objects. Full detail: [event-taxonomy-ownership-and-contracts.md, Sections 3, 6](event-taxonomy-ownership-and-contracts.md#3-event-payload-rules).

## 21. Event Metadata, Versioning, Ordering, and Correlation

Conceptual metadata (Event ID, type, version, timestamps, owning context, aggregate references, actor, scope, correlation/causation IDs, idempotency key, classification, trace reference); schema versioning discipline (additive vs. breaking changes, deprecation, migration, replay compatibility, contract testing); correlation and causation ID usage; **do not assume global ordering** — consumers must tolerate delayed or duplicate messages. Full detail: [event-metadata-versioning-ordering-and-correlation.md, Sections 1–4](event-metadata-versioning-ordering-and-correlation.md#1-event-metadata-conceptual).

## 22. Event Persistence, Privacy, and Sensitive-Event Restrictions

Event retention remains a placeholder; replay is a privileged, audited operation; every event inherits its underlying data's classification tier. Sensitive-event restrictions apply to medical, eligibility, financial, authentication, and minor-athlete events specifically. Full detail: [event-metadata-versioning-ordering-and-correlation.md, Sections 5–6](event-metadata-versioning-ordering-and-correlation.md#5-event-persistence-retention-and-replay).

## 23. Transactional Outbox and Inbox Evaluation

Outbox evaluated for critical post-commit events (certified results, result holds, credential revocations, publication, medal-tally recalculation, security alerts, integrations); inbox evaluated for durable consumer deduplication. **No outbox or inbox mechanism is implemented in this phase** — the outbox-versus-`after_commit` decision remains explicitly deferred, unchanged from Phase 0.4/0.5. Full detail: [outbox-inbox-idempotency-and-message-reliability.md, Sections 1–2](outbox-inbox-idempotency-and-message-reliability.md#1-transactional-outbox-evaluation).

## 24. Idempotency and Message Deduplication

**Every retryable action must be idempotent** — offline submissions, score submissions, access scans, credential issuance/revocation, report generation, publication, notification dispatch, webhook processing, imports, queue retries, AI-assisted background tasks. Full detail: [outbox-inbox-idempotency-and-message-reliability.md, Sections 3–4](outbox-inbox-idempotency-and-message-reliability.md#3-message-deduplication).

## 25. Queue Architecture, Priority, and Isolation

The 11 existing queue categories (`critical` through `maintenance`) validated unchanged from Phase 0.4; `critical` workers never shared with `imports`/`media`/`exports`. Full detail: [queue-routing-priority-retry-and-failure-architecture.md, Sections 1–2](queue-routing-priority-retry-and-failure-architecture.md#1-queue-architecture-validated-against-phase-04).

## 26. Retry, Backoff, Failed Jobs, and Dead-Letter Readiness

Retryable/non-retryable/poison-message distinctions; **do not retry unauthorized or invalid business transitions blindly**; failed-job record fields; **a failed job must not be replayed without verifying source state and idempotency**. Full detail: [queue-routing-priority-retry-and-failure-architecture.md, Sections 3–4](queue-routing-priority-retry-and-failure-architecture.md#3-retry-and-backoff).

## 27. Scheduler Architecture and Business Calendar

Scheduled tasks must be idempotent, observable, overlap-safe, time-zone-aware, scope-aware, owner-assigned, recoverable; **do not invent official deadline calculations** — every policy-dependent timer (eligibility review, protest filing, appeal window) is explicitly marked for validation, pending OD-07/OD-09. Full detail: [scheduler-calendar-deadline-and-escalation-architecture.md, Sections 1, 5, 8](scheduler-calendar-deadline-and-escalation-architecture.md#1-scheduled-job-architecture).

## 28. Notification Architecture and Recipient Resolution

Lifecycle: `Notification Intent → Recipient Resolution → Classification and Privacy Filter → Template Selection → Channel Selection → Queue → Provider Delivery → Delivery Status → Retry or Failure → Acknowledgment Where Applicable → Audit or Operational Record`. Recipients resolved at dispatch time, not intent time. **User preferences must not suppress mandatory notices.** Full detail: [notification-and-recipient-resolution-architecture.md, Sections 1–6](notification-and-recipient-resolution-architecture.md#1-notification-lifecycle).

## 29. Delivery Channels

In-App (durable, primary), Email (formal, queued), SMS (deferred integration), Push (mobile, once `mobile/` exists), Real-time alert (best-effort, paired with a durable channel for anything consequential). **No channel should be the only durable record of an official decision.** Full detail: [email-sms-push-and-in-app-delivery-architecture.md, Section 1](email-sms-push-and-in-app-delivery-architecture.md#1-delivery-channels).

## 30. Notification Delivery Status, Deduplication, and Escalation

**Notification delivery must not be treated as proof that a user read or understood a decision.** Full detail: [notification-and-recipient-resolution-architecture.md, Sections 9–13](notification-and-recipient-resolution-architecture.md#9-notification-delivery-status).

## 31. Real-Time, Broadcast, and Reverb Message Architecture

Nine-type channel taxonomy, all non-public channels requiring server-side authorization; public channels restricted to BC-29's approved projections; **broadcast events do not replace durable domain events**; provisional-versus-published visual distinction restated absolutely. Full detail: [realtime-broadcast-and-reverb-message-architecture.md, Sections 2, 5–6](realtime-broadcast-and-reverb-message-architecture.md#2-channel-taxonomy-restated-and-extended).

## 32. Reconnection and State Resynchronization

Real-time is explicitly non-durable — "no replay guarantee"; clients recover authoritative state via ordinary queries, never assuming Reverb replays missed messages. Full detail: [realtime-broadcast-and-reverb-message-architecture.md, Sections 8–9](realtime-broadcast-and-reverb-message-architecture.md#8-reconnection-and-missed-message-recovery).

## 33. Internal Messaging and Communication Boundaries

Messaging evaluated, not committed; **messaging must not replace official workflow records**; **avoid building a general-purpose social chat platform unless justified**. Full detail: [internal-messaging-announcements-and-communication-boundaries.md, Sections 1–2](internal-messaging-announcements-and-communication-boundaries.md#1-messaging-architecture--evaluated-not-committed).

## 34. Announcements

Public/organization/meet/committee/delegation/venue/emergency announcements, each requiring audience, start time, expiry, classification, approval, publication state — architecturally distinct from internal messaging. Full detail: [internal-messaging-announcements-and-communication-boundaries.md, Section 5](internal-messaging-announcements-and-communication-boundaries.md#5-announcements).

## 35. Responsible Automation Principles and Categories

Thirteen principles; four categories (Informational, Operational, Workflow, High-Risk Automation) — **high-risk automation requires strong controls and human override.** Full detail: [responsible-automation-and-authority-boundaries.md, Sections 1–2](responsible-automation-and-authority-boundaries.md#1-responsible-automation-principles-13).

## 36. Automation Authority Model

Every automation entry (ID prefix `AU-`) defines owner, purpose, trigger, conditions, allowed/prohibited actions, service identity, permission, scope, affected records, audit, feature flag, retry/failure behavior, manual override, disablement, version — six illustrative candidate entries (AU-01 through AU-06), none enabled. Full detail: [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model).

## 37. AI-Assisted Automation Boundaries

AI may suggest classification, assignment, drafts, escalation recommendations, conflict identification, and schedule proposals; **AI may not automatically approve eligibility, change scores, certify results, resolve protests, award medals, revoke permanent access, approve finances, issue medical decisions, or publish protected data. Any AI-assisted automation must require an authorized deterministic or human confirmation step.** Full detail: [responsible-automation-and-authority-boundaries.md, Section 5](responsible-automation-and-authority-boundaries.md#5-ai-assisted-automation-boundaries).

## 38. Workflow Identity, Authorization, Scope, and Device Trust

Every workflow transition passes the same 16-step authorization decision sequence, regardless of whether it is human-initiated, scheduled, or automated. **Device trust never substitutes for operator identity.** Full detail: [workflow-identity-authorization-scope-and-separation-of-duties.md, Sections 1, 6](workflow-identity-authorization-scope-and-separation-of-duties.md#1-workflow-authorization).

## 39. Workflow Versioning, Migration, and Active-Instance Compatibility

**Active workflows must not be silently changed by new definitions** — explicit migration, state mapping, pending-task review, timer review, and audit-history preservation are all required before any in-flight instance migrates to a new workflow-definition version. Full detail: [workflow-versioning-migration-and-active-instance-compatibility.md, Sections 2–3](workflow-versioning-migration-and-active-instance-compatibility.md#2-workflow-versioning).

## 40. Offline, Mobile, Device, Public, and AI Workflow Boundaries

The eight-item never-final-offline list (eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, high-risk overrides) applies unchanged to every workflow in this package. **Public users cannot trigger internal workflow transitions.** Every AI-assisted workflow step inherits the full Phase 0.10 authority model unchanged. Full detail: [offline-mobile-device-public-and-ai-workflow-boundaries.md](offline-mobile-device-public-and-ai-workflow-boundaries.md).

---

## 41. Domain-Specific Workflow Architecture

Six documents apply this package's event/notification/state-machine/automation architecture to the 25 named workflows (WF-01–WF-25), without redefining their existing Phase 0.2 steps, actors, or preconditions:

- **Meet, Registration, Eligibility, and Entry** (WF-01, WF-03, WF-04, WF-06) — full detail: [meet-registration-eligibility-and-entry-workflows.md](meet-registration-eligibility-and-entry-workflows.md).
- **Tournament, Technical Official, Schedule, and Scoring** (WF-07, WF-08, WF-09, WF-10, WF-11) — full detail: [tournament-official-schedule-and-scoring-workflows.md](tournament-official-schedule-and-scoring-workflows.md).
- **Result, Protest, Medal, and Publication** (WF-12, WF-13, WF-14, WF-15) — full detail: [result-protest-medal-and-publication-workflows.md](result-protest-medal-and-publication-workflows.md).
- **Accreditation, Access Validation, and Security** (WF-05, WF-16, WF-21) — full detail: [accreditation-access-validation-and-security-workflows.md](accreditation-access-validation-and-security-workflows.md).
- **Committee, Logistics, Medical, Finance, and ICT** (WF-17, WF-18, WF-19, WF-20, and newly-introduced WF-24/WF-25) — full detail: [committee-logistics-medical-finance-and-ict-workflows.md](committee-logistics-medical-finance-and-ict-workflows.md).
- **Document, Reporting, Audit, Media, and Meet Closure** (WF-22, WF-23, plus BC-30/BC-32/BC-33) — full detail: [document-reporting-audit-and-meet-closure-workflows.md](document-reporting-audit-and-meet-closure-workflows.md).

## 42. Workflow Testing, Simulation, Recovery, and Reconciliation

State-transition, event-contract, queue/job, notification, automation, SOD/authorization, and high-integrity sports-workflow testing; simulation evaluated as a distinct practice from testing; reconciliation defined for eight named state-pair categories, always surfacing discrepancies for human review, never silently auto-correcting. Full detail: [workflow-testing-simulation-recovery-and-reconciliation.md](workflow-testing-simulation-recovery-and-reconciliation.md).

## 43. Workflow Incident, Change, and Release Governance

Workflow/automation-specific incident categories; immediate feature-flag disablement as the primary automation-incident containment; a full release-gate list mirroring Phase 0.10's identical "no bypass regardless of tier" discipline. Full detail: [workflow-incident-change-and-release-governance.md](workflow-incident-change-and-release-governance.md).

---

## 44. Risks, Assumptions, Tradeoffs, and Alternatives Considered

### Key Risks
- **The eligibility, protest, and appeal deadline/authority cluster remains blocked** ([WD-12](workflow-open-decisions.md#wd-12--policy-dependent-deadline-and-authority-values-eligibility-protest-appeal)) — directly on Phase 0.1's still-unresolved OD-07 and OD-09, the same dependency chain every prior phase has carried forward.
- **The outbox-versus-`after_commit` decision remains unresolved across three phases now** ([WD-08](workflow-open-decisions.md#wd-08--outbox-table-versus-after_commit-dispatch)) — a genuine reliability gap for the most critical cross-context events (`ResultCertified` → Medal Tally, `AccreditationRevoked` → offline sync) until resolved.
- **No workflow, notification, or automation is implemented** — deliberately, but this means the coordination benefits this package's own vision statement describes remain entirely unrealized until a future phase implements at least the first pilot workflow.

### Key Assumptions
- The Phase 0.1–0.10 foundation (bounded contexts, authorization, data model, security controls, quality gates, deployment discipline, design system, AI governance) remains stable enough to anchor a workflow architecture without near-term restructuring.
- The existing WF-01–WF-23 catalog and 30+ cataloged domain events remain the accurate foundation this package layers onto — no workflow or event was redefined, only extended.
- A controlled pilot (per Phase 0.7) occurs before general availability, providing the first genuine cycle-time, failure-rate, and workflow-metric data multiple open decisions in this package depend on.

### Key Tradeoffs
- **Hand-rolled state machines and process managers over a generic workflow engine** (Sections 10, 12) trade near-term implementation convenience for avoiding the premature complexity working rule 16 warns against — assessed as correct given PMMS's ~25 named, well-bounded workflows rather than an open-ended workflow-authoring product.
- **No outbox implementation yet, despite naming critical reliability candidates** (Section 23) trades near-term delivery-reliability guarantees for avoiding infrastructure investment before the pattern is proven necessary — an accepted, explicitly-flagged risk (WD-08), not an oversight.
- **Automation limited to six illustrative, none-enabled candidate entries** (Section 36) trades near-term automation value for ensuring every future automation entry passes the full Automation Authority Model review before any real capability exists to bypass it.

### Alternatives Considered
1. **Begin implementing the first workflow directly from this phase's architecture.** Rejected — directly violates this phase's own working rules; no event class, listener, job, notification, or automation script may be created here.
2. **Redefine WF-01 through WF-23 from scratch with this package's event/notification/automation vocabulary baked in.** Rejected — directly violates working rule 4 ("update equivalent documents instead of creating unnecessary duplicates"); this package extends the existing catalog, it does not replace it.
3. **Adopt a generic workflow-engine package (e.g., a state-machine or BPMN-style library) immediately.** Rejected — directly violates working rule 16; PMMS's workflows are well-bounded enough that hand-rolled orchestration, evaluated per Section 14's saga-readiness criteria, remains the lower-risk starting point.
4. **Allow automation to auto-apply AI recommendations for low-risk-seeming cases (e.g., an obvious duplicate-athlete merge).** Rejected — no exception is carved out from the absolute AI-automation boundary (Section 37); every AI-assisted automation requires a deterministic or human confirmation step, regardless of perceived risk.
5. **Treat notification delivery confirmation as proof a decision was communicated and understood.** Rejected — directly violates working rule 45; delivery status reflects transport success only.

## 45. Recommended Direction

> Build the process-manager, state-machine, event-contract, queue-reliability, notification-lifecycle, and Automation Authority Model architecture defined in this package as the shared foundation every future workflow implementation must pass through — extending, never redefining, the existing WF-01–WF-23 catalog and domain-events catalog — while treating every high-integrity workflow's separation-of-duties rule, idempotency requirement, and human-review gate as a non-negotiable product requirement, and activating automation only entry-by-entry through the full Automation Authority Model review, starting with the lowest-risk Informational and Operational categories.

## 46. Phase 0.11 Deliverables

- 28 supporting documents plus this main document in `docs/08-workflows/` (see [README.md](README.md)).
- Updates to [../01-architecture/README.md](../01-architecture/README.md), [../02-data/README.md](../02-data/README.md), [../03-security/README.md](../03-security/README.md), [../04-quality/README.md](../04-quality/README.md), [../05-devops/README.md](../05-devops/README.md), [../06-design/README.md](../06-design/README.md), and [../07-ai/README.md](../07-ai/README.md) referencing this package.
- Updates to `.ai/project-context.md`, `.ai/current-phase.md`, `.ai/architecture.md`.
- New `.ai/workflow-rules.md`, `.ai/event-rules.md`, `.ai/queue-rules.md`, `.ai/notification-rules.md`, `.ai/messaging-rules.md`, `.ai/automation-rules.md`, `.ai/scheduler-rules.md`.
- New `.ai/decisions/ADR-0011-event-workflow-notification-messaging-and-automation.md`.

## 47. Phase 0.11 Acceptance Criteria

- [x] Workflow vision, event-driven principles, governance model, and ownership structure documented.
- [x] Workflow classification (7 categories) and risk model (Low/Moderate/High, mapped to 13 high-integrity domains) documented.
- [x] Command architecture, state-machine architecture, and long-running workflow architecture documented — no universal status vocabulary.
- [x] Process managers (6 named), orchestration-versus-choreography, and saga readiness documented — no workflow engine or saga framework introduced.
- [x] Human task, approval/review/certification distinction, and separation-of-duties application documented.
- [x] Event taxonomy (6 types), naming, ownership, payload rules, metadata, versioning, ordering, and correlation documented.
- [x] Outbox/inbox evaluation, idempotency, and message-deduplication requirements documented — no mechanism implemented.
- [x] Queue routing, priority, retry, backoff, failed-job, and dead-letter readiness documented — the existing 11-category queue architecture validated, not redefined.
- [x] Scheduler, business-calendar, deadline, and escalation architecture documented — no official deadline invented.
- [x] Notification lifecycle, recipient resolution, delivery channels, and mandatory-notice discipline documented.
- [x] Real-time/Reverb channel taxonomy, authorization, payload rules, and reconnection/recovery documented.
- [x] Internal messaging and announcement boundaries documented — no chat platform built.
- [x] Responsible automation principles, categories, authority model, and AI-assisted automation boundaries documented — no automation script created, six illustrative entries all disabled.
- [x] Workflow identity, authorization, scope, SOD, and device-trust rules documented.
- [x] Workflow audit, observability, metrics, and support-tool requirements documented.
- [x] Workflow versioning, migration, and active-instance compatibility documented.
- [x] Offline, mobile, device, public, and AI workflow boundaries documented — the eight-item never-final-offline list restated absolutely.
- [x] Six domain-specific workflow documents documented, extending WF-01–WF-25 without redefining existing steps.
- [x] Workflow testing, simulation, recovery, and reconciliation documented.
- [x] Workflow incident, change, and release governance documented.
- [x] Open decisions recorded (32 items, cross-referenced against all prior phases).
- [x] AI workspace updated with seven new `.ai/*.md` rule files and ADR-0011.
- [x] No Laravel event, listener, job, notification, mailable, broadcast, command, or scheduled task created.
- [x] No workflow-engine, messaging, queue, or notification package installed; no migration or workflow table created; no queue or Reverb configuration created.
- [x] No production notification template, SMS/email/push-provider integration, or automation script created.
- [x] Documents internally consistent (cross-reference verified — see completion report).

## 48. Preparation Requirements for Phase 0.12

Phase 0.12 (the next phase — scope to be defined by its own prompt) can proceed once it has:

- This package's process-manager, state-machine, event-contract, and Automation Authority Model architecture as the binding reference for any future workflow, event, notification, messaging, or automation implementation decision.
- Every prior phase's `.ai/` rule files plus this phase's new `.ai/workflow-rules.md`, `.ai/event-rules.md`, `.ai/queue-rules.md`, `.ai/notification-rules.md`, `.ai/messaging-rules.md`, `.ai/automation-rules.md`, and `.ai/scheduler-rules.md` as the complete workflow-facing implementation guardrail set.
- Resolution (or an accepted interim plan) for the highest-priority open decisions: **WD-12** (eligibility/protest/appeal deadlines, blocking, pending OD-07/OD-09), **WD-08** (outbox-versus-`after_commit`, unresolved since Phase 0.4), and **WD-06** (event retention, unresolved since Phase 0.5).
- Confirmation of whether Phase 0.12 begins actual implementation (Laravel migrations, events, jobs, React/Flutter components) or continues architecture/documentation work — this package deliberately does not assume which, consistent with the "do not proceed into Phase 0.12" instruction governing this phase.

Phase 0.11 does not itself perform any of Phase 0.12's work — this section exists so Phase 0.12 does not need to rediscover these foundations.

## Next Phase

```text
Phase 0.12 — (to be named by the next phase's own prompt)
```

Phase 0.12 is not started as part of this task, per working rule 58.
