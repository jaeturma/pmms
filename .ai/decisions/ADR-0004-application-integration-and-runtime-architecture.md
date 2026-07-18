# ADR-0004: Application, Integration, and Runtime Architecture

## Status

Accepted (as a Phase 0.4 application/runtime-architecture decision; pending formal architecture, security, and engineering sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 established PMMS as a domain-oriented modular monolith with 34 bounded contexts. ADR-0003 established a hybrid RBAC+ABAC+scope+assignment authorization model on top of those contexts. Neither decision specified how either is actually executed: what a Laravel module looks like on disk, what runs synchronously versus in a queue, how a live scoreboard update reaches a browser, where an uploaded eligibility document physically lives, or how the approved technology stack (Laravel 13, React 19, Inertia, Flutter, MySQL, Redis, Horizon, Reverb, MinIO) divides responsibility.

Left unspecified, this gap risks the same failure mode Phase 0.2 was built to prevent at the domain level: without an explicit application/runtime contract, a bounded context's boundary is only as real as whichever developer happens to respect it on a given day — nothing stops a report job from writing to a table it doesn't own, or a queued job from treating a stale authorization grant as still valid, or Redis quietly becoming a second (unreliable) system of record for scoring data.

## Decision

PMMS will use a **domain-oriented modular monolith with layered modules (Domain/Application/Infrastructure/Delivery), explicit application boundaries, internal event-driven integration, dedicated public read models, queue-backed asynchronous processing, and independently scalable runtime components where needed.**

Specifically:

1. **Every bounded context maps to one Laravel module** (`app/Domains/<Context>/`) with four layers whose dependency direction is always Infrastructure → Application → Domain, and whose Delivery layer stays thin (see [../../docs/01-architecture/laravel-architecture.md](../../docs/01-architecture/laravel-architecture.md)).
2. **Commands and Queries are the unit of business operation.** Every command from the Phase 0.2 command catalog carries forward unchanged, is validated, authorized per the Phase 0.3 decision sequence, transactional, auditable, and idempotent where retryable. CQRS is a design discipline, not an infrastructure mandate — full CQRS/event sourcing is explicitly rejected as a broad default.
3. **High-integrity state transitions are always synchronous**; notifications, reports, media processing, search indexing, analytics, exports, and imports are always asynchronous, via 11 queue categories with `critical` processing isolated from bulk categories.
4. **Six event types are distinguished and never conflated:** domain event, application event, integration event, real-time broadcast event, audit event, notification event — a single business fact (e.g., `ResultCertified`) may produce several without itself being any one of them.
5. **Redis is never a system of record.** MinIO is infrastructure, with Document and Records (BC-30) metadata remaining authoritative for access. The public portal reads only approved projections (BC-29), never operational tables directly, and is architecturally isolated from Scoring/Access-Validation write capacity.
6. **React/Inertia serves the administrative application; dedicated APIs exist only for genuinely distinct clients** (Flutter, devices, external systems) — never a duplicate API per Inertia page. The frontend is never the authorization boundary.
7. **AI services never hold a standing write credential.** Every AI-proposed change flows through the normal Command/Application-layer path with full authorization and audit; an AI feature's effective access is the intersection of its own scope and the requesting user's authority, never a union.
8. **No external integration is currently approved.** Any future integration uses an anti-corruption-layer adapter, built only once specifically approved.

**Explicitly not decided by this ADR:** database schema (Phase 0.5), specific API endpoint/route design, UI component design, the specific offline synchronization wire protocol, message-broker technology specifics beyond the already-approved Reverb/Horizon direction, monitoring vendor selection, and numerical performance/backup targets (RPO/RTO, retention durations) pending DepEd policy input.

## Rationale

- **Preserves Phase 0.2/0.3 boundaries in code, not just documentation.** A bounded context and an authorization rule are only real if the running system enforces them; this ADR gives both a concrete technical home.
- **Matches the confirmed technology stack's actual strengths.** Laravel's module-friendly architecture, Horizon's queue supervision, Reverb's broadcast capability, and MinIO's S3-compatible storage are each assigned one clear job rather than left to organically overlap (e.g., "Redis is also kind of a database").
- **Protects the platform's institutional-trust guarantees at the exact points they're most easily violated** — public endpoints, queued jobs, offline clients, and AI integrations are precisely where a high-integrity guarantee established at the domain layer could be silently undermined by a runtime shortcut; this ADR names each boundary and states the rule that prevents the violation.
- **Avoids both extremes of premature complexity and premature simplicity.** Full CQRS/event sourcing and microservices are rejected as unjustified by current evidence; an undifferentiated single-layer CRUD application is rejected as unable to enforce the boundaries Phase 0.2/0.3 established.

## Approved Architecture Direction

> Domain-oriented modular monolith with layered modules, explicit application boundaries, internal event-driven integration, dedicated public read models, queue-backed asynchronous processing, and independently scalable runtime components where needed.

## Domain Classification (Carried Forward)

Unchanged from ADR-0002 — 34 bounded contexts, 13 Core / 16 Supporting / 5 Generic.

## Bounded-Context Principles (Carried Forward)

Unchanged from ADR-0002 — one authoritative owner per data concept, named DDD relationship patterns for every cross-context flow, minimal Shared Kernel.

## Data Ownership Rule (Runtime Expression)

Every major data concept's authoritative Laravel module is the only module permitted to write to its underlying storage. Other modules hold references, call the owning module's Application-layer contract, or consume its published projection — never a second write path.

## Integration Rule

Cross-context integration uses one of six named patterns (same-context call, synchronous contract call, reliable domain event, async integration event, read-model consumption, anti-corruption adapter) selected by a documented decision flow — never an ad hoc cross-context ORM relationship, shared mutable table, or hidden event-listener chain.

## High-Integrity Domain Rule (Runtime Expression)

Eligibility approval, result certification, score validation, protest resolution, medal tally certification, credential revocation, entry locking, and assignment activation are always synchronous, transactional, and auditable — never dependent on an unconfirmed background job, never finalized by a queue, never finalized by an offline client.

## Public Data Rule (Runtime Expression)

The public portal owns no authoritative data, reads only from BC-29's approved projections, and is architecturally and operationally isolated from the write-path capacity that high-integrity domains require.

## Offline Authority Rule (Runtime Expression)

Every offline-captured record is Provisional until server-revalidated. The Phase 0.3 "never final offline" list carries forward unchanged and is enforced at the Synchronization API boundary, not merely documented as a client-side convention.

## AI Authority Rule (Runtime Expression)

No AI service holds a standing database write credential. Every AI-proposed change is routed through the same Command/Application-layer path, authorization check, and audit trail as a human-initiated action.

## Consequences

**Positive:**
- Phase 0.5 (data architecture) inherits a complete module structure, command/query catalog, and integration-pattern vocabulary, and can design the database schema against known write-ownership boundaries rather than guessing at them.
- High-integrity workflows have runtime enforcement points named before any code exists to violate them.
- The technology stack's components (Laravel, React, Flutter, Redis, Horizon, Reverb, MinIO) each have unambiguous, non-overlapping responsibility, reducing the risk of organic scope creep (e.g., Redis becoming a quasi-database) as implementation proceeds.

**Negative / trade-offs:**
- The four-layer module discipline (Domain/Application/Infrastructure/Delivery) applied across 34 modules is more structural overhead than an unstructured Laravel application, particularly for small Supporting/Generic contexts — mitigated by allowing lighter internal structure for low-complexity modules, but still a real discipline cost the team must maintain.
- Several runtime decisions (RD-01 outbox pattern, RD-19 public-portal deployment separation, RD-13/RD-18 retention/RPO targets) remain open, meaning Phase 0.5 and later implementation will need to revisit this ADR's assumptions once pilot-meet operational data is available.
- The explicit rejection of full CQRS/event sourcing means some future high-scale reporting or audit-replay use case may eventually require revisiting that decision — accepted as a reasonable trade against the complexity cost of adopting it now without evidence of need.

## Alternatives Considered

1. **Traditional unstructured Laravel application.** Rejected — collapses the domain and authorization boundaries established in ADR-0002/0003, making high-integrity separation-of-duties unenforceable in code.
2. **Microservices per bounded context.** Rejected — same distributed-systems complexity cost identified in ADR-0002, now applied even more granularly (34 potential services) with no evidenced operational need.
3. **Separate mobile backend API.** Rejected — would duplicate authorization and business logic already correctly enforced in the Core Laravel Application, violating the "avoid duplicate APIs" principle.
4. **Full CQRS and event sourcing.** Rejected as a broad default — the audit and correction-not-overwrite guarantees PMMS needs are achieved through the Phase 0.2 versioning discipline ([high-integrity-domain-rules.md](../../docs/01-architecture/high-integrity-domain-rules.md)) without the substantial infrastructure cost of full event sourcing.
5. **Separate public-portal deployment from launch.** Rejected for the initial build — application-layer isolation (public reads only from BC-29 projections) achieves the most valuable part of this separation without operating a second deployable before evidence of need (see [runtime-open-decisions.md, RD-19](../../docs/01-architecture/runtime-open-decisions.md#rd-19--public-traffic-deployment-separation-timing)).

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated software architect, security architect, and Laravel/React/Flutter/DevOps engineering leads, per [../../docs/01-architecture/README.md, "Ownership and Review Expectations"](../../docs/01-architecture/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.4 open decisions, per [../../docs/01-architecture/runtime-open-decisions.md, "Summary of Blocking / High-Priority Runtime Decisions"](../../docs/01-architecture/runtime-open-decisions.md#summary-of-blocking--high-priority-runtime-decisions) — notably RD-19 (public traffic deployment separation timing).
- Continued resolution of the Phase 0.1 policy decisions this ADR's high-integrity runtime rules depend on (eligibility authority, result approval chain, protest authority, medal tally rules).

## Related Documents

- [../../docs/01-architecture/phase-0.4-application-integration-runtime-architecture.md](../../docs/01-architecture/phase-0.4-application-integration-runtime-architecture.md)
- [../../docs/01-architecture/application-architecture.md](../../docs/01-architecture/application-architecture.md)
- [../../docs/01-architecture/laravel-architecture.md](../../docs/01-architecture/laravel-architecture.md)
- [../../docs/01-architecture/internal-integration-architecture.md](../../docs/01-architecture/internal-integration-architecture.md)
- [../../docs/01-architecture/runtime-security-architecture.md](../../docs/01-architecture/runtime-security-architecture.md)
- [../../docs/01-architecture/runtime-open-decisions.md](../../docs/01-architecture/runtime-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../runtime-rules.md](../runtime-rules.md)
- [../integration-rules.md](../integration-rules.md)
- [../coding-standards.md](../coding-standards.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
