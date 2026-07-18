# PMMS Architecture Documentation — `docs/01-architecture/`

This directory contains the Phase 0.2 (domain architecture), Phase 0.3 (identity, role, permission, scope, and assignment architecture), and Phase 0.4 (application, integration, and runtime architecture) documentation for the **Provincial Meet Management System (PMMS)**. Phase 0.2 decomposes the product vision into business domains and bounded contexts; Phase 0.3 builds the authorization model on top of those contexts; Phase 0.4 defines how both are actually executed across Laravel, React, Flutter, and the supporting infrastructure stack — together, the complete conceptual and structural foundation later database design and implementation will build on.

**No implementation code, migrations, database schema, API endpoints, routes, UI component design, Laravel policies/gates/middleware, Docker files, CI configuration, or package installation are contained in this directory.** It is architecture documentation only, per the Phase 0.2–0.4 working rules.

## Purpose

Phase 0.2 exists to decompose PMMS into clear business domains and bounded contexts *before* implementation begins. Phase 0.3 exists to define how people, devices, and services are identified and what they may do, because conventional RBAC cannot express PMMS's multi-meet, high-integrity, offline-capable reality. Phase 0.4 exists to give both a concrete technical home — a Laravel module structure, a frontend structure, an integration pattern, a runtime topology — so that Phase 0.5 (data architecture) begins from a fully specified structural foundation rather than an unstructured blank slate. See [phase-0.2-domain-architecture.md, Section 2](phase-0.2-domain-architecture.md#2-executive-summary), [phase-0.3-access-and-assignment-architecture.md, Section 2](phase-0.3-access-and-assignment-architecture.md#2-executive-summary), and [phase-0.4-application-integration-runtime-architecture.md, Section 2](phase-0.4-application-integration-runtime-architecture.md#2-executive-summary) for the full rationale of each.

## Document Index

### Phase 0.2 — Domain Architecture

| Document | Purpose |
|---|---|
| [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md) | Primary Phase 0.2 document: goals, domain classification, bounded context summary, detailed Core-context definitions, context map, data ownership, consistency boundaries, high-integrity domains, aggregates, entities/value objects, domain services, events, workflows/commands, public/reporting/offline boundaries, AI boundaries, risks/smells, alternatives considered, recommended architecture direction, acceptance/exit criteria |
| [business-capability-map.md](business-capability-map.md) | 18 Level-1 capability groups decomposed into Level-2/3 capabilities, each mapped to an owning bounded context |
| [domain-classification.md](domain-classification.md) | Core / Supporting / Generic classification with rationale for every bounded context |
| [bounded-context-catalog.md](bounded-context-catalog.md) | Authoritative catalog of all 34 bounded contexts — summary table plus a full entry per context |
| [context-map.md](context-map.md) | Relationship matrix (Customer-Supplier, ACL, Published Language, Partnership, etc.) and 6 Mermaid diagrams |
| [domain-glossary.md](domain-glossary.md) | Single consistent definition per domain term, each attributed to an owning context |
| [data-ownership-map.md](data-ownership-map.md) | Authoritative owner, steward, consumers, sensitivity, retention, and correction authority for 34 major data concepts |
| [domain-events-catalog.md](domain-events-catalog.md) | Conceptual domain events by owning context, with consistency/audit/offline/idempotency notes |
| [workflow-and-command-catalog.md](workflow-and-command-catalog.md) | 23 business workflows and 22 command candidates, grouped by domain |
| [high-integrity-domain-rules.md](high-integrity-domain-rules.md) | Architectural safeguards (no silent mutation, separation of duties, versioning, AI advisory-only, etc.) for 11 high-integrity domains |
| [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md) | Reporting principles and 17 candidate read models, keeping transactional contexts authoritative |
| [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) | Offline/sync boundaries for 10 offline-relevant contexts, plus actions that must never finalize offline |
| [domain-open-decisions.md](domain-open-decisions.md) | 26 unresolved domain-modeling decisions, cross-referenced against Phase 0.1's product-level open decisions |

### Phase 0.3 — Identity, Role, Permission, Scope, and Assignment Architecture

