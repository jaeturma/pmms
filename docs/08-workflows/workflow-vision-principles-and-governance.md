# PMMS Workflow Vision, Principles, and Governance

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/laravel-architecture.md, Sections 5–7](../01-architecture/laravel-architecture.md#5-domain-event-architecture) · [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) · [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md)

This document defines the workflow vision, event-driven architecture principles, governance model, and ownership discipline for PMMS's business processes, events, notifications, messaging, and automation. **No workflow engine, event class, listener, job, or automation script is created here.**

---

## 1. Workflow Vision

> PMMS workflows will coordinate authorized human tasks, deterministic business rules, durable state transitions, asynchronous processing, notifications, and real-time updates through explicit, observable, recoverable, and versioned processes that preserve human accountability and bounded-context ownership.

This vision extends, and does not replace, [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 13](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#13-synchronous-versus-asynchronous-decisions)'s synchronous/asynchronous discipline and [../01-architecture/laravel-architecture.md, Section 6](../01-architecture/laravel-architecture.md#6-workflow-orchestration)'s named cross-context orchestration concerns — Phase 0.11 gives these an explicit, package-level architecture rather than leaving them scattered across Phase 0.2 and 0.4 documents.

## 2. Event-Driven Architecture Principles (20)

| # | Principle |
|---|---|
| 1 | Business state is durable — MySQL, never Redis, Reverb, or a queue payload, is authoritative for workflow and business state (working rules 22–25) |
| 2 | Events describe completed facts, named in past tense, restated from [../01-architecture/laravel-architecture.md, Section 5](../01-architecture/laravel-architecture.md#5-domain-event-architecture) |
| 3 | Commands express intent — a command is validated and authorized before any event is emitted |
| 4 | Notifications communicate but do not define state — restated absolutely from [../01-architecture/notification-architecture.md, Section 4](../01-architecture/notification-architecture.md#4-relationship-to-high-integrity-domains) |
| 5 | Broadcasts are transient — a Reverb message carries no durability guarantee and is never the sole record of anything |
| 6 | Queues transport work but do not own business truth |
| 7 | Every workflow has an explicit owner — a named bounded context and, where identified, a named business/technical owner |
| 8 | High-integrity transitions remain controlled — synchronous, authorized, auditable, restated from [../01-architecture/laravel-architecture.md, Section 7](../01-architecture/laravel-architecture.md#7-synchronous-versus-asynchronous-decisions) |
| 9 | Human accountability remains visible in every workflow step, automated or manual |
| 10 | Every retryable step is idempotent (working rule 49) |
| 11 | Failures are recoverable — a failed step has a defined recovery path, never a silent dead end |
| 12 | Long-running processes have explicit, durable state — never held only in Redis, a queue, or a Reverb channel |
| 13 | Cross-context actions use explicit contracts — integration events and application-layer orchestration, never direct cross-context database access |
| 14 | Workflow definitions are versioned |
| 15 | Active workflow instances remain compatible across a definition change — never silently migrated |
| 16 | Manual intervention is supported for exceptional cases, always authorized and audited |
| 17 | Automation is limited by explicit authority boundaries (working rules 36–44) |
| 18 | Event payloads use the minimum necessary data — identifiers and safe references, not sensitive objects (working rules 47–48) |
| 19 | Workflow actions are auditable — every transition, automated or human, produces an audit record |
| 20 | Workflow health is observable — cycle time, failure rate, backlog, and stalled instances are visible, not just aggregate uptime |

## 3. Workflow-Governance Model

| Element | Direction |
|---|---|
| Workflow definition ownership | The owning bounded context (per [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md)) owns its workflow's definition, states, and transitions |
| Cross-context workflow ownership | The downstream or coordinating context's Application layer owns orchestration logic, restated from [../01-architecture/laravel-architecture.md, Section 6](../01-architecture/laravel-architecture.md#6-workflow-orchestration) — never the upstream context reaching into the downstream one |
| Workflow-definition change approval | Requires review by the owning context's technical owner and, for high-risk workflows (Section 6, [workflow-classification-and-risk-model.md](workflow-classification-and-risk-model.md)), a security/quality reviewer |
| Notification-content approval | Requires review by the owning context plus a content-design reviewer, per [../06-design/content-design-terminology-help-and-onboarding.md](../06-design/content-design-terminology-help-and-onboarding.md) |
| Automation approval | Requires the full Automation Authority Model review in [responsible-automation-and-authority-boundaries.md, Section 3](responsible-automation-and-authority-boundaries.md#3-automation-authority-model) before any automation entry is enabled |
| Emergency workflow change | Follows [../05-devops/incident-problem-change-and-release-management.md, Section 5](../05-devops/incident-problem-change-and-release-management.md#5-change-management) — no workflow-specific bypass |

## 4. Workflow Ownership

Every workflow document in this package uses this fixed field structure (restated and extended from [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md)'s existing WF-01–WF-23 fields):

Workflow ID (cross-referencing the existing `WF-XX` catalog where applicable) · Name · Owning bounded context · Business owner (to be identified) · Technical owner (to be identified) · Trigger · Participants · Authoritative state · Preconditions · States · Transitions · Commands · Events · Notifications · Timers · Failure modes · Recovery · Audit · Data classification · Offline allowance · Automation allowance · Version · Status.

**Phase 0.11 does not redefine the 23 workflows already cataloged in [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-01–WF-23)** — it adds the event, notification, state-machine, and automation layer on top of that existing catalog, per working rule 4 ("update equivalent documents instead of creating unnecessary duplicates"). Where this package introduces a workflow not already cataloged there (e.g., notification delivery itself), it continues the existing numbering starting at **WF-24**.

## 5. Relationship to Phase 0.2 Bounded-Context Ownership

Restated absolutely per working rule 27: every workflow's owning context is exactly the bounded context that owns the underlying business data, per [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md) — Phase 0.11 never introduces a new authoritative owner for a data concept Phase 0.2 already assigned.

## 6. Relationship to Phase 0.10 AI Governance

Any workflow step involving AI assistance (drafting, classification, recommendation, detection) inherits the full Phase 0.10 authority model unchanged — restated absolutely per working rule 35. See [offline-mobile-device-public-and-ai-workflow-boundaries.md, Section 5](offline-mobile-device-public-and-ai-workflow-boundaries.md#5-ai-workflow-boundaries).

## 7. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md).
