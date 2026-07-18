# PMMS Event-Workflow-Notification-Automation Open Decisions

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [workflow-vision-principles-and-governance.md](workflow-vision-principles-and-governance.md) · [../07-ai/ai-open-decisions.md](../07-ai/ai-open-decisions.md)

This document tracks every unresolved Phase 0.11 decision using Decision ID prefix `WD-` (Workflow Decision), distinct from every prior phase's prefix (`OD-`, `DD-`, `AD-`, `RD-`, `PD-`, `SD-`, `QD-`, `DV-`, `DX-`, `AX-`). Each entry follows the established format: Question / Areas Affected / Why It Matters / Options / Recommended Direction / Evidence Required / Decision Owner / Target Phase / Status.

---

### WD-01 — Numeric Cycle-Time and SLA Targets Per Risk Tier

- **Question:** What specific cycle-time/wait-time numeric targets apply to Low/Moderate/High risk-tier workflows?
- **Areas affected:** [workflow-classification-and-risk-model.md](workflow-classification-and-risk-model.md), [workflow-audit-observability-metrics-and-support.md](workflow-audit-observability-metrics-and-support.md)
- **Why it matters:** Deliberately undefined pending real operational data, consistent with every prior phase's "no invented numbers" discipline.
- **Recommended direction:** Establish empirically once the first implemented workflows produce real cycle-time measurements.
- **Decision owner:** Workflow governance owner + Quality owner
- **Target phase:** Post-first-implementation
- **Status:** Open

### WD-02 — Formal State-Machine Library Adoption