| Document | Purpose |
|---|---|
| [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) | Primary Phase 0.3 document: architecture goals/principles, identity/role/permission/scope/assignment models, authorization decision model, separation of duties, high-integrity access controls, data classification, public/device/service identity, offline authorization, emergency/impersonation policy, access review/revocation, authentication boundaries, testing strategy, risks/anti-patterns, recommended direction, acceptance/exit criteria |
| [identity-model.md](identity-model.md) | Person / Participant Profile / User Account / Device / Service / AI identity distinctions, relationships, lifecycle, minor-athlete and guardian considerations |
| [role-catalog.md](role-catalog.md) | 53 roles across 12 categories, each with purpose, required assignment, allowed scopes, sensitive capabilities, SoD concerns, and validation status |
| [permission-catalog.md](permission-catalog.md) | ~115 business-action permissions across 17 categories, each with risk level, required role/scope/assignment, resource-state conditions, audit level, offline allowance |
| [scope-model.md](scope-model.md) | 18 scope types across two hierarchies plus non-hierarchical dimensions, composition/intersection rules, non-inheritance rules, example evaluation scenarios |
| [assignment-model.md](assignment-model.md) | 14 assignment types, conceptual fields, lifecycle state machine, concurrent-assignment rules, acting/emergency assignments, conceptual examples |
| [authorization-decision-model.md](authorization-decision-model.md) | 16-step decision sequence, explicit-deny precedence, 12-scenario decision table, logging/caching/revocation considerations |
| [separation-of-duties-matrix.md](separation-of-duties-matrix.md) | 11 conflict entries covering registration/eligibility, scoring/certification, protests, tally, accreditation, finance, platform/security admin, audit, medical/publication, device admin, impersonation |
| [high-integrity-access-controls.md](high-integrity-access-controls.md) | Access-control specifics (roles, scopes, assignments, state conditions, SoD, approval/audit level, correction control, offline/AI limitation) for 13 high-integrity domains |
| [device-and-service-identity-model.md](device-and-service-identity-model.md) | 8 device categories and 10 service categories, trust principles, device-loss and service-compromise handling |
| [offline-authorization-model.md](offline-authorization-model.md) | Offline snapshot/validity/binding model, Provisional-vs-Final action classification, revocation lag, actions that must never finalize offline |
| [access-review-and-revocation.md](access-review-and-revocation.md) | Review types/triggers/stakeholders and the revocation model for every identity/authority type |
| [access-open-decisions.md](access-open-decisions.md) | 22 unresolved access-architecture decisions, cross-referenced against Phase 0.1/0.2 open decisions |

### Phase 0.4 — Application, Integration, and Runtime Architecture

| Document | Purpose |
|---|---|
| [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) | Primary Phase 0.4 document: architecture style, application landscape, Laravel layering/module/CQRS/transaction/event architecture, sync/async rules, queue/Horizon/Redis/Reverb architecture, public runtime boundary, React/Inertia and Flutter architecture, API/integration boundaries, object storage, notifications, caching/sessions, reporting/search, offline sync, device/AI integration, external integration, error/logging/observability, environments/configuration, scaling/resilience/backup, runtime security, testing, risks/anti-patterns, tradeoffs, recommended direction, acceptance/exit criteria |
| [application-architecture.md](application-architecture.md) | Application surfaces, why conventional CRUD is insufficient, public portal separation, layering summary |
| [laravel-architecture.md](laravel-architecture.md) | Domain/Application/Infrastructure/Delivery layers, 34-module structure, modular monolith rules, command/query/transaction/event/workflow architecture |
| [react-inertia-architecture.md](react-inertia-architecture.md) | Frontend directory structure, feature-oriented grouping, InertiaJS boundary, server-authoritative rules |
| [flutter-architecture.md](flutter-architecture.md) | Mobile directory structure, layers, mobile-relevant bounded contexts, offline record states, rules |
| [internal-integration-architecture.md](internal-integration-architecture.md) | Permitted/discouraged cross-context integration patterns, pattern-selection flowchart, external integration status |
| [api-and-client-boundaries.md](api-and-client-boundaries.md) | 6 API categories (mobile, device, public, admin integration, webhook, sync), shared principles |
| [event-and-queue-architecture.md](event-and-queue-architecture.md) | 11 queue categories, job rules, Horizon architecture, event-type distinctions, reliable-delivery consideration |
| [realtime-architecture.md](realtime-architecture.md) | Reverb use cases, channel types, rules, provisional-vs-published distinction, fallback behavior |
| [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md) | MinIO responsibilities, 13-step document flow, rules |
| [notification-architecture.md](notification-architecture.md) | Notification principles, delivery channels, recipient resolution |
| [caching-and-session-architecture.md](caching-and-session-architecture.md) | Cache targets/rules, session options/requirements, environment-specific direction |
| [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md) | Read-model update timing, staged search architecture, generated-document traceability, privacy filtering |
| [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md) | Sync protocol components, record states, conflict detection/resolution, sync priority ordering |
| [runtime-security-architecture.md](runtime-security-architecture.md) | Authentication integration boundary, authorization enforcement points, runtime security controls |
| [observability-and-error-handling.md](observability-and-error-handling.md) | Error categories/rules, logging categories/rules, observability signals, health-check type separation |
| [environment-and-configuration-model.md](environment-and-configuration-model.md) | 7 environments, configuration categories/rules, runtime deployment units |
| [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md) | Scaling boundaries, performance principles, availability/resilience patterns, backup/recovery considerations |
| [testing-architecture.md](testing-architecture.md) | 8 test layers, traceability to architecture documents, high-priority test scenarios |
| [runtime-open-decisions.md](runtime-open-decisions.md) | 28 unresolved application/integration/runtime decisions, cross-referenced against Phase 0.1–0.3 open decisions |

