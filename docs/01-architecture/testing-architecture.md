# PMMS Testing Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [laravel-architecture.md](laravel-architecture.md) · [phase-0.3-access-and-assignment-architecture.md, Section 37](phase-0.3-access-and-assignment-architecture.md#37-authorization-testing-strategy) · [separation-of-duties-matrix.md](separation-of-duties-matrix.md)

This document defines future test layers for PMMS, using PestPHP (already present in the repository — Pest 4, per `composer.json`) for the backend. **No test is written in this phase.**

---

## 1. Unit Tests
Domain rules, value objects, domain services, permission decisions, calculations, state transitions. These test the Domain layer (per [laravel-architecture.md, Section 1](laravel-architecture.md#1-laravel-application-layering)) in isolation, with no database, HTTP, or queue involvement.

## 2. Application Tests
Commands, queries, workflows, transaction boundaries, event production, idempotency. These test the Application layer's use cases end-to-end within a module, verifying that a Command produces the correct state change, emits the correct domain event, and respects its transaction boundary (per [laravel-architecture.md, Section 4](laravel-architecture.md#4-transaction-boundaries)).

## 3. Feature Tests
HTTP endpoints, Inertia responses, API contracts, authorization, validation, file uploads. These test the Delivery layer, verifying that a request reaches the correct Application-layer use case, that authorization is correctly enforced (or denied) per [authorization-decision-model.md](authorization-decision-model.md), and that the response shape matches the contract.

## 4. Integration Tests
MySQL, Redis, queue, Horizon, Reverb, MinIO, external adapters. These verify the Infrastructure layer's adapters actually work against real (or realistic test-environment) instances of each dependency, not mocked stand-ins — catching the class of bug that only appears at the boundary between application code and a real external system.

## 5. Contract Tests
Mobile API, Device API, webhooks, external integrations, real-time payloads. These verify that PMMS's API contracts (per [api-and-client-boundaries.md](api-and-client-boundaries.md)) remain stable for existing clients (Flutter app, registered devices) across changes — a contract test failing signals a breaking change requiring a version bump, not a silent client-breaking deploy.

## 6. Frontend Tests
React components, forms, tables, permissions as UI behavior, error states, accessibility. These verify [react-inertia-architecture.md](react-inertia-architecture.md)'s frontend components render correctly and that capability-flag-driven UI behavior (Section 6 of that document) correctly shows/hides based on server-provided flags — never re-testing authorization logic itself, which belongs to Feature Tests.

## 7. Flutter Tests
Use cases, local store, offline queues, sync conflict handling, QR scanning workflow, device binding. These verify [flutter-architecture.md](flutter-architecture.md)'s Application/Domain/Infrastructure layers, with particular attention to the offline record-state machine (Local Draft → Pending Sync → Uploaded → Accepted/Rejected/Conflict → Superseded) and conflict-handling logic from [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md).

## 8. Non-Functional Tests
Load, concurrency, queue throughput, real-time connections, public traffic, security, backup restore, failover, offline recovery. These validate the resilience and performance principles in [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md) under realistic (or synthetically amplified) conditions — most meaningfully executed once a pilot meet has established real traffic/usage baselines, per that document's repeated deference to pilot data over invented targets.

## 9. Traceability

Every test layer above maps directly back to an architectural document produced across Phases 0.2–0.4:

| Test Layer | Verifies |
|---|---|
| Unit | Domain invariants from [high-integrity-domain-rules.md](high-integrity-domain-rules.md) |
| Application | Commands/workflows from [workflow-and-command-catalog.md](workflow-and-command-catalog.md) |
| Feature | Authorization decisions from [authorization-decision-model.md](authorization-decision-model.md), including every scenario in its decision table |
| Integration | Infrastructure adapters from [laravel-architecture.md, Section 1](laravel-architecture.md#1-laravel-application-layering) |
| Contract | API categories from [api-and-client-boundaries.md](api-and-client-boundaries.md) |
| Frontend | UI capability-flag behavior from [react-inertia-architecture.md, Section 6](react-inertia-architecture.md#6-authorization-signal-to-the-frontend) |
| Flutter | Offline record states from [flutter-architecture.md, Section 4](flutter-architecture.md#4-record-states-local) |
| Non-Functional | Resilience/performance principles from [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md) |

This traceability is intended to make future test-writing directly referenceable against a specific architectural requirement, rather than invented ad hoc during implementation.

## 10. High-Priority Test Scenarios (Not Implemented — Named for Later Reference)

Consistent with [phase-0.3-access-and-assignment-architecture.md, Section 37](phase-0.3-access-and-assignment-architecture.md#37-authorization-testing-strategy), the following categories are the highest-value future tests given PMMS's high-integrity domains:

- Every entry in [separation-of-duties-matrix.md](separation-of-duties-matrix.md) (SOD-01 through SOD-11) has a corresponding "this combination must be rejected" test.
- Every "never final offline" action in [offline-authorization-model.md, Section 8](offline-authorization-model.md#8-actions-requiring-server-confirmation-never-final-offline) has a corresponding "this cannot be finalized without server confirmation" test.
- Cross-meet and cross-organization isolation (per [scope-model.md, Sections 8–9](scope-model.md#8-cross-meet-isolation)) has explicit "no bleed-through" tests.
- Every high-integrity domain in [high-integrity-access-controls.md](high-integrity-access-controls.md) has a corresponding "correction supersedes, never overwrites" test.

## 11. Open Questions

- CI pipeline introduction timing — explicitly deferred (working rule 9, "do not create GitHub Actions").
- Test-data management strategy (factories, seeders for test purposes only — distinct from any production seeding, which remains out of scope entirely per working rule 6).
- Non-functional test tooling selection — later implementation-phase decision.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
