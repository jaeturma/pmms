# PMMS Application Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) · [laravel-architecture.md](laravel-architecture.md) · [bounded-context-catalog.md](bounded-context-catalog.md)

This document defines PMMS's overall application landscape: the major runtime surfaces, how they relate, and the layering discipline every Laravel domain module follows. **No code, namespace, or directory is created by this document** — it defines the structure later implementation must follow.

---

## 1. Application Surfaces

| Surface | Responsibility | Primary Technology |
|---|---|---|
| **Core Laravel Application** | Authoritative business operations, authorization enforcement, workflow coordination, persistence, domain events, operational APIs | Laravel 13 |
| **React/Inertia Administrative Application** | Authenticated administration, meet operations, committee workflows, tournament management, scoring interfaces, dashboards | React 19 + Inertia v3 + TypeScript + Tailwind 4 + shadcn/ui |
| **Public Web Application** | Public schedules, results, medal tally, announcements — served from approved projections only | Initially the same Laravel/React deployment, with a controlled publication boundary (see Section 3 and [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)) |
| **Flutter Mobile Application** | Mobile field operations, offline capture, synchronization, QR validation, limited self-service | Flutter |
| **Background Processing Runtime** | Asynchronous work — notifications, document processing, exports, projections | Laravel queue workers supervised by Horizon |
| **Real-Time Runtime** | Approved live updates — scoreboards, tournament progress, medal tally, operational alerts | Laravel Reverb |
| **Object Storage Runtime** | Controlled file/document/image/export/report storage | MinIO (S3-compatible) |
| **Redis Runtime** | Cache, queues, sessions (where approved), rate limiting, locks, Reverb support | Redis |
| **Reporting and Search Runtime** | Approved read models, projections, exports, future search indexes | MySQL-backed initially (see [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)) |

## 2. Why a Conventional Controller-Model-Page Structure Is Insufficient

A default Laravel "thin controller, Eloquent model, Blade/Inertia page" structure collapses the same distinctions Phase 0.2 fought to establish at the domain level. If every bounded context's data lives in a shared `app/Models` directory with no enforced boundary, nothing prevents a Finance controller from directly querying the `official_results` table, or a report-generation job from writing to a `medal_tallies` table outside the Medal Tally domain's own controlled recalculation path. The layering in this document exists specifically to make Phase 0.2's data-ownership principles ([data-ownership-map.md](data-ownership-map.md)) and Phase 0.3's authorization model ([phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md)) enforceable in code, not just in documentation.

## 3. Public Portal Separation

The Public Web Application is architecturally distinguished from the Administrative Application even though both may initially deploy from the same Laravel codebase:

- Public routes/controllers read **only** from BC-29 Public Information's approved projections (see [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)).
- Public routes never share a request-handling path with authenticated administrative mutation endpoints.
- Whether the public surface later becomes an independently deployable runtime is evaluated, not decided, in this phase (see [phase-0.4-application-integration-runtime-architecture.md, Section 18](phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary)).

## 4. Layering Discipline (Summary)

Every Laravel domain module follows four layers — Domain, Application, Infrastructure, Delivery — detailed fully in [laravel-architecture.md](laravel-architecture.md). In brief:

| Layer | Owns | Must Not Depend On |
|---|---|---|
| Domain | Business concepts, aggregates, invariants, domain services, domain events | HTTP, Inertia, React, Flutter, queue workers, Redis/MinIO SDK details |
| Application | Use cases (commands/queries), workflow coordination, transaction boundaries, authorization orchestration | Presentation logic |
| Infrastructure | Repositories, ORM adapters, Redis/MinIO adapters, notification/integration clients, search adapters | Domain rules (infrastructure implements interfaces the Domain layer defines, never the reverse) |
| Delivery | Controllers, Inertia endpoints, API controllers, CLI commands, webhook endpoints, queue job entry points, Reverb channel authorization | Business logic (delivery is thin — it translates a request into an Application-layer use case call and translates the result back) |

## 5. Architecture Goals (Cross-Reference)

Full goal list with rationale: [phase-0.4-application-integration-runtime-architecture.md, Section 3](phase-0.4-application-integration-runtime-architecture.md#3-architecture-goals). Summary: domain boundary preservation, maintainability, testability, explicit ownership, high-integrity workflow protection, scalable public delivery, real-time capability, offline support, secure document handling, reliable background processing, observability, commercial readiness, deployment flexibility, extraction-ready boundaries, controlled technical complexity.

## 6. Relationship to Phase 0.2 and Phase 0.3

- Every Laravel domain module in [laravel-architecture.md](laravel-architecture.md) maps to a bounded context from [bounded-context-catalog.md](bounded-context-catalog.md) — module boundaries are not invented here, they are the Phase 0.2 boundaries given a technical home.
- Every authorization enforcement point in this application architecture implements the Phase 0.3 decision sequence from [authorization-decision-model.md](authorization-decision-model.md) — Phase 0.4 does not redefine authorization, it defines *where in the request lifecycle* that decision is evaluated (see [phase-0.4-application-integration-runtime-architecture.md, Section 29](phase-0.4-application-integration-runtime-architecture.md#29-authorization-enforcement-points)).
