# PMMS Phase 0.4 — Application, Integration, and Runtime Architecture

## 1. Document Control

| Field | Value |
|---|---|
| Document title | PMMS Phase 0.4 — Application, Integration, and Runtime Architecture |
| Project | Provincial Meet Management System (PMMS) |
| Phase | Phase 0.4 — Application, Integration, and Runtime Architecture |
| Version | 0.4.0 |
| Status | Draft Complete — Pending Architecture, Security, and Engineering Validation |
| Date | 2026-07-14 |
| Intended audience | Software architects, Laravel developers, React developers, Flutter developers, DevOps engineers, QA engineers, security engineers, data engineers, technical leads |
| Related documents | [application-architecture.md](application-architecture.md), [laravel-architecture.md](laravel-architecture.md), [react-inertia-architecture.md](react-inertia-architecture.md), [flutter-architecture.md](flutter-architecture.md), [internal-integration-architecture.md](internal-integration-architecture.md), [api-and-client-boundaries.md](api-and-client-boundaries.md), [event-and-queue-architecture.md](event-and-queue-architecture.md), [realtime-architecture.md](realtime-architecture.md), [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md), [notification-architecture.md](notification-architecture.md), [caching-and-session-architecture.md](caching-and-session-architecture.md), [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md), [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md), [runtime-security-architecture.md](runtime-security-architecture.md), [observability-and-error-handling.md](observability-and-error-handling.md), [environment-and-configuration-model.md](environment-and-configuration-model.md), [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md), [testing-architecture.md](testing-architecture.md), [runtime-open-decisions.md](runtime-open-decisions.md), [README.md](README.md), [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md), [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md), [../../.ai/decisions/ADR-0004-application-integration-and-runtime-architecture.md](../../.ai/decisions/ADR-0004-application-integration-and-runtime-architecture.md) |

### Change History

| Version | Date | Description |
|---|---|---|
| 0.4.0 | 2026-07-14 | Initial Phase 0.4 draft: application/Laravel/React/Flutter architecture, internal and external integration patterns, event/queue/real-time/storage/notification/caching runtime, reporting/search/offline-sync runtime, runtime security, observability, environments, resilience/performance, testing architecture, and open decisions — built from the approved Phase 0.1–0.3 foundation. |

---

## 2. Executive Summary

Phase 0.2 decomposed PMMS into 34 bounded contexts and recommended a domain-oriented modular monolith. Phase 0.3 built a hybrid RBAC+ABAC+scope+assignment authorization model on top of those contexts. Both phases deliberately stopped short of saying how any of this actually executes — what Laravel actually does with a bounded context, what happens when a request arrives, what runs synchronously versus in a queue, how a live scoreboard update reaches a browser, where an uploaded eligibility document physically lives. **Phase 0.4 exists to close that gap.**

**Why PMMS requires explicit application and runtime boundaries.** A bounded context documented in Phase 0.2 is only a real boundary if something in the running system actually enforces it — otherwise it is aspirational. Phase 0.4 gives every Phase 0.2 boundary a concrete home (a Laravel module with Domain/Application/Infrastructure/Delivery layers) and gives every Phase 0.3 authorization rule a concrete enforcement point (a specific place in the request/job/broadcast lifecycle where the 16-step decision sequence actually runs).

