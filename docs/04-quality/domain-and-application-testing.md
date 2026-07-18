# PMMS Domain and Application-Service Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md) · [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [test-levels-and-test-types.md](test-levels-and-test-types.md)

This document defines domain-layer and application-service-layer testing requirements, mapping directly onto the Domain/Application layering from [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md). **No domain class, command, or test code is created here.**

---

## 1. Domain Testing

Every bounded context's Domain layer requires tests covering:

| Target | What to Verify |
|---|---|
| Aggregates | The aggregate enforces its own invariants regardless of how it's invoked |
| Value objects | Equality, immutability, and validation behave correctly |
| Domain services | Cross-aggregate domain logic within a single bounded context behaves correctly |
| Invariants | A business rule that must always hold (e.g., "a certified result always references a validated score set") cannot be violated through any code path |
| State transitions | Only valid transitions succeed; every invalid transition is rejected |
| Rejections | An explicitly rejected/denied outcome (e.g., an eligibility rejection) is captured with its required reason |
| Corrections | A correction produces a new version referencing its predecessor — never a destructive overwrite, per [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) |
| Supersession | An old version is correctly marked superseded, never silently discarded |
| Versioning | Version identity, current/previous tracking, and effective period behave per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession) |
| Effective dates | Time-bound validity (e.g., an assignment's effective period) is correctly evaluated |
| Rule-set version changes | A change to a referenced sports/eligibility rule-set version does not retroactively alter a historical decision made under a prior version |
| Domain events | The correct domain event is raised for a given state change, with correct payload |

**Domain tests should remain fast and independent from Laravel infrastructure where practical** — a domain test that requires a database connection, an HTTP request, or a queued job to run is a candidate for refactoring toward a genuinely isolated test, not accepted as the default.

## 2. Application-Service Testing

Every bounded context's Application layer (Commands and Queries) requires tests covering:

| Target | What to Verify |
|---|---|
| Commands | State-changing operations correctly orchestrate validation, authorization, and domain-layer invocation |
| Queries | Read operations never mutate state and correctly apply query-level authorization filtering |
| Use-case orchestration | A multi-step use case (e.g., "certify a result") correctly sequences its steps and fails safely if any step fails |
| Transactions | A command's database operations are correctly wrapped in a transaction — a partial failure leaves no inconsistent state |
| Authorization coordination | The command correctly invokes the centralized authorization decision (per [../03-security/authorization-and-privileged-access-assurance.md](../03-security/authorization-and-privileged-access-assurance.md)) before proceeding |
| Idempotency | A retried command (e.g., a duplicate submission from a flaky network) does not duplicate its effect, per [../02-data/offline-sync-and-conflict-data-model.md, Section 4](../02-data/offline-sync-and-conflict-data-model.md#4-idempotency-data) |
| Event emission | The correct domain/application/integration event is emitted after a successful command, per [../01-architecture/laravel-architecture.md, Section 5](../01-architecture/laravel-architecture.md#5-domain-event-architecture) |
| Failure behavior | A failed command leaves the system in a well-defined, recoverable state — never a silent partial success |
| Cross-context orchestration | A command spanning multiple bounded contexts uses an approved integration pattern (per [../01-architecture/internal-integration-architecture.md](../01-architecture/internal-integration-architecture.md)), never a direct cross-context write |
| Retry-safe operations | Operations expected to be retried (queued jobs, sync operations) behave correctly under retry |
| Audit creation | Every command that should produce an audit event (per [../03-security/audit-and-security-event-architecture.md, Section 2](../03-security/audit-and-security-event-architecture.md#2-audit-event-categories)) actually does |
| Projection-trigger behavior | A command that should trigger a public-projection or read-model rebuild correctly does so, per [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) |

## 3. High-Assurance Domain and Application Test Emphasis

For the Critical-tier domains named in [risk-based-testing-model.md, Section 3](risk-based-testing-model.md#3-illustrative-classification-by-area) — eligibility, scoring, official results, protests, medal tally, accreditation, access validation, medical, finance, audit, authorization — domain and application tests are the primary verification layer, since these are exactly the layers where the invariant and orchestration logic protecting the platform's institutional trust actually lives. A Feature-level HTTP test alone is insufficient evidence for a Critical-tier rule; the underlying domain/application test must exist and pass independently.

## 4. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably whether domain tests are organized per bounded-context module folder or per a cross-cutting domain-test suite.