- **Question:** Is a dedicated state-machine package adopted, or is state enforced via hand-rolled enum + transition table?
- **Areas affected:** [business-process-and-state-machine-architecture.md, Section 2](business-process-and-state-machine-architecture.md#2-state-machine-architecture)
- **Recommended direction:** Hand-rolled initially, consistent with working rule 16 ("do not implement a generic workflow engine prematurely").
- **Decision owner:** Technical lead
- **Target phase:** Pre-first-implementation
- **Status:** Open

### WD-03 — Saga Framework Adoption

- **Question:** Does any future cross-context workflow grow complex enough to justify a saga framework?
- **Areas affected:** [orchestration-choreography-and-process-manager-architecture.md, Section 3](orchestration-choreography-and-process-manager-architecture.md#3-saga-readiness)
- **Recommended direction:** No framework now; the six named process managers are hand-orchestrated with explicit compensation.
- **Decision owner:** Technical lead
- **Target phase:** Reactive, as-needed
- **Status:** Open

### WD-04 — Formal Conflict-of-Interest Declaration Workflow

- **Question:** Is a dedicated, system-tracked conflict-of-interest declaration workflow built, beyond the structural SOD matrix?
- **Areas affected:** [human-tasks-approvals-reviews-and-certifications.md, Section 4](human-tasks-approvals-reviews-and-certifications.md#4-conflict-of-interest)
- **Recommended direction:** Evaluate after the first Technical Official/Protest workflow pilot.
- **Decision owner:** Domain reviewer + Workflow governance owner
- **Target phase:** Post-pilot
- **Status:** Open

### WD-05 — Numeric Event-ID Scheme

- **Question:** Is a numeric `EVT-XX` ID scheme ever introduced alongside the existing PascalCase-name-based event catalog?
- **Areas affected:** [event-taxonomy-ownership-and-contracts.md, Section 6](event-taxonomy-ownership-and-contracts.md#6-event-naming-conventions)
- **Recommended direction:** No — the existing name-based catalog is sufficient; a numeric scheme would only aid tooling, not clarity.
- **Decision owner:** Technical lead
- **Target phase:** Not scheduled
- **Status:** Open

### WD-06 — Event Retention Numeric Values

- **Question:** How long is a domain/integration/audit event retained?
- **Areas affected:** [event-metadata-versioning-ordering-and-correlation.md, Section 5](event-metadata-versioning-ordering-and-correlation.md#5-event-persistence-retention-and-replay)
- **Why it matters:** Blocked on the same unresolved retention decisions as every prior phase ([../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md)).
- **Decision owner:** Data governance owner
- **Target phase:** Unresolved since Phase 0.5
- **Status:** Open — mirrors PD-04/SD-23

### WD-07 — Event-Replay Tooling and Authorization Timing

- **Question:** When is event-replay tooling built, and who is authorized to trigger it?
- **Areas affected:** [event-metadata-versioning-ordering-and-correlation.md, Section 5](event-metadata-versioning-ordering-and-correlation.md#5-event-persistence-retention-and-replay)
- **Recommended direction:** Deferred until a real replay need (e.g., a consumer bug requiring reprocessing) is demonstrated.
- **Decision owner:** Technical lead + Security owner
- **Target phase:** Reactive, as-needed
- **Status:** Open

### WD-08 — Outbox Table Versus `after_commit` Dispatch

- **Question:** Is a formal transactional-outbox table implemented, or is Laravel's `after_commit` dispatch sufficient?
- **Areas affected:** [outbox-inbox-idempotency-and-message-reliability.md, Section 1](outbox-inbox-idempotency-and-message-reliability.md#1-transactional-outbox-evaluation)
- **Why it matters:** Carried unchanged from Phase 0.4's RD-01, mirrored by Phase 0.5's PD-21 — still unresolved.
- **Decision owner:** Technical lead
- **Target phase:** Pre-first-critical-event-implementation
- **Status:** Open — mirrors RD-01/PD-21

### WD-09 — Maximum-Retry-Attempt and Backoff-Interval Values

- **Question:** What specific retry-count and backoff-interval values apply per queue category?
- **Areas affected:** [queue-routing-priority-retry-and-failure-architecture.md, Section 3](queue-routing-priority-retry-and-failure-architecture.md#3-retry-and-backoff)
- **Recommended direction:** Establish empirically from real failure-rate data during pilot.
- **Decision owner:** Technical lead + Operations owner
- **Target phase:** Pre-pilot
- **Status:** Open

### WD-10 — Queue-Backlog Alert Threshold Per Category

- **Question:** What backlog depth per queue category triggers an operational alert?
- **Areas affected:** [queue-routing-priority-retry-and-failure-architecture.md, Section 6](queue-routing-priority-retry-and-failure-architecture.md#6-queue-backlog-management)
- **Decision owner:** Operations owner
- **Target phase:** Pre-pilot
- **Status:** Open

### WD-11 — Business-Calendar and Holiday-Reference Source

- **Question:** What is the authoritative source for Philippine public holidays and DepEd-specific non-working days used in deadline computation?
- **Areas affected:** [scheduler-calendar-deadline-and-escalation-architecture.md, Section 5](scheduler-calendar-deadline-and-escalation-architecture.md#5-business-calendar-architecture-readiness)
- **Recommended direction:** A verified, versioned reference source, per the same source-governance discipline as [../07-ai/policy-rulebook-and-source-governance.md](../07-ai/policy-rulebook-and-source-governance.md).
- **Decision owner:** Domain reviewer
- **Target phase:** Pre-deadline-computation-implementation
- **Status:** Open

### WD-12 — Policy-Dependent Deadline and Authority Values (Eligibility, Protest, Appeal)

- **Question:** What are the specific eligibility-review, protest-filing, and appeal-window durations and authorities?
- **Areas affected:** [scheduler-calendar-deadline-and-escalation-architecture.md, Section 8](scheduler-calendar-deadline-and-escalation-architecture.md#8-timers-and-escalations), [meet-registration-eligibility-and-entry-workflows.md](meet-registration-eligibility-and-entry-workflows.md), [result-protest-medal-and-publication-workflows.md](result-protest-medal-and-publication-workflows.md)
- **Why it matters:** Blocking — directly dependent on [OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) and [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).
- **Decision owner:** DepEd Leadership
- **Target phase:** Unresolved since Phase 0.1
- **Status:** Open — blocking, mirrors OD-07/OD-09

### WD-13 — Notification-Preference Granularity

- **Question:** Are notification preferences per-category opt-out, or all-or-nothing?
- **Areas affected:** [notification-and-recipient-resolution-architecture.md, Section 4](notification-and-recipient-resolution-architecture.md#4-notification-preferences)
- **Why it matters:** Carried unchanged from Phase 0.4's identical open question.
- **Decision owner:** Product owner + UX lead
- **Target phase:** Pre-notification-implementation
- **Status:** Open

### WD-14 — Digest/Summary Notification Mode

- **Question:** Is a digest/summary delivery mode built?
- **Areas affected:** [notification-and-recipient-resolution-architecture.md, Section 11](notification-and-recipient-resolution-architecture.md#11-notification-batching-digesting-and-throttling)
- **Why it matters:** "No evidenced need yet," restated unchanged from Phase 0.4.
- **Decision owner:** Product owner
- **Target phase:** Reactive, as-needed
- **Status:** Open

### WD-15 — Mail-Provider Vendor Selection

- **Question:** Which mail-delivery provider is used in production?
- **Areas affected:** [email-sms-push-and-in-app-delivery-architecture.md, Section 3](email-sms-push-and-in-app-delivery-architecture.md#3-email-notifications)
- **Decision owner:** Vendor manager
- **Target phase:** Pre-production
- **Status:** Open

### WD-16 — SMS/Push Provider Selection and Integration Timing

- **Question:** Which SMS and push-notification providers are integrated, and when?
- **Areas affected:** [email-sms-push-and-in-app-delivery-architecture.md, Sections 4–5](email-sms-push-and-in-app-delivery-architecture.md#4-sms-notifications)
- **Why it matters:** Tracked jointly with [OD-25](../00-product/open-decisions.md#od-25--integration-requirements) (Integration Requirements) — no integration is assumed at launch.
- **Decision owner:** Vendor manager + Product owner
- **Target phase:** Post-launch
- **Status:** Open — mirrors OD-25

### WD-17 — Public-Scoreboard Throttling/Aggregation Strategy

- **Question:** What specific throttling/aggregation strategy handles high-volume public scoreboard broadcast traffic?
- **Areas affected:** [realtime-broadcast-and-reverb-message-architecture.md, Section 7](realtime-broadcast-and-reverb-message-architecture.md#7-event-fan-out-and-throttling)
- **Why it matters:** Carried unchanged from Phase 0.4's identical open question — a tuning concern, non-blocking.
- **Decision owner:** Technical lead
- **Target phase:** Pre-pilot
- **Status:** Open

### WD-18 — Presence-Channel Need

- **Question:** Is a Reverb presence channel ever needed?
- **Areas affected:** [realtime-broadcast-and-reverb-message-architecture.md, Section 3](realtime-broadcast-and-reverb-message-architecture.md#3-presence-channel-readiness)
- **Why it matters:** "No evidenced need," carried unchanged from Phase 0.4.
- **Decision owner:** Technical lead
- **Target phase:** Reactive, as-needed
- **Status:** Open

### WD-19 — Internal Messaging Build Decision

- **Question:** Is internal messaging (beyond notifications) built at all in the initial implementation?
- **Areas affected:** [internal-messaging-announcements-and-communication-boundaries.md, Section 1](internal-messaging-announcements-and-communication-boundaries.md#1-messaging-architecture--evaluated-not-committed)
- **Recommended direction:** Defer; evaluate whether notifications plus existing committee-operations tooling suffice before building a messaging surface.
- **Decision owner:** Product owner
- **Target phase:** Post-pilot
- **Status:** Open

### WD-20 — First Automation Pilot Priority

- **Question:** Which automation entries (from the illustrative AU-01–AU-06 catalog) are prioritized for the first pilot?
- **Areas affected:** [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model)
- **Recommended direction:** AU-03 (Reminder Dispatch) and AU-04 (Public Projection Rebuild) first, given their Informational/Operational (not High-Risk) classification.
- **Decision owner:** Workflow governance owner
- **Target phase:** Pre-pilot
- **Status:** Open

### WD-21 — Automation-Conflict Resolution Mechanism

- **Question:** What specific mechanism resolves a race between an automation entry and a concurrent human action on the same record?
- **Areas affected:** [responsible-automation-and-authority-boundaries.md, Section 9](responsible-automation-and-authority-boundaries.md#9-automation-conflict-handling-and-escalation)
- **Recommended direction:** Reuse existing optimistic-locking concurrency control (per [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md)) rather than a new mechanism.
- **Decision owner:** Technical lead
- **Target phase:** Pre-first-automation-implementation
- **Status:** Open

### WD-22 — Automation Service-Identity Credential Rotation

- **Question:** Does an automation service identity require a dedicated rotation cadence distinct from the AI service identity model?
- **Areas affected:** [workflow-identity-authorization-scope-and-separation-of-duties.md, Section 7](workflow-identity-authorization-scope-and-separation-of-duties.md#7-service-identities)
- **Recommended direction:** Reuse the AI service identity rotation model (per [../07-ai/ai-identity-authorization-scope-and-audit.md, Section 2](../07-ai/ai-identity-authorization-scope-and-audit.md#2-ai-service-identity)) unless a specific gap is demonstrated.
- **Decision owner:** Security owner
- **Target phase:** Pre-first-automation-implementation
- **Status:** Open

### WD-23 — Workflow-Metric Numeric Targets

- **Question:** What specific numeric target applies to each workflow metric (completion rate, rejection rate, escalation rate, etc.)?
- **Areas affected:** [workflow-audit-observability-metrics-and-support.md, Section 3](workflow-audit-observability-metrics-and-support.md#3-workflow-metrics)
- **Why it matters:** Deliberately undefined pending real operational data.
- **Decision owner:** Quality owner + Workflow governance owner
- **Target phase:** Post-first-implementation
- **Status:** Open

### WD-24 — Dedicated Workflow-Support Dashboard

- **Question:** Is a dedicated workflow-support dashboard built, or does existing Horizon/admin tooling suffice initially?
- **Areas affected:** [workflow-audit-observability-metrics-and-support.md, Section 4](workflow-audit-observability-metrics-and-support.md#4-workflow-dashboards)
- **Recommended direction:** Existing Horizon dashboard plus targeted admin views initially; a dedicated workflow dashboard evaluated once workflow volume justifies it.
- **Decision owner:** Technical lead
- **Target phase:** Post-pilot
- **Status:** Open

### WD-25 — Dedicated Workflow-Template Mechanism

- **Question:** Is a reusable workflow-template mechanism built, or does each workflow remain hand-specified?
- **Areas affected:** [workflow-versioning-migration-and-active-instance-compatibility.md, Section 1](workflow-versioning-migration-and-active-instance-compatibility.md#1-workflow-definitions-and-templates)
- **Recommended direction:** Hand-specified initially, consistent with working rule 16.
- **Decision owner:** Technical lead
- **Target phase:** Reactive, as-needed
- **Status:** Open

### WD-26 — Public-Facing Intake Workflow Approval

- **Question:** Is any public-facing form/intake workflow approved for the initial implementation?
- **Areas affected:** [offline-mobile-device-public-and-ai-workflow-boundaries.md, Section 4](offline-mobile-device-public-and-ai-workflow-boundaries.md#4-public-workflow-boundaries)
- **Decision owner:** Product owner
- **Target phase:** Pre-pilot
- **Status:** Open

### WD-27 — Mobile Workflow Rollout Sequencing

- **Question:** When are workflows extended to the Flutter mobile app?
- **Areas affected:** [offline-mobile-device-public-and-ai-workflow-boundaries.md, Section 2](offline-mobile-device-public-and-ai-workflow-boundaries.md#2-mobile-workflow-boundaries)
- **Why it matters:** Directly dependent on Phase 0.9's [DX-18](../06-design/design-open-decisions.md#dx-18--mobile-scaffolding-timing-cross-reference) and Phase 0.10's [AX-17](../07-ai/ai-open-decisions.md#ax-17--mobile-ai-rollout-sequencing-cross-reference).
- **Decision owner:** Technical lead
- **Target phase:** Contingent on DX-18
- **Status:** Open — mirrors DX-18/AX-17

### WD-28 — Access-Denial-Pattern Automatic-Escalation Threshold

- **Question:** What specific pattern/threshold of repeated `AccessDenied` events triggers automatic Security-workflow escalation?
- **Areas affected:** [accreditation-access-validation-and-security-workflows.md, Section 3](accreditation-access-validation-and-security-workflows.md#3-security-incident-workflow-wf-21-bc-25)
- **Decision owner:** Security owner
- **Target phase:** Pre-pilot
- **Status:** Open

### WD-29 — Finance/ICT Workflow Catalog Formalization

- **Question:** Are the Finance (WF-24) and ICT (WF-25) workflows introduced in this package formally added to [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) in a future Phase 0.2 revision?
- **Areas affected:** [committee-logistics-medical-finance-and-ict-workflows.md](committee-logistics-medical-finance-and-ict-workflows.md)
- **Recommended direction:** Yes, for catalog consistency — a low-risk, reversible documentation update.
- **Decision owner:** Domain reviewer
- **Target phase:** Next Phase 0.2 revision opportunity
- **Status:** Open

### WD-30 — Meet-Closure Readiness Check Enforcement

- **Question:** Are meet-closure readiness checks (no open protest, unresolved eligibility, pending finance) enforced structurally (blocking) or only advisory-warned initially?
- **Areas affected:** [document-reporting-audit-and-meet-closure-workflows.md, Section 5](document-reporting-audit-and-meet-closure-workflows.md#5-meet-closure-workflow-wf-23-bc-04-coordinating-bc-30)
- **Recommended direction:** Structural blocking for the closure transition itself, given meet closure's High-Risk classification.
- **Decision owner:** Domain reviewer + Workflow governance owner
- **Target phase:** Pre-first-implementation
- **Status:** Open

### WD-31 — Dedicated Workflow-Simulation Tool

- **Question:** Is a dedicated workflow-simulation tool built?
- **Areas affected:** [workflow-testing-simulation-recovery-and-reconciliation.md, Section 2](workflow-testing-simulation-recovery-and-reconciliation.md#2-workflow-simulation)
- **Recommended direction:** Staging-environment rehearsal initially; a dedicated tool evaluated only if demonstrated insufficient.
- **Decision owner:** Quality owner
- **Target phase:** Reactive, as-needed
- **Status:** Open

### WD-32 — Dedicated Workflow-Incident Severity Matrix

- **Question:** Does workflow/automation incident response use a dedicated severity matrix, or the existing general/AI severity models?
- **Areas affected:** [workflow-incident-change-and-release-governance.md, Section 1](workflow-incident-change-and-release-governance.md#1-workflow-and-automation-incident-categories)
- **Why it matters:** Mirrors Phase 0.10's identical, still-open [AX-20](../07-ai/ai-open-decisions.md#ax-20--ai-specific-incident-severity-matrix).
- **Recommended direction:** Reuse the existing general severity model initially, consistent with AX-20's recommended direction.
- **Decision owner:** Security owner + Workflow governance owner
- **Target phase:** Reactive, as-needed
- **Status:** Open — mirrors AX-20

---

## Summary of Blocking / High-Priority Workflow Decisions

| Decision | Why It Blocks |
|---|---|
| **WD-12** | Eligibility, protest, and appeal deadline/authority values are blocked directly on the still-unresolved Phase 0.1 OD-07 and OD-09 |
| **WD-08** | The outbox-versus-`after_commit` decision is carried unresolved from Phase 0.4 (RD-01) through Phase 0.5 (PD-21) into Phase 0.11, and blocks finalizing reliable delivery for every critical cross-context event |
| **WD-06** | Event retention values are blocked on the same unresolved general retention decisions (PD-04/SD-23) carried across every prior phase |
| **WD-27** | Mobile workflow rollout is blocked on Phase 0.9's DX-18 (`mobile/` scaffolding timing) and Phase 0.10's AX-17 |
