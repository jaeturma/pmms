# EPIC-02 — Modular Monolith and Application Architecture Foundation

**Phase:** Phase 1 — Core Foundation Implementation
**Release Group:** Foundation Release A
**Status:** Planned — Not Started

## Purpose

Create the foundation for bounded-context-oriented implementation without premature microservices or a generic workflow engine. Every domain-bearing epic (03–09) builds inside the namespace, shared-kernel, command/query, event, persistence, and error-handling conventions this epic establishes.

## Architecture Sources

[../../../../01-architecture/](../../../../01-architecture/), ADR-0002, ADR-0004.

## Work Packages

| ID | Title | Complexity | Priority |
|---|---|---|---|
| [WP-02-01](WP-02-01-modular-monolith-directory-and-namespace-foundation.md) | Modular Monolith Directory and Namespace Foundation | Medium | P1 |
| [WP-02-02](WP-02-02-shared-kernel-and-common-contracts-foundation.md) | Shared Kernel and Common Contracts Foundation | Medium | P1 |
| [WP-02-03](WP-02-03-application-command-and-query-conventions.md) | Application Command and Query Conventions | Medium | P1 |
| [WP-02-04](WP-02-04-domain-event-and-integration-contract-conventions.md) | Domain Event and Integration Contract Conventions | Medium | P1 |
| [WP-02-05](WP-02-05-repository-transaction-and-persistence-conventions.md) | Repository, Transaction, and Persistence Conventions | Medium | P1 |
| [WP-02-06](WP-02-06-architecture-dependency-rules-and-fitness-test-readiness.md) | Architecture Dependency Rules and Fitness-Test Readiness | Medium | P1 |
| [WP-02-07](WP-02-07-error-handling-and-exception-translation-foundation.md) | Error Handling and Exception Translation Foundation | Small | P1 |
| [WP-02-08](WP-02-08-correlation-and-request-context-foundation.md) | Correlation and Request Context Foundation | Small | P1 |

## Dependencies

WP-01-01 (Hard).

## Completion Outcome

A namespace/directory convention, shared-kernel contracts, command/query/event conventions, and architecture-fitness-test readiness that EPIC-03 through EPIC-14 build inside.

## Deferred Items

Executable fitness-test tooling selection (deptrac/PHPStan custom rules) remains a WP-02-06 open decision, not a hard blocker.

## Risks

RISK-EPIC02-01 — namespace convention chosen too early could require rework once the first real bounded context (EPIC-04) is implemented.