## Reading Order

### Phase 0.2
1. [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md) — read first; establishes goals, classification, and the recommended architecture direction.
2. [business-capability-map.md](business-capability-map.md) and [domain-classification.md](domain-classification.md) — the "what and why" of decomposition.
3. [bounded-context-catalog.md](bounded-context-catalog.md) — the authoritative reference for every context's boundary.
4. [context-map.md](context-map.md) — how contexts relate to each other.
5. [domain-glossary.md](domain-glossary.md) — shared vocabulary, useful as a standing reference while reading the rest.
6. [data-ownership-map.md](data-ownership-map.md), [domain-events-catalog.md](domain-events-catalog.md), [workflow-and-command-catalog.md](workflow-and-command-catalog.md) — the operational detail layer.
7. [high-integrity-domain-rules.md](high-integrity-domain-rules.md), [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md), [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) — cross-cutting safeguard boundaries.
8. [domain-open-decisions.md](domain-open-decisions.md) — read last; everything still unresolved.

### Phase 0.3 (read after Phase 0.2)
1. [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) — read first; establishes why conventional RBAC is insufficient and the recommended hybrid model.
2. [identity-model.md](identity-model.md) — the foundational Person/Participant/User Account distinction everything else depends on.
3. [role-catalog.md](role-catalog.md) and [permission-catalog.md](permission-catalog.md) — what roles exist and what business actions they may be permitted.
4. [scope-model.md](scope-model.md) and [assignment-model.md](assignment-model.md) — where and for how long authority applies.
5. [authorization-decision-model.md](authorization-decision-model.md) — how all of the above combine into a single allow/deny decision.
6. [separation-of-duties-matrix.md](separation-of-duties-matrix.md) and [high-integrity-access-controls.md](high-integrity-access-controls.md) — the safeguards for the platform's most sensitive workflows.
7. [device-and-service-identity-model.md](device-and-service-identity-model.md) and [offline-authorization-model.md](offline-authorization-model.md) — non-human identities and disconnected operation.
8. [access-review-and-revocation.md](access-review-and-revocation.md) — how access is kept correct over time.
9. [access-open-decisions.md](access-open-decisions.md) — read last; everything still unresolved.

### Phase 0.4 (read after Phase 0.3)
1. [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) — read first; establishes the recommended architecture style and application landscape.
2. [application-architecture.md](application-architecture.md) and [laravel-architecture.md](laravel-architecture.md) — the backend structural foundation.
3. [react-inertia-architecture.md](react-inertia-architecture.md) and [flutter-architecture.md](flutter-architecture.md) — the client structural foundation.
4. [internal-integration-architecture.md](internal-integration-architecture.md) and [api-and-client-boundaries.md](api-and-client-boundaries.md) — how modules and clients talk to each other.
5. [event-and-queue-architecture.md](event-and-queue-architecture.md), [realtime-architecture.md](realtime-architecture.md), [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md), [notification-architecture.md](notification-architecture.md), [caching-and-session-architecture.md](caching-and-session-architecture.md) — infrastructure-service responsibilities.
6. [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md) and [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md) — derived-data and disconnected-operation runtime.
7. [runtime-security-architecture.md](runtime-security-architecture.md) and [observability-and-error-handling.md](observability-and-error-handling.md) — cross-cutting operational safeguards.
8. [environment-and-configuration-model.md](environment-and-configuration-model.md), [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md), [testing-architecture.md](testing-architecture.md) — operational readiness.
9. [runtime-open-decisions.md](runtime-open-decisions.md) — read last; everything still unresolved.

## Status Legend

| Status | Meaning |
|---|---|
| Draft Complete — Pending Domain and Stakeholder Validation | Phase 0.2 status: content complete, no formal architecture/domain-expert sign-off yet |
| Draft Complete — Pending Security, Domain, and Stakeholder Validation | Phase 0.3 status: content complete, no formal security/architecture/domain-expert sign-off yet |
| Draft Complete — Pending Architecture, Security, and Engineering Validation | Phase 0.4 status: content complete, no formal architecture/security/engineering sign-off yet |
| Validated | Reviewed and confirmed by identified stakeholders (not yet reached for any document) |
| Superseded | Replaced by a later-phase document (not yet applicable) |

