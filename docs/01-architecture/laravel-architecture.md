# PMMS Laravel Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [application-architecture.md](application-architecture.md) · [bounded-context-catalog.md](bounded-context-catalog.md) · [event-and-queue-architecture.md](event-and-queue-architecture.md) · [internal-integration-architecture.md](internal-integration-architecture.md)

This document defines the Laravel application's internal structure: layering, module boundaries, modular-monolith rules, and command/query/transaction/event architecture. **No directories, namespaces, classes, or code are created** — this is the structural contract later implementation must follow.

---

## 1. Laravel Application Layering

### Domain Layer
Contains business concepts, aggregates, entities, value objects, domain invariants, domain services, domain events, business policies, and state-transition rules — the conceptual candidates already identified per bounded context in [phase-0.2-domain-architecture.md, Sections 11–13](phase-0.2-domain-architecture.md#11-aggregate-candidate-analysis). **Must not depend on** HTTP, Inertia, React, Flutter, queue workers, MinIO SDK details, Redis implementation details, or framework request objects. The Domain layer is the only layer that may enforce a high-integrity invariant (e.g., "a score's validator cannot equal its enterer" from [high-integrity-domain-rules.md](high-integrity-domain-rules.md)).

### Application Layer
Contains use cases (Commands and Queries — Section 3), application services, workflow coordination, transaction boundaries, authorization orchestration, idempotency coordination, domain-event dispatch coordination, and read-model query coordination. **Must not contain presentation logic.** This is where a Phase 0.3 authorization decision (per [authorization-decision-model.md](authorization-decision-model.md)) is actually evaluated before a Command is allowed to execute.

### Infrastructure Layer
Contains database repositories, ORM adapters, Redis adapters, MinIO adapters, notification-channel adapters, external integration clients, search adapters, queue transport adapters, event transport adapters, file processors, and clock/UUID/identity adapters. Infrastructure **implements interfaces the Domain and Application layers define** — dependency direction always points inward (Infrastructure → Application → Domain), never outward.

### Delivery Layer
Contains HTTP controllers, Inertia endpoints, API controllers, CLI commands, webhook endpoints, queue job entry points, Reverb channel authorization, and mobile synchronization endpoints. **The delivery layer must remain thin** — it authenticates the request, translates input into a Command/Query, calls the Application layer, and translates the result into a response. It never itself decides business rules or performs multi-repository orchestration.

## 2. Laravel Module Structure

A conceptual structure, aligned to the 34 approved bounded contexts, is recommended for later implementation:

```text
app/
├── Domains/
│   ├── PlatformAdministration/      (BC-01)
│   ├── IdentityAccess/              (BC-02)
│   ├── OrganizationDirectory/       (BC-03)
│   ├── MeetAdministration/          (BC-04)
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Delivery/
│   ├── CommitteeOperations/         (BC-05)
│   ├── DelegationManagement/        (BC-06)
│   ├── ParticipantRegistry/         (BC-07)
│   ├── AthleteRegistration/         (BC-08)
│   ├── Eligibility/                 (BC-09)
│   ├── SportsCatalog/               (BC-10)
│   ├── CompetitionEntries/          (BC-11)
│   ├── TournamentManagement/        (BC-12)
│   ├── TechnicalOfficials/          (BC-13)
│   ├── VenueSchedule/               (BC-14)
│   ├── Scoring/                     (BC-15)
│   ├── OfficialResults/             (BC-16)
│   ├── ProtestAppeals/              (BC-17)
│   ├── MedalTally/                  (BC-18)
│   ├── Accreditation/               (BC-19)
│   ├── AccessValidation/            (BC-20)
│   ├── MedicalOperations/           (BC-21)
│   ├── Billeting/                   (BC-22)
│   ├── FoodServices/                (BC-23)
│   ├── Transportation/              (BC-24)
│   ├── SecurityOperations/          (BC-25)
│   ├── FinanceOperations/           (BC-26)
│   ├── IctServiceOperations/        (BC-27)
│   ├── MediaCommunications/         (BC-28)
│   ├── PublicInformation/           (BC-29)
│   ├── DocumentRecords/             (BC-30)
│   ├── Notifications/               (BC-31)
│   ├── AuditCompliance/             (BC-32)
│   ├── ReportingAnalytics/          (BC-33)
│   └── ConfigurationReferenceData/  (BC-34)
├── Shared/       (genuinely cross-context value objects/contracts only — kept minimal)
├── Platform/     (service providers, bootstrapping, framework wiring)
└── Support/      (framework-glue base classes/traits — no business logic)
```

**This directory structure is documented for later reference and is not created during Phase 0.4.**

### Structural Rules

- Every domain module maps to an approved bounded context from [bounded-context-catalog.md](bounded-context-catalog.md); context IDs (BC-01…BC-34) are not encoded into namespace names — descriptive names are used instead (as shown above).
- **Not every bounded context requires an independent package.** Small Supporting/Generic contexts (e.g., Billeting, Food Services) may use a lighter internal structure (fewer sub-layers) proportionate to their complexity, while still respecting the four-layer discipline conceptually.
- Shared code (`Shared/`) is minimal — genuinely cross-context value objects (e.g., an `Identifier` value-object contract) and integration contracts only, never shared mutable business models.
- Framework models (Eloquent models) are never treated as universal cross-domain objects passed between modules — each module's Infrastructure layer owns its own persistence models.
- Modules communicate through explicit contracts (Application-layer interfaces) or domain events — never through direct cross-module Eloquent relationships.
- Shared utilities (`Support/`) contain no hidden business logic — they are framework glue (base classes, traits for cross-cutting technical concerns) only.

## 3. Command and Query Architecture

### Commands
Represent an intention to change authoritative state. Every command from [workflow-and-command-catalog.md, "Command Candidates"](workflow-and-command-catalog.md#command-candidates) (`CreateMeet`, `ActivateMeet`, `RegisterDelegation`, `RegisterAthlete`, `SubmitEligibilityCase`, `ApproveEligibility`, `RejectEligibility`, `IssueAccreditation`, `RevokeCredential`, `SubmitCompetitionEntry`, `LockEntries`, `GenerateDraw`, `AssignOfficial`, `RecordScore`, `ValidateScore`, `CertifyResult`, `PublishResult`, `FileProtest`, `ResolveProtest`, `RecalculateMedalTally`, `ValidateAccess`, `CloseMeet`) carries through to this layer unchanged — Phase 0.4 does not rename or reinterpret the Phase 0.2 command catalog, it gives each command an execution home.

Every command:
- Has exactly one owning bounded-context module.
- Carries validated input (validation occurs before the command reaches the Domain layer).
- Is authorized per the Phase 0.3 decision sequence before execution (see [runtime-security-architecture.md](runtime-security-architecture.md)).
- Defines its own transaction boundary (Section 4).
- Enforces idempotency where the command may be retried (see [event-and-queue-architecture.md](event-and-queue-architecture.md)).
- Produces an auditable outcome (per the audit-level requirements in [permission-catalog.md](permission-catalog.md)).
- Emits domain events where appropriate (Section 5).

### Queries
Represent read operations and **never mutate authoritative state.** Queries may use context-owned repositories, optimized read models, public projections (BC-29), cached projections, or cross-context reporting models (BC-33) — never a direct write path.

### CQRS Discipline
Command-query separation is used **as a design discipline**, not as a mandate for separate infrastructure (separate databases, event-sourced write models, etc.) for every operation. Most bounded contexts use a single persistence store with commands and queries as distinct code paths against it; only contexts with a genuine, evidenced need for separate read infrastructure (most plausibly BC-29 Public Information and BC-33 Reporting and Analytics, which already have no authoritative data of their own per [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md)) warrant a physically separate read model. **Full CQRS/event sourcing is explicitly not adopted broadly** — see [phase-0.4-application-integration-runtime-architecture.md, Section 50](phase-0.4-application-integration-runtime-architecture.md#50-tradeoffs-and-alternatives).

## 4. Transaction Boundaries

- A transaction normally remains **within one bounded context.** A command that appears to need to touch two contexts' authoritative data in one atomic operation is a signal the boundary needs reconsideration, not a signal to open a cross-context transaction.
- Strong consistency (per [phase-0.2-domain-architecture.md, Section 9](phase-0.2-domain-architecture.md#9-transaction-and-consistency-boundaries)) is required for high-integrity state transitions — these execute within a single-context transaction, synchronously.
- Cross-context workflows use orchestration (Section 6), domain events, or staged processes — never a distributed transaction. PMMS does not adopt two-phase commit or any distributed-transaction protocol across bounded contexts.
- Eventual consistency, where used, is visible and manageable — a consumer of a domain event knows it is reading eventually-consistent data and is designed accordingly (e.g., a read model exposes its own freshness timestamp, per [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)).
- An **outbox-style reliability pattern** should be evaluated for critical event delivery (e.g., `ResultCertified` reliably reaching Medal Tally recalculation) — evaluated in [event-and-queue-architecture.md](event-and-queue-architecture.md), not mandated here as the only acceptable mechanism.
- External side effects (notifications, integrations) occur **after** safe state persistence, never before — a command commits its authoritative state change first, then triggers side effects.
- Retryable operations (any queued job, any command that might be resubmitted after a client timeout) must be idempotent.
- Long-running workflows (e.g., the full eligibility-to-accreditation chain) need explicit process state — they are not implicitly tracked by a chain of fire-and-forget event listeners (Section 6).
- Database transactions must never remain open during a remote network call (an external integration request, an AI service call, an S3/MinIO upload) — the transaction commits or rolls back before any such call begins.

## 5. Domain Event Architecture

- Domain events express **completed business facts**, named in past tense (`ResultCertified`, not `CertifyResult`) — this is the full [domain-events-catalog.md](domain-events-catalog.md) from Phase 0.2, now given an execution mechanism.
- Domain events originate from their owning bounded context only — no context emits an event on another context's behalf.
- Events are **not generic notifications** — a `ResultCertified` event is a business fact regardless of whether anyone is listening; a "please notify the coach" concern is a downstream reaction (via Notifications, BC-31), not the event's own purpose.
- Events must not carry excessive sensitive data — a payload carries identifiers and minimal necessary context (e.g., `ScoreRecorded` carries a score-record ID, not the full participant biographical record), consistent with working rule 28 ("do not place sensitive data in... queue payloads").
- Event consumers never mutate the source aggregate directly — a consumer reacting to `ResultCertified` calls its own context's Application-layer use case (e.g., Medal Tally's `RecalculateMedalTally` command), never reaches back into Official Results' tables.
- Consumers must be idempotent — replaying the same event twice produces the same end state, not a duplicate side effect.
- Critical event delivery (anything feeding a high-integrity workflow) may require persistence or an outbox mechanism so that a crash between "commit state" and "dispatch event" cannot silently drop the event — evaluated in [event-and-queue-architecture.md](event-and-queue-architecture.md).
- Events may trigger projections, notifications, queued work, analytics, and integration messages. Synchronous in-process handling may be used for immediate internal reactions within the same request (e.g., updating an in-transaction cache); asynchronous handling is used for non-critical or expensive work (Section 7 and [phase-0.4-application-integration-runtime-architecture.md, Section 13](phase-0.4-application-integration-runtime-architecture.md#13-synchronous-versus-asynchronous-decisions)).

### Event Type Distinctions

| Event Type | Purpose | Example |
|---|---|---|
| **Domain event** | A completed business fact within a bounded context | `ResultCertified` |
| **Application event** | A technical signal within the Application layer coordinating a use case (e.g., "use case completed," used for internal orchestration bookkeeping) | Not business-meaningful outside the use case itself |
| **Integration event** | A domain event translated for consumption by another bounded context or external system, via an explicit contract | A `ResultCertified` domain event translated into the payload Medal Tally's consumer expects |
| **Real-time broadcast event** | A transient, non-durable message pushed to a Reverb channel for immediate UI update | A live scoreboard tick — see [realtime-architecture.md](realtime-architecture.md) |
| **Audit event** | An immutable record of actor/action/target/reason/timestamp, per [high-integrity-domain-rules.md](high-integrity-domain-rules.md) | Every Critical/Elevated-audit-level command execution |
| **Notification event** | A trigger for a message to a human recipient | `EligibilityRequirementsSubmitted` triggering an in-app notice to the reviewer |

These are related but distinct — a single domain event (`ResultCertified`) may simultaneously trigger an integration event (to Medal Tally), a real-time broadcast (to the live result board), an audit event (recording the certification), and a notification event (to the delegation) — but the domain event itself is none of these; it is the source fact all four derive from.

## 6. Workflow Orchestration

Multi-step workflows that span bounded contexts are coordinated by **Application-layer orchestrators** (process managers), never buried inside an unlabeled chain of event listeners. Named examples requiring explicit orchestration:

| Workflow | Spans | Orchestration Concern |
|---|---|---|
| Eligibility approval → accreditation eligibility | Eligibility (BC-09) → Accreditation (BC-19) | Accreditation must know eligibility is a precondition, not silently poll for it |
| Certified result → medal tally recalculation | Official Results (BC-16) → Medal Tally (BC-18) | Must be reliable (Section 4 outbox consideration) — a missed event means an incorrect public tally |
| Protest filing → result hold | Protest and Appeals (BC-17) → Official Results (BC-16) | Must be synchronous/strong, not eventually consistent (per [phase-0.2-domain-architecture.md, Section 9](phase-0.2-domain-architecture.md#9-transaction-and-consistency-boundaries)) |
| Credential revocation → access denial propagation | Accreditation (BC-19) → Access Validation (BC-20) | Must be prioritized for offline-device sync (per [offline-authorization-model.md](offline-authorization-model.md)) |
| Meet closure → assignment expiration and archival | Meet Administration (BC-04) → all meet-scoped contexts | A wide fan-out requiring explicit process state, not a single unmonitored event cascade |
| Registration acceptance → competition-entry readiness | Athlete Registration (BC-08) → Competition Entries (BC-11) | Gate condition, not automatic entry creation |

Each such workflow's orchestration logic lives in the **downstream or coordinating context's Application layer** (e.g., Medal Tally's Application layer owns the orchestration reacting to `ResultCertified`, not Official Results reaching into Medal Tally). Compensating actions (e.g., reversing a partially-applied fan-out if a later step fails) are designed explicitly per workflow, not assumed to be automatic.

## 7. Synchronous Versus Asynchronous Decisions

**Synchronous** (executes within the request/response cycle, blocking user confirmation until complete):
- Validation required before user confirmation
- High-integrity state transitions (score validation, result certification, eligibility approval, credential revocation, entry locking, assignment activation)
- Authorization decisions
- Critical conflict detection (e.g., schedule/officiating conflicts blocking a save)

**Asynchronous** (queued, per [event-and-queue-architecture.md](event-and-queue-architecture.md)):
- Emails, SMS, push notifications
- Report generation
- Media processing
- Public projection updates where a brief delay is acceptable
- Search indexing
- Analytics
- Data exports
- Non-critical file scanning
- Bulk imports
- Scheduled reconciliation

Where eventual consistency is used, it must be **surfaced to the user** — e.g., a just-certified result may take a few seconds to appear on the public portal, and the administrative UI should not imply instantaneous public visibility when publication is itself a distinct, asynchronously-propagated step (per [domain-open-decisions.md, DD-17](domain-open-decisions.md#dd-17--public-publication-approval-chain)).

## 8. Modular Monolith Rules (Summary)

1. One authoritative module owns each major business concept (inherited directly from [data-ownership-map.md](data-ownership-map.md)).
2. Other modules reference identifiers or approved projections — never a second authoritative copy.
3. No cross-module table mutation.
4. No direct modification of another module's aggregate.
5. Cross-module workflows use application contracts or domain events (Sections 5–6).
6. Shared Kernel remains minimal (Section 2) — mirrors the Phase 0.2 principle that BC-34 Configuration and Reference Data is a versioned Published Language, never a mutable shared table.
7. Module boundaries are testable — a module's Application-layer contract can be tested in isolation with test doubles for its dependencies.
8. Dependencies are directional and documented (Section 1) — no module imports another module's Domain layer directly.
9. Cyclic module dependencies are prohibited.
10. Public projections (BC-29) are downstream consumers only.
11. Reporting modules (BC-33) cannot write back to source domains.
12. Integration adapters cannot bypass Application-layer use cases — an external webhook still calls a Command, it does not write directly to Infrastructure.
13. Jobs and listeners call Application-layer use cases — a queued job is a Delivery-layer entry point, same discipline as a controller.
14. Controllers cannot directly coordinate multiple repositories for a complex workflow — that coordination belongs in the Application layer or an orchestrator (Section 6).