**Why a conventional controller-model-page structure is insufficient.** As detailed in [application-architecture.md, Section 2](application-architecture.md#2-why-a-conventional-controller-model-page-structure-is-insufficient), an undifferentiated Laravel app collapses exactly the distinctions Phases 0.2 and 0.3 exist to protect — nothing stops a report job from writing to a table it has no business owning, or a controller from checking authorization inconsistently from one endpoint to the next.

**Why the recommended initial architecture is a domain-oriented modular monolith.** This is not a new decision — it is ADR-0002 (Phase 0.2) carried forward and given technical shape: one Laravel codebase, 34 domain modules, internal event-driven integration, extraction-ready boundaries. Phase 0.4 does not revisit whether this is the right direction; it defines how to build it.

**Why public delivery, real-time updates, offline clients, queues, and object storage require controlled runtime boundaries.** Each of these is a place where the platform's institutional-trust guarantees are most easily accidentally violated: a public endpoint that queries operational tables directly, a queued job that assumes stale authority is still valid, an offline client that treats a provisional scan as final, a queue payload that leaks eligibility evidence into Redis. This document names each boundary explicitly and states the rule that prevents the violation.

**Why Laravel, React, Flutter, Redis, Horizon, Reverb, and MinIO must have clear responsibilities.** The approved technology stack (confirmed across Phases 0.1–0.3, and observable in the repository's existing Laravel 13 + Fortify + Inertia + React 19 scaffolding) is capable of building PMMS correctly — but only if each piece has an assigned, non-overlapping job. Without that assignment, "we have Redis" tends to drift into "Redis is also kind of a database," which directly contradicts working rule 27 ("do not treat Redis as a system of record").

**Why this phase must precede database schema and code generation.** A database table designed before its owning module's layering and integration pattern is understood tends to calcify the wrong shape — foreign keys and Eloquent relationships are expensive to unwind once real data depends on them, exactly as noted in [phase-0.2-domain-architecture.md, Section 2](phase-0.2-domain-architecture.md#2-executive-summary) for bounded contexts. Phase 0.4 is the last purely conceptual phase before Phase 0.5 begins translating this into an actual database design.

---

## 3. Architecture Goals

Domain boundary preservation (every Phase 0.2 context maps to exactly one Laravel module) · Maintainability (thin Delivery layer, explicit Application-layer use cases) · Testability (every layer independently testable, per [testing-architecture.md](testing-architecture.md)) · Explicit ownership (every queue, cache, and read model has a named owning context) · High-integrity workflow protection (synchronous, transactional, auditable execution for the actions named throughout [high-integrity-access-controls.md](high-integrity-access-controls.md)) · Scalable public delivery (isolated from administrative/scoring load) · Real-time capabilities (Reverb, without becoming a durability substitute) · Offline support (Flutter + sync runtime, without offline clients gaining final authority) · Secure document handling (MinIO with authorization-gated access) · Reliable background processing (Horizon-supervised queues with category-appropriate reliability guarantees) · Observability (health checks, logging, error handling proportionate to risk) · Commercial product readiness (environment model, configuration discipline) · Deployment flexibility (logical units separable from physical topology) · Extraction-ready module boundaries (inherited from ADR-0002) · Controlled technical complexity (CQRS-as-discipline not CQRS-as-infrastructure; queues where warranted, not everywhere; search infrastructure only when justified).

---

## 4. Recommended Architecture Style

> Domain-oriented modular monolith with layered modules, explicit application boundaries, internal event-driven integration, dedicated public read models, queue-backed asynchronous processing, and independently scalable runtime components where needed.

### Why This, Rather Than the Alternatives

| Alternative | Why Rejected as the Primary Style |
|---|---|
| Traditional unstructured Laravel CRUD | Collapses domain boundaries; makes high-integrity separation-of-duties unenforceable in code (per [application-architecture.md, Section 2](application-architecture.md#2-why-a-conventional-controller-model-page-structure-is-insufficient)) |
| Microservices from the beginning | Premature distributed-systems complexity given current team size and still-resolving domain questions (unchanged from ADR-0002's reasoning) |
| One module per committee | Violates the Phase 0.2 finding that committee membership is not the same as bounded-context ownership — modules map to bounded contexts, not org charts (per [bounded-context-catalog.md](bounded-context-catalog.md)) |
| One service per bounded context | Same distributed-complexity cost as microservices, applied even more granularly (34 services) — no evidenced need |
| Shared-services-heavy architecture | Recreates the "generic global service class" anti-pattern named in Section 49 below |
| Frontend-driven business logic | Violates the server-authoritative principle threaded through Phase 0.3's entire authorization model — a frontend cannot enforce separation of duties |

---

## 5. Application Landscape

Full surface definitions are in [application-architecture.md, Section 1](application-architecture.md#1-application-surfaces): Core Laravel Application, React/Inertia Administrative Application, Public Web Application, Flutter Mobile Application, Background Processing Runtime (Horizon), Real-Time Runtime (Reverb), Object Storage Runtime (MinIO), Redis Runtime, Reporting and Search Runtime.

---

## 6. Laravel Application Layering

Domain, Application, Infrastructure, and Delivery layers, with their responsibilities and dependency rules, are fully defined in [laravel-architecture.md, Section 1](laravel-architecture.md#1-laravel-application-layering). Dependency direction is always Infrastructure → Application → Domain, with Delivery calling into Application only.

## 7. Laravel Module Structure

The full 34-module conceptual directory structure (mapping every bounded context to a `app/Domains/<Context>/` module with `Domain/Application/Infrastructure/Delivery` sub-layers) is in [laravel-architecture.md, Section 2](laravel-architecture.md#2-laravel-module-structure). **This directory structure is not created during Phase 0.4.** Not every context requires an independent package at equal depth — small Supporting/Generic contexts may use a lighter internal structure proportionate to their complexity.

## 8. Modular Monolith Rules

The full 14-rule list (one authoritative module per concept, no cross-module table mutation, directional/acyclic dependencies, minimal Shared Kernel, etc.) is in [laravel-architecture.md, Section 8](laravel-architecture.md#8-modular-monolith-rules-summary).

## 9. Command and Query Architecture

Commands (state-changing intentions — the full [workflow-and-command-catalog.md](workflow-and-command-catalog.md) command list carried forward unchanged) and Queries (read-only, never mutating) are defined in [laravel-architecture.md, Section 3](laravel-architecture.md#3-command-and-query-architecture). **CQRS is used as a design discipline, not a mandate for separate infrastructure everywhere** — full CQRS/event sourcing is explicitly rejected as a broad default (Section 50).

## 10. Transaction Boundaries

A transaction normally remains within one bounded context; cross-context workflows use orchestration or events, never distributed transactions; database transactions never remain open during a remote network call. Full principles: [laravel-architecture.md, Section 4](laravel-architecture.md#4-transaction-boundaries).

## 11. Domain Event Architecture

Domain, application, integration, real-time broadcast, audit, and notification events are six distinct concepts, fully distinguished in [laravel-architecture.md, Section 5](laravel-architecture.md#5-domain-event-architecture). A single domain event (e.g., `ResultCertified`) typically produces several of these simultaneously without itself being any of them.

## 12. Workflow Orchestration

Six named cross-context workflows (eligibility→accreditation, certified-result→medal-tally, protest-filing→result-hold, credential-revocation→access-denial, meet-closure→archival, registration-acceptance→entry-readiness) requiring explicit Application-layer orchestration, not hidden event-listener chains, are detailed in [laravel-architecture.md, Section 6](laravel-architecture.md#6-workflow-orchestration).

## 13. Synchronous Versus Asynchronous Decisions

High-integrity state transitions, authorization decisions, and critical conflict detection are synchronous. Notifications, report generation, media processing, search indexing, analytics, exports, and bulk imports are asynchronous. Full lists and the eventual-consistency-must-be-surfaced rule: [laravel-architecture.md, Section 7](laravel-architecture.md#7-synchronous-versus-asynchronous-decisions).

## 14. Queue Architecture

11 conceptual queue categories (`critical`, `default`, `notifications`, `documents`, `imports`, `exports`, `media`, `projections`, `analytics`, `integrations`, `maintenance`), each with purpose/priority/retry/timeout/idempotency/failure-handling/sensitive-data/dead-letter/ownership/monitoring, are defined in [event-and-queue-architecture.md, Section 1](event-and-queue-architecture.md#1-queue-categories). Category names require repository validation before becoming literal queue names (see [runtime-open-decisions.md, RD-02](runtime-open-decisions.md#rd-02--queue-category-naming-finalization)).

## 15. Laravel Horizon Architecture

Horizon is the operational control plane, not a business-logic component — environment-specific worker groups, priority-based processing, process isolation for `critical`, and deployment-restart behavior are defined in [event-and-queue-architecture.md, Section 3](event-and-queue-architecture.md#3-laravel-horizon-architecture). **No Horizon configuration is created.**

## 16. Redis Usage Boundaries

**Approved:** queue transport, cache, session storage (where approved per [caching-and-session-architecture.md](caching-and-session-architecture.md)), rate limiting, distributed locks, temporary idempotency keys, real-time runtime support, short-lived synchronization state, temporary workflow coordination.

**Prohibited:** authoritative scoring records, official results, eligibility decisions, medal awards, medical records, financial records, permanent audit records, permanent assignment records. **Redis is never a system of record** (working rule 27) — every one of these prohibited categories has its authoritative home in a specific bounded-context module's Infrastructure layer (per [data-ownership-map.md](data-ownership-map.md)), never Redis.

Cache invalidation responsibility and Redis-unavailable fallback behavior: [caching-and-session-architecture.md, Section 1](caching-and-session-architecture.md#1-caching-architecture).

## 17. Real-Time Architecture

Use cases (live scoreboards, result boards, tournament progress, medal tally, schedule changes, operational/committee/security/ICT alerts, public announcements), channel types (public, private user, meet, committee, delegation, sport, venue, tournament, administrative), and the full rule set (broadcasts never carry protected data, server-side channel authorization, transience, reconciliation-through-normal-queries) are in [realtime-architecture.md](realtime-architecture.md).

## 18. Public Portal Runtime Boundary

- The public portal reads **only** approved public projections (BC-29) — never operational tables directly.
- Public traffic must not directly stress high-integrity write workflows — architecturally isolated per [resilience-performance-and-scaling.md, Section 1](resilience-performance-and-scaling.md#1-scaling-boundaries).
- Public queries may use cache, per [caching-and-session-architecture.md](caching-and-session-architecture.md).
- Public result data is versioned and traceable — a published result carries the version it reflects (per [reporting-search-and-read-model-runtime.md, Section 4](reporting-search-and-read-model-runtime.md#4-generated-documents)).
- Unpublished or held results must never leak — enforced at the BC-29 projection boundary itself (per [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary)), not by a downstream filter that could be bypassed.
- Public athlete profiles are privacy-filtered per [phase-0.3-access-and-assignment-architecture.md, Section 22](phase-0.3-access-and-assignment-architecture.md#22-public-guest-and-self-service-access).
- The public portal is rate-limited (per [runtime-security-architecture.md, Section 3](runtime-security-architecture.md#3-runtime-security-controls)) and should remain available during administrative load spikes (and vice versa — the isolation is bidirectional).
- Public pages degrade gracefully if real-time services fail (per [realtime-architecture.md, Section 5](realtime-architecture.md#5-fallback-behavior)).
- Static/cached content may be used for announcements and venue information.

**Whether the public portal should later become an independently deployable runtime is evaluated, not decided** — see [runtime-open-decisions.md, RD-19](runtime-open-decisions.md#rd-19--public-traffic-deployment-separation-timing).

## 19. React and Inertia Architecture

Full directory structure, layer responsibilities (Inertia Pages, Feature Modules, Shared Design System, Client Services), InertiaJS boundary (when to use Inertia vs. dedicated APIs), and the server-remains-authoritative rule set are in [react-inertia-architecture.md](react-inertia-architecture.md).

## 20. InertiaJS Boundary

**Use Inertia for:** administrative portal, committee operations, registration workflows, tournament management, scoring interfaces (where reliable connectivity exists), operational dashboards, configuration and reporting.

**Use dedicated API endpoints for:** Flutter mobile clients, offline synchronization, scanner devices, public integrations, webhooks, external systems, high-frequency live scoring devices, kiosks, dedicated scoreboard applications.

**Avoid building a duplicate API for every Inertia page without a real client need** — see [react-inertia-architecture.md, Section 4](react-inertia-architecture.md#4-inertiajs-boundary) and [api-and-client-boundaries.md, Section 4](api-and-client-boundaries.md#4-avoiding-duplicate-apis).

## 21. Flutter Architecture

Full directory structure, layers (Presentation, Application, Domain, Infrastructure), mobile-relevant bounded-context mapping, and rules (never reimplement business rules independently, server remains authoritative, both device and user identity considered) are in [flutter-architecture.md](flutter-architecture.md).

## 22. API and Client Boundaries

Six API categories (Internal Mobile, Device, Public, Administrative Integration, Webhook, Synchronization) and the shared principles every one follows (versioned, authenticated-where-required, scoped, idempotent, rate-limited, no internal model leakage) are in [api-and-client-boundaries.md](api-and-client-boundaries.md).

## 23. Internal Integration Architecture

Permitted patterns (direct application-service call, synchronous contract call, domain event, asynchronous integration event, read-model consumption, anti-corruption adapter, controlled file import, approved webhooks) and discouraged patterns (cross-context ORM relationships, shared mutable tables, direct cross-context SQL, hidden event-listener workflow chains, generic global service classes, backend-calling-frontend, queues as a substitute for domain design) are in [internal-integration-architecture.md](internal-integration-architecture.md), including a decision flowchart for choosing a pattern.

## 24. Object Storage and Document Runtime

MinIO's responsibilities (eligibility documents, accreditation assets, profile images, committee/medical/financial documents, official result documents, generated reports, media, import/export files, archives) and the 13-step document flow (upload authorization → metadata creation → staging → validation → scanning → classification → permanent placement → versioning → access policy → retention → audit → download authorization → expiry/archival) are in [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md).

## 25. Notification Architecture

Notification intent originates from application workflows, is separated from delivery transport, and respects role/assignment/scope-based recipient resolution. Full principles: [notification-architecture.md](notification-architecture.md).

## 26. Caching Architecture

Candidate cache targets and the full rule set (never cache sensitive records without classification review, every cache has an owner and invalidation strategy, cache failure never corrupts authoritative workflows, high-integrity actions always validate against authoritative state) are in [caching-and-session-architecture.md, Section 1](caching-and-session-architecture.md#1-caching-architecture).

## 27. Session Architecture

Candidate session storage options, requirements (secure cookies, CSRF, rotation, invalidation-on-privilege-change), and the clear separation of web session / mobile token / API client / QR credential are in [caching-and-session-architecture.md, Section 2](caching-and-session-architecture.md#2-session-architecture).

## 28. Authentication Integration Boundary

How future authentication integrates with User Account context, Person identity, Organization membership, role/assignment activation, device trust, MFA readiness, session lifecycle, mobile tokens, service accounts, account recovery, risk-based controls, and audit events is defined in [runtime-security-architecture.md, Section 1](runtime-security-architecture.md#1-authentication-integration-boundary). **Authentication proves identity; authorization determines allowed action** — these remain distinct throughout.

## 29. Authorization Enforcement Points

The full 16-step decision sequence from [authorization-decision-model.md](authorization-decision-model.md) is enforced at 14 named entry points (HTTP, Application use cases, domain-sensitive transitions, query filtering, file downloads, broadcast channels, queue execution, scheduled jobs, API endpoints, synchronization endpoints, device actions, report generation, export creation, public publication) — full list and rules in [runtime-security-architecture.md, Section 2](runtime-security-architecture.md#2-authorization-enforcement-points). **Frontend checks never replace backend authorization**, restated as the single most load-bearing rule in this phase.

## 30. Reporting and Read-Model Runtime

Context-owned operational read models, cross-context executive projections, public projections, historical snapshots, exports, search indexes, and analytics datasets each have a defined update-timing mechanism (synchronous, asynchronous, scheduled, or on-demand) in [reporting-search-and-read-model-runtime.md, Sections 1–2](reporting-search-and-read-model-runtime.md#1-read-model-categories-at-runtime). **Read-model failures never corrupt source transactions.**

## 31. Search Architecture

A staged approach — MySQL-backed search initially, optimized indexes/dedicated read models as measured need emerges, an external search engine only when justified — is defined in [reporting-search-and-read-model-runtime.md, Section 3](reporting-search-and-read-model-runtime.md#3-search-architecture). **No search infrastructure is built prematurely.** Search results always respect authorization and data classification.

## 32. Offline Synchronization Runtime

The sync client, local mobile store, device registration, authorization snapshot, reference-data download, pending action queue, change upload, server validation, conflict detection/resolution, sync acknowledgment, retry, audit consolidation, device revocation, and data cleanup — plus the shared Local Draft → Pending Sync → Uploaded → Accepted/Rejected/Conflict → Superseded record-state machine — are fully defined in [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md), building directly on [offline-authorization-model.md](offline-authorization-model.md) (Phase 0.3).

## 33. Device Integration Runtime

PMMS supports the following device categories, per [device-and-service-identity-model.md, Section 1](device-and-service-identity-model.md#1-device-identity-categories) (Phase 0.3): QR scanner stations, accreditation printers, score entry devices, scoreboard displays, kiosks, gate-validation devices, venue tablets, offline local servers, and (where a future integration is approved) timing/scoring devices.

For each device, the runtime defines: device registration (an explicit enrollment step, never implicit-on-first-connect for anything beyond a Low-risk kiosk), credential issuance (a Device Identity credential, distinct from any operator's User Account credential per [identity-model.md](identity-model.md)), meet and venue assignment (per [assignment-model.md](assignment-model.md)'s Device Assignment type), software version and capability profile (so the server can reject an outdated/incompatible client rather than accept malformed sync data), heartbeat and last-seen tracking (feeding [observability-and-error-handling.md, Section 4](observability-and-error-handling.md#4-health-check-architecture)'s "device connectivity" signal), trust status (Trusted/Untrusted/Under Review/Revoked), revocation (per [access-review-and-revocation.md](access-review-and-revocation.md)), offline behavior (per Section 32 above), firmware/app-update considerations (a device's capability profile determines whether it can safely operate against the current server contract version), audit records (every device action attributed to its specific Device Identity), and failure recovery (a device that fails to check in within an expected window is flagged, not silently ignored).

**No vendor protocol is invented** — this runtime defines PMMS's own device-registration and communication contract; integration with any specific third-party timing/scoring hardware protocol is deferred until such a device is actually approved for integration (per [internal-integration-architecture.md, Section 4](internal-integration-architecture.md#4-external-integration-status)).

## 34. AI Service Integration Boundary

### Allowed Integration Modes
Human-requested assistant (a user explicitly invokes AI help within a workflow), background recommendation service (e.g., duplicate-detection running as a scheduled/queued job), document-review assistant, duplicate-detection assistant, schedule-conflict assistant, narrative report generator, search and knowledge assistant, anomaly detection.

### Requirements
- Uses an approved Service Identity (per [device-and-service-identity-model.md, Section 7](device-and-service-identity-model.md#7-ai-service-identity-cross-reference)), never a standing independent credential.
- Respects the requesting user's authorization — the AI feature's effective data access is the *intersection* of its own scope and the requesting user's authority, never a union (per [phase-0.3-access-and-assignment-architecture.md, Section 29](phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary)).
- Uses minimum necessary data — a duplicate-detection assistant receives the specific fields needed for matching, not a full participant record.
- Redacts sensitive information where possible before any data leaves the application boundary.
- Logs AI-assisted actions and data access at Elevated/Critical audit level where the underlying domain warrants it (per [permission-catalog.md](permission-catalog.md)).
- Preserves source references — an AI-generated summary/recommendation cites what it was generated from.
- Labels generated output as AI-generated, never presented as if human-authored without attribution.
- Requires human confirmation for any consequential action — an AI recommendation is never auto-applied to authoritative state.
- Prevents direct database writes — an AI service has no Infrastructure-layer write credential of its own; any change it proposes flows through the normal Command/Application-layer path, subject to the same authorization and audit rules as if a human had typed it.
- Routes approved changes through normal Application-layer use cases — never a side-channel write path.
- Supports service disablement — AI-assisted features can be turned off platform-wide or per-context without disrupting the underlying manual workflow.
- Records model and prompt version where material to reproducibility/audit of a consequential recommendation.
- Protects external data transfer — any AI service that is itself external (a third-party AI API) is subject to the same data-classification-aware transfer rules as any other external integration (per [internal-integration-architecture.md](internal-integration-architecture.md)), blocked pending [Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions) for any Restricted/Highly Restricted data category.

### Prohibited (Restated, Absolute)
Autonomous eligibility approval, autonomous result certification, autonomous score changes, autonomous protest resolution, autonomous medal awards, autonomous medical decisions, unlogged background mutation, unrestricted database credentials for any AI service. This list is identical to — and does not weaken — [phase-0.3-access-and-assignment-architecture.md, Section 29](phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary) and [phase-0.2-domain-architecture.md, Section 19](phase-0.2-domain-architecture.md#19-ai-assistance-by-context).

## 35. External Integration Architecture

**No external integration is currently approved** (per [internal-integration-architecture.md, Section 4](internal-integration-architecture.md#4-external-integration-status) and [Phase 0.1 product-scope.md, Section 8](../00-product/product-scope.md#8-deferred-integrations)). Potential future integrations — DepEd organization reference data, school directory, learner/athlete source systems, email/SMS/push providers, payment/financial references, government identity/SSO, timing/scoring systems, livestream/media platforms, mapping/weather services, analytics platforms — would each use an anti-corruption-layer adapter (per [internal-integration-architecture.md](internal-integration-architecture.md)) and require documented ownership, contract, authentication, data classification, retry/timeout/rate-limit behavior, idempotency, failure mode, reconciliation, audit, fallback, retention, and vendor-lock-in-risk assessment **at the time each is actually approved** — none of this is invented speculatively here, and **no vendor is selected**.

## 36. Error Handling Architecture

13 error categories (validation, authorization, authentication, conflict, state-transition, not-found, rate-limit, integration, queue, file-processing, synchronization, infrastructure, unexpected) and the full requirement set (stable user-facing messages, correlation IDs, no sensitive-data leakage, high-integrity failures never leaving ambiguous state) are in [observability-and-error-handling.md, Section 1](observability-and-error-handling.md#1-error-handling-architecture).

## 37. Logging Architecture

10 log categories and the rule set (audit events distinct from operational logs, medical/eligibility/finance/auth-secret content never logged, correlation IDs flow across requests/jobs/integrations, privileged log access, logs are never a shadow database) are in [observability-and-error-handling.md, Section 2](observability-and-error-handling.md#2-logging-architecture).

## 38. Observability Architecture

Health checks, queue latency, failed jobs, dependency health (database/Redis/MinIO/Reverb), API latency, error rate, public-portal performance, synchronization success, device connectivity, cache hit rate, storage capacity, backup status, and security events are the conceptual signals — dashboards/alerts are organized by owning role/committee. **No monitoring vendor is selected.** Full detail: [observability-and-error-handling.md, Section 3](observability-and-error-handling.md#3-observability-architecture).

## 39. Health Check Architecture

12 candidate checks, separated into Liveness / Readiness / Dependency health / Business health / Degraded mode, are in [observability-and-error-handling.md, Section 4](observability-and-error-handling.md#4-health-check-architecture) — this separation matters operationally (a Liveness failure means restart; a Readiness failure means stop routing traffic; a Dependency-health failure may mean degrade gracefully).

## 40. Environment Model

Seven environments (Local, Development, Test, Staging, Pilot, Production, Disaster Recovery — not all required immediately) with purpose/data-type/access/integration-behavior/logging/queue/Reverb/MinIO/secrets/deployment/reset/backup/exposure defined per environment are in [environment-and-configuration-model.md, Section 1](environment-and-configuration-model.md#1-environment-model). **Production data is never casually copied into lower environments.**

## 41. Configuration and Secrets

17 configuration categories and the rule set (secrets never committed, no shared production credentials, rotation must be possible, safe defaults, no secrets in client bundles, mobile apps carry no privileged server secrets, auditable rule-affecting configuration changes) are in [environment-and-configuration-model.md, Sections 2–3](environment-and-configuration-model.md#2-configuration-categories).

## 42. Runtime Deployment Units

12 conceptual deployable units (Laravel web application, queue workers, scheduler, Reverb server, public web runtime, MinIO, Redis, MySQL, Flutter mobile app, optional local venue sync service, optional reporting worker, optional media-processing worker) are in [environment-and-configuration-model.md, Section 4](environment-and-configuration-model.md#4-runtime-deployment-units). **These may deploy together or separately depending on environment/scale — no physical topology is committed to.**

## 43. Scaling Boundaries

13 independently scalable workloads and the rule set (scaling follows measured bottlenecks; public traffic never starves official scoring/results; queue pools isolate long-running jobs) are in [resilience-performance-and-scaling.md, Section 1](resilience-performance-and-scaling.md#1-scaling-boundaries).

## 44. Performance Architecture

12 performance-sensitive workflows and the principle list (pagination, lazy loading, indexed queries, read models, caching, batching, chunking, background processing, payload minimization, avoiding N+1) are in [resilience-performance-and-scaling.md, Section 2](resilience-performance-and-scaling.md#2-performance-architecture). **No numerical performance targets are invented** — targets remain deferred to pilot-meet baseline data per [Phase 0.1 success-framework.md](../00-product/success-framework.md).

## 45. Availability and Resilience

Graceful degradation, queue retry, integration timeout, circuit-breaker (where justified), cached public fallback, offline mobile operation, Reverb-to-polling fallback, failed-job recovery, idempotent replay, reconciliation jobs, and manual operational fallback are in [resilience-performance-and-scaling.md, Section 3](resilience-performance-and-scaling.md#3-availability-and-resilience). **High-integrity transactions fail safely rather than partially commit** — no exceptions.

## 46. Backup and Recovery Considerations

Conceptual backup coverage (MySQL, MinIO, configuration, encryption keys, audit records, generated reports, device credentials) and requirements (RPO/RTO placeholders, backup verification, restore drills, offsite backup, access control, retention, meet-specific archival, legal/policy validation) are in [resilience-performance-and-scaling.md, Section 4](resilience-performance-and-scaling.md#4-backup-and-recovery-considerations). **No numerical RPO/RTO or retention duration is invented.**

## 47. Runtime Security Architecture

TLS, secure headers, CSRF, CORS, rate limiting, request/upload validation, signed URLs, queue payload protection, broadcast authorization, secret management, session/API-token/device-credential/service-account security, webhooks, audit logs, encryption, database/Redis/MinIO access control, internal network isolation, administrative-endpoint protection, dependency management, error disclosure, and AI-integration boundaries are fully tabulated in [runtime-security-architecture.md, Section 3](runtime-security-architecture.md#3-runtime-security-controls).

## 48. Testing Architecture

Eight test layers (Unit, Application, Feature, Integration, Contract, Frontend, Flutter, Non-Functional) with PestPHP as the confirmed backend testing tool, plus a traceability table linking every layer back to a specific architectural document, are in [testing-architecture.md](testing-architecture.md). **No test is written in this phase.**

## 49. Architectural Risks and Anti-Patterns

| Anti-Pattern | Status in This Documentation Package |
|---|---|
| Fat controllers | **Prevented by design** — Delivery layer is explicitly thin (Section 6) |
| Global service classes | **Prevented** — Shared Kernel discipline minimal by rule (Section 8) |
| Shared mutable models across contexts | **Prevented** — one authoritative module per concept (Section 8) |
| Direct cross-context ORM writes | **Discouraged explicitly** — [internal-integration-architecture.md, Section 2](internal-integration-architecture.md#2-discouraged-patterns) |
| Queue-driven critical consistency | **Prevented** — high-integrity transitions are synchronous by rule (Section 13) |
| Event-listener workflow chains | **Prevented** — explicit orchestration required for multi-step workflows (Section 12) |
| Redis as source of truth | **Explicitly prohibited** (Section 16) |
| Public portal reading operational tables | **Structurally prevented** — public portal reads only BC-29 projections (Section 18) |
| MinIO access without metadata authorization | **Prevented** — database metadata remains authoritative for access (Section 24) |
| Sensitive broadcast payloads | **Prevented** — broadcasts carry minimum necessary, never protected data (Section 17) |
| Mobile business-rule duplication | **Prevented** — Flutter defers to server-side rules (Section 21) |
| Offline clients certifying final results | **Structurally prevented** — never-final-offline list (Section 32) |
| AI direct database access | **Prevented** — no standing AI write credential (Section 34) |
| Shared device accounts | **Prevented** — device identity ≠ operator identity (Section 33) |
| Unbounded file uploads | **Prevented** — request size limits, chunked processing (Sections 14, 24) |
| N+1 queries | **Named performance principle to avoid** (Section 44) |
| Report generation in HTTP requests | **Prevented** — reports/exports are queued (`exports` category, Section 14) |
| Unscoped API tokens | **Prevented** — every API category is scoped (Section 22) |
| Environment secrets in source control | **Prevented** — explicit rule (Section 41) |
| One worker pool for all queues | **Prevented** — `critical` isolated from bulk categories (Section 15) |
| No failed-job recovery | **Prevented** — every queue category has defined failure handling (Section 14) |
| No correlation IDs | **Prevented** — required across requests/jobs/integrations (Section 37) |
| Logs containing sensitive data | **Explicitly prohibited** (Section 37) |
| Microservices introduced before operational need | **Rejected as the initial style** (Section 4) |
| Frontend-only authorization | **Explicitly prohibited, restated repeatedly** (Sections 19, 21, 29) |
| Database triggers hiding business logic | **Prevented by layering discipline** — business logic lives in the Domain layer only (Section 6) |
| Generic status fields spanning unrelated workflows | **Prevented** — inherited from [phase-0.2-domain-architecture.md, Section 20](phase-0.2-domain-architecture.md#20-domain-risks-and-smells) |

## 50. Tradeoffs and Alternatives

| Approach | Advantages | Risks |
|---|---|---|
| **Traditional layered Laravel application** (no explicit domain modules) | Fastest initial velocity, familiar to any Laravel developer | Recreates the CRUD-collapse problem (Section 2); does not scale organizationally as 34 contexts grow |
| **Domain-oriented modular monolith** (recommended) | Preserves Phase 0.2 boundaries in code; single deployable; extraction-ready | Requires more upfront structural discipline than either extreme |
| **Microservices** | Independent scaling/deployment per context; strong boundary enforcement by infrastructure | Distributed-systems complexity (network partitions, eventual-consistency debugging, deployment orchestration) disproportionate to current team size and still-resolving domain questions |
| **Separate public application** | Complete traffic isolation | A second deployable to operate before evidence of need (Section 18; [runtime-open-decisions.md, RD-19](runtime-open-decisions.md#rd-19--public-traffic-deployment-separation-timing)) |
| **Separate mobile backend** | Tailored API surface for Flutter | Duplicates authorization/business logic already correctly enforced in the Core Laravel Application; violates "avoid duplicate APIs" (Section 20) |
| **Event-driven internal architecture** (adopted for cross-context integration, not universally) | Decouples contexts, supports async workloads | Over-applied, it recreates hidden-workflow-chain risk (Section 49); used deliberately per Section 12's orchestration rule, not as a blanket default |
| **Full CQRS and event sourcing** | Complete audit trail via event log; strong read/write separation | Substantial complexity cost (separate read/write models, event-replay tooling, eventual-consistency UX everywhere) not justified by current evidence; PMMS achieves its audit and correction-not-overwrite requirements through [high-integrity-domain-rules.md](high-integrity-domain-rules.md)'s versioning discipline without full event sourcing. **Not adopted broadly.** |

## 51. Recommended Initial Runtime Direction

- One Laravel codebase.
- Domain-oriented modules (34, mapped to bounded contexts).
- React and Inertia administrative application.
- Public portal using controlled projections (co-deployed initially, isolated at the application layer).
- Flutter client using versioned APIs.
- MySQL as the authoritative relational store.
- Redis for queues, cache, locks, rate limiting, and real-time support (never as a system of record).
- Horizon-managed workers, with `critical` isolated from bulk categories.
- Reverb-managed real-time communication, with graceful polling fallback.
- MinIO-managed object storage, with database-metadata-gated access.
- Queue-backed notifications and document processing.
- Explicit read models for public and cross-domain reporting, never authoritative.
- Extraction-ready boundaries, inherited from ADR-0002.
- **Docker deferred to the infrastructure implementation phase** — not initialized during Phase 0.4 (working rule 8).

## 52. Phase 0.4 Deliverables

1. [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) (this document)
2. [application-architecture.md](application-architecture.md)
3. [laravel-architecture.md](laravel-architecture.md)
4. [react-inertia-architecture.md](react-inertia-architecture.md)
5. [flutter-architecture.md](flutter-architecture.md)
6. [internal-integration-architecture.md](internal-integration-architecture.md)
7. [api-and-client-boundaries.md](api-and-client-boundaries.md)
8. [event-and-queue-architecture.md](event-and-queue-architecture.md)
9. [realtime-architecture.md](realtime-architecture.md)
10. [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md)
11. [notification-architecture.md](notification-architecture.md)
12. [caching-and-session-architecture.md](caching-and-session-architecture.md)
13. [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)
14. [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md)
15. [runtime-security-architecture.md](runtime-security-architecture.md)
16. [observability-and-error-handling.md](observability-and-error-handling.md)
17. [environment-and-configuration-model.md](environment-and-configuration-model.md)
18. [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md)
19. [testing-architecture.md](testing-architecture.md)
20. [runtime-open-decisions.md](runtime-open-decisions.md)
21. [README.md](README.md) (updated architecture documentation index)
22. [../../.ai/project-context.md](../../.ai/project-context.md) (updated)
23. [../../.ai/current-phase.md](../../.ai/current-phase.md) (updated)
24. [../../.ai/architecture.md](../../.ai/architecture.md) (updated)
25. [../../.ai/coding-standards.md](../../.ai/coding-standards.md) (new)
26. [../../.ai/runtime-rules.md](../../.ai/runtime-rules.md) (new)
27. [../../.ai/integration-rules.md](../../.ai/integration-rules.md) (new)
28. [../../.ai/decisions/ADR-0004-application-integration-and-runtime-architecture.md](../../.ai/decisions/ADR-0004-application-integration-and-runtime-architecture.md) (new)

## 53. Phase 0.4 Acceptance Criteria

- [x] Application architecture style is documented.
- [x] Laravel layer responsibilities are defined.
- [x] Bounded-context module structure is documented (34 modules).
- [x] Modular monolith rules are defined (14 rules).
- [x] Command and query architecture is documented.
- [x] Transaction boundaries are documented.
- [x] Domain, application, integration, real-time, audit, and notification events are distinguished (6 types).
- [x] Synchronous and asynchronous rules are documented.
- [x] Queue architecture is documented (11 categories).
- [x] Redis usage boundaries are documented (approved and prohibited lists).
- [x] Horizon architecture is documented.
- [x] Reverb architecture is documented.
- [x] React and Inertia architecture is documented.
- [x] Flutter architecture is documented.
- [x] API and client boundaries are documented (6 categories).
- [x] Internal integration patterns are documented (permitted and discouraged).
- [x] Object-storage flow is documented (13 steps).
- [x] Notification architecture is documented.
- [x] Caching and session boundaries are documented.
- [x] Public runtime boundary is documented.
- [x] Reporting and read-model runtime is documented.
- [x] Offline synchronization runtime is documented.
- [x] Device integration boundary is documented.
- [x] AI integration boundary is documented.
- [x] Error handling and logging are documented.
- [x] Observability and health checks are documented.
- [x] Environment model is documented (7 environments).
- [x] Configuration and secret rules are documented.
- [x] Scaling and resilience principles are documented.
- [x] Runtime security is documented.
- [x] Testing architecture is documented (8 layers).
- [x] Open decisions are recorded (28 runtime decisions, cross-referenced against Phase 0.1–0.3 decisions).
- [x] AI workspace is updated.
- [x] No production code is generated.
- [x] No migration is created.
- [x] No package is installed.
- [x] No Docker or CI configuration is created.
- [x] Documents are internally consistent (cross-reference verified — see completion report).

## 54. Exit Criteria

Phase 0.4 is complete because:

- Every Phase 0.2 bounded context has an assigned Laravel module structure and layering discipline.
- Every Phase 0.3 authorization rule has a named runtime enforcement point.
- High-integrity workflows have synchronous, transactional, auditable execution rules.
- Public, mobile, device, service, and offline runtime boundaries are technically (not just conceptually) defined.
- Queue, event, real-time, storage, caching, and session architecture give the approved technology stack (Laravel, React, Flutter, Redis, Horizon, Reverb, MinIO) clear, non-overlapping responsibilities.
- Environment, configuration, resilience, and testing architecture anticipate operational readiness without inventing unvalidated numerical targets.
- **Phase 0.5 can design the actual database schema and begin implementation architecture** using the approved module structure, command/query catalog, and integration patterns — without rediscovering how Laravel, React, Flutter, and the infrastructure stack fit together.
- No implementation was prematurely generated — verified in the completion report's quality checks.

## 55. Next Phase

```text
Phase 0.5 — Data Architecture and Database Design
```

Phase 0.5 is not started as part of this task.