## Ownership and Review Expectations

A document owner (software architect) and domain-expert reviewers (sports specialists, DepEd policy stakeholders, technical officials, a security architect/administrator, and — for Phase 0.4 specifically — Laravel/React/Flutter/DevOps engineering leads) are to be identified — see the consultation priorities already established in [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md). Until formal review occurs, this documentation should be treated as a structured, defensible proposal built from the approved Phase 0.1–0.3 foundation, not as an approved specification.

## Relationship to Phase 0.1

Every bounded context (Phase 0.2) traces back to a capability named in [../00-product/phase-0.1-product-foundation.md, Section 9](../00-product/phase-0.1-product-foundation.md#9-product-scope-summary), and every high-integrity domain traces back to the same designation in [../00-product/phase-0.1-product-foundation.md, Section 8](../00-product/phase-0.1-product-foundation.md#8-product-principles). Every Phase 0.3/0.4 decision that depends on an unresolved Phase 0.1 decision is cross-referenced explicitly (e.g., "blocked pending [Phase 0.1 OD-07]") rather than assumed.

## Relationship to Phase 0.5

**Phase 0.5 — Data, Database, Persistence, and Information Lifecycle Architecture is complete** — see [../02-data/README.md](../02-data/README.md). It consumed this directory's bounded contexts (Phase 0.2), authorization model (Phase 0.3), and application/module structure (Phase 0.4) to define the logical data architecture, persistence ownership, and database standards. Phase 0.5 did not need to rediscover module boundaries, command/query contracts, or integration patterns from scratch — no boundary defined in this directory was altered by Phase 0.5's work. See [../02-data/phase-0.5-data-database-persistence-architecture.md, Section 76](../02-data/phase-0.5-data-database-persistence-architecture.md#76-risks-assumptions-tradeoffs-and-open-decisions) for how Phase 0.5 findings relate back to this directory's decisions.

## Relationship to Phase 0.6

**Phase 0.6 — Security, Privacy, Audit, Compliance, and Data Governance Architecture is now complete** — see [../03-security/README.md](../03-security/README.md). It preserves this directory's Phase 0.3 authorization model and Phase 0.4 runtime boundaries unchanged, adding security assurance, privileged-access governance, audit architecture, and privacy/compliance controls on top. No boundary defined in this directory was altered by Phase 0.6's work.

## Relationship to Phase 0.7

**Phase 0.7 — Quality Engineering, Testing, Validation, and Assurance Architecture is now complete** — see [../04-quality/README.md](../04-quality/README.md). It extends [testing-architecture.md](testing-architecture.md)'s 8 test layers into a 12-level, 21-type quality architecture, and preserves every bounded-context, authorization, and runtime boundary in this directory unchanged. No boundary defined in this directory was altered by Phase 0.7's work.

## Relationship to Phase 0.8

**Phase 0.8 — DevOps, Environment, CI/CD, Deployment, Observability, and Operations Architecture is complete** — see [../05-devops/README.md](../05-devops/README.md). It extends [environment-and-configuration-model.md](environment-and-configuration-model.md) and [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md) into a full deployment/observability/operations architecture, and preserves every bounded-context, authorization, and runtime boundary in this directory unchanged. No boundary defined in this directory was altered by Phase 0.8's work.

## Relationship to Phase 0.9

**Phase 0.9 — Design System, UX, Accessibility, and Cross-Platform Experience Architecture is now complete** — see [../06-design/README.md](../06-design/README.md). It extends [react-inertia-architecture.md](react-inertia-architecture.md) and [flutter-architecture.md](flutter-architecture.md) into a full cross-platform experience and design-system architecture, and preserves every bounded-context, authorization, and runtime boundary in this directory unchanged — restated absolutely, frontend hiding is never treated as authorization. No boundary defined in this directory was altered by Phase 0.9's work.

## Relationship to Phase 0.10

**Phase 0.10 — AI-Assisted Platform Architecture is now complete** — see [../07-ai/README.md](../07-ai/README.md). It consumed this directory's bounded contexts (Phase 0.2), authorization model (Phase 0.3), and application/runtime boundaries (Phase 0.4) to define the AI Gateway pattern, provider/model abstraction, and the requesting-user/AI-service-identity dual-authorization model — see [../07-ai/ai-platform-and-service-architecture.md, Section 4](../07-ai/ai-platform-and-service-architecture.md#4-relationship-to-the-ai-gateway). No boundary defined in this directory was altered by Phase 0.10's work; every AI request still resolves to this directory's existing authorization and application boundaries.

## Relationship to Phase 0.11

**Phase 0.11 — Event-Driven Workflows, Notifications, Messaging, and Responsible Automation Architecture is now complete** — see [../08-workflows/README.md](../08-workflows/README.md). It consumed this directory's [domain-events-catalog.md](domain-events-catalog.md), [workflow-and-command-catalog.md](workflow-and-command-catalog.md) (WF-01–WF-23), and [laravel-architecture.md, Sections 5–7](laravel-architecture.md#5-domain-event-architecture) to define the process-manager, state-machine, event-contract, and responsible-automation architecture layered on top — extending, never redefining, this directory's existing workflow and event catalogs. No boundary, event, or workflow defined in this directory was altered by Phase 0.11's work.

## Relationship to Phase 0.12

**Phase 0.12 — Performance, Scalability, Multi-Tenancy, Disaster Recovery, and Enterprise Readiness Architecture is now complete** — see [../09-enterprise/README.md](../09-enterprise/README.md). It consumed this directory's [data-ownership-map.md](data-ownership-map.md), [bounded-context-catalog.md](bounded-context-catalog.md) (BC-03 Organization Directory), [domain-open-decisions.md, DD-21](domain-open-decisions.md#dd-21--tenant-boundaries) (Tenant Boundaries), and [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md) to define the multi-tenant product model, tenant-isolation architecture, and scalability model — extending, never redefining, this directory's existing bounded-context ownership and scaling-boundary rules. No boundary defined in this directory was altered by Phase 0.12's work.

## Relationship to Phase 0.13

**Phase 0.13 — Architecture Validation, Gap Analysis, Technical Debt, and Final Architecture Review is now complete** — see [../10-review/README.md](../10-review/README.md). It reviewed this directory's domain, identity, and application architecture as the most mature area of the entire Phase 0 corpus (zero cross-context write, zero ownership ambiguity, zero circular dependency found) — see [../10-review/domain-bounded-context-and-ownership-review.md](../10-review/domain-bounded-context-and-ownership-review.md) and [../10-review/identity-access-scope-and-assignment-review.md](../10-review/identity-access-scope-and-assignment-review.md). It also identified this directory's single highest-leverage unresolved decision (DD-01, participant identity modeling) as a Priority 0 blocker for Phase 0.14's first work package — see [../10-review/architecture-gap-register.md, GAP-12](../10-review/architecture-gap-register.md#gap-12--participant-identity-modeling-unresolved-dd-01). No boundary defined in this directory was altered by Phase 0.13's work.

## Relationship to Phase 0.14

The next phase is not yet started, per working rule 8 of Phase 0.13. See [../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md, Section 47](../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md#47-next-phase).

## Update Rules

1. Changes to bounded-context boundaries should be reflected first in [bounded-context-catalog.md](bounded-context-catalog.md), then propagated to [context-map.md](context-map.md), [data-ownership-map.md](data-ownership-map.md), and [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md) as needed.
2. Changes to roles/permissions should be reflected first in [role-catalog.md](role-catalog.md)/[permission-catalog.md](permission-catalog.md), then propagated to [separation-of-duties-matrix.md](separation-of-duties-matrix.md) and [high-integrity-access-controls.md](high-integrity-access-controls.md) as needed.
3. Changes to module structure or integration patterns should be reflected first in [laravel-architecture.md](laravel-architecture.md)/[internal-integration-architecture.md](internal-integration-architecture.md), then propagated to the relevant infrastructure-service document (queue, real-time, storage, caching, etc.).
4. Resolving an item in [domain-open-decisions.md](domain-open-decisions.md), [access-open-decisions.md](access-open-decisions.md), or [runtime-open-decisions.md](runtime-open-decisions.md) should update its `Status` field and, where it changes a boundary or rule, be reflected back into the relevant document.
5. New terminology should be added to [domain-glossary.md](domain-glossary.md) rather than defined inline elsewhere, to preserve a single consistent definition per term.
6. Keep `.ai/project-context.md`, `.ai/architecture.md`, `.ai/domain-glossary.md`, `.ai/security-rules.md`, `.ai/authorization-rules.md`, `.ai/coding-standards.md`, `.ai/runtime-rules.md`, and `.ai/integration-rules.md` (see [../../.ai/](../../.ai/)) in sync with material changes to this directory, since those files are the primary context AI-assisted development tools use for this project.
