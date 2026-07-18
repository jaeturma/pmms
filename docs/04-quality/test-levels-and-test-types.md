# PMMS Test Levels and Test Types

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [quality-engineering-strategy.md](quality-engineering-strategy.md) · [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md) · [risk-based-testing-model.md](risk-based-testing-model.md)

This document defines the 12 test levels and 21 test types PMMS's quality architecture uses, extending the 8-layer test architecture from [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md) rather than replacing it. **No test framework configuration or test code is created here.**

---

## 1. Test Levels

| Level | Boundary | Ownership |
|---|---|---|
| Unit | A single class/function in isolation | Implementing engineer |
| Component | A small cluster of tightly-related units | Implementing engineer |
| Domain | An aggregate, value object, or domain service, independent of Laravel infrastructure where practical | Implementing engineer, reviewed by domain owner |
| Application | A command/query use case, including orchestration and authorization coordination | Implementing engineer |
| Feature | Full Laravel HTTP request/response behavior | Implementing engineer, reviewed by technical lead |
| Integration | Real interaction with MySQL/Redis/MinIO/Reverb/queue infrastructure | Implementing engineer + QA lead |
| Contract | Schema/interface compatibility across a system boundary (API, webhook, mobile) | QA lead + the boundary's owner |
| System | The full PMMS system operating together, excluding external validation | QA lead |
| End-to-end | A full user journey across UI, backend, and (where relevant) mobile | QA lead, selectively |
| Acceptance | Human business-stakeholder confirmation the built system meets the real need | UAT coordinator |
| Pilot | Controlled real-world operation during an actual or simulated meet | Pilot coordinator |
| Operational | Go-live infrastructure, process, and support readiness | DevOps reviewer + Quality owner |

This extends, and is fully consistent with, the 8 layers already named in [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md) (Unit, Application, Feature, Integration, Contract, Frontend, Flutter, Non-Functional) — Phase 0.7 adds System, End-to-end, Acceptance, Pilot, and Operational as levels above that architecture's original scope, and splits "Non-Functional" into its constituent test types (Section 2).

## 2. Test Types

| Type | Verifies |
|---|---|
| Functional | The system does what it's specified to do, for valid input |
| Negative | The system correctly rejects/handles invalid input or unauthorized attempts |
| Boundary | Behavior at the edges of a valid range (e.g., minimum/maximum score value) |
| State-transition | A resource only moves through its valid states in the valid order (per [../02-data/temporal-history-and-versioning-model.md, Section 1](../02-data/temporal-history-and-versioning-model.md#1-state-and-status-persistence)) |
| Authorization | Permission + Scope + Assignment + Resource State + Data Classification + SoD + Device Trust + Time Validity + Explicit Restrictions is correctly evaluated |
| Data-integrity | Referential integrity, versioning, and correction-not-overwrite discipline hold |
| Concurrency | Simultaneous access produces correct, non-corrupting outcomes |
| Idempotency | A retried operation does not duplicate its effect |
| Recovery | The system returns to a correct state after a failure or interruption |
| Compatibility | The system behaves correctly across supported browsers/devices/networks |
| Accessibility | The system is usable per [accessibility-usability-and-compatibility-testing.md](accessibility-usability-and-compatibility-testing.md) |
| Security | The system resists the threats named in [../03-security/threat-model.md](../03-security/threat-model.md) |
| Privacy | Personal data is handled per [../03-security/privacy-by-design-architecture.md](../03-security/privacy-by-design-architecture.md) |
| Performance | The system meets responsiveness expectations under expected load |
| Resilience | The system degrades gracefully and recovers from component failure |
| Usability | Real users can accomplish their task effectively |
| Exploratory | Unscripted, skilled investigation surfaces defects scripted tests miss |
| Regression | Previously-working behavior remains correct after a change |
| Smoke | Critical-path availability is confirmed before deeper testing proceeds |
| Sanity | A narrow, quick check that a specific fix/change behaves as intended |
| Acceptance | The business-facing outcome is confirmed correct by a human stakeholder |

## 3. Test-Case and Test-Scenario Architecture

- **A test scenario** describes a business situation to be verified (e.g., "a scorer attempts to validate their own submitted score") — traceable to an Acceptance Criterion (Section, [requirements-traceability-model.md](requirements-traceability-model.md)).
- **A test case** is the specific, concrete instantiation of a scenario (specific input values, specific expected output) — one scenario may generate several test cases (e.g., boundary values).
- **A test suite** groups related test cases by test level and/or bounded context, mirroring the module structure in [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md) — one suite per bounded-context module is the anticipated default organization, adjusted where a cross-cutting concern (e.g., authorization) warrants its own suite.

**No test-case template, ID scheme, or management tool is selected here** — this is the conceptual model; a specific implementation (e.g., Pest test file organization) is a Phase 0.8+ decision.

## 4. Distinguishing Functional Quality from Operational Readiness

A system can be functionally correct (every test type above passing) while still not being operationally ready (staff untrained, devices unregistered, backups unverified, support contacts unconfirmed) — restated from working rule 38. [pilot-operational-and-stakeholder-validation.md, Section 2](pilot-operational-and-stakeholder-validation.md#2-operational-readiness-testing) is where operational readiness is validated as its own, distinct concern.

## 5. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably the specific test-suite directory/naming convention for Pest (a Phase 0.8+ implementation decision, anticipated but not decided here).
