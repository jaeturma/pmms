# PMMS Final Architecture Decision Register

**Status:** Draft Complete — Pending Formal Architecture Review and Stakeholder Sign-Off
**Related:** All 12 ADRs in [.ai/decisions/](../../.ai/decisions/)

This register consolidates every major architectural decision from ADR-0001 through ADR-0012, classified as Accepted, Accepted with Conditions, Unresolved, Superseded, Deferred, or Rejected — as of this Phase 0.13 review.

---

## Accepted Decisions (No Material Condition)

| Decision | Source | Status |
|---|---|---|
| Domain-oriented modular monolith | [ADR-0002](../../.ai/decisions/ADR-0002-domain-and-bounded-context-architecture.md) | Accepted, confirmed unchanged through Phase 0.12 |
| Hybrid RBAC+ABAC+scope+assignment authorization | [ADR-0003](../../.ai/decisions/ADR-0003-role-permission-scope-and-assignment-architecture.md) | Accepted |
| Layered application architecture (Domain/Application/Infrastructure/Delivery) | [ADR-0004](../../.ai/decisions/ADR-0004-application-integration-and-runtime-architecture.md) | Accepted |
| MySQL sole authoritative store; Redis/Reverb transient | [ADR-0005](../../.ai/decisions/ADR-0005-data-database-and-information-lifecycle-architecture.md) | Accepted, most consistently reinforced rule in the architecture |
| Compliance-language discipline (no compliance claimed without evidence) | [ADR-0006](../../.ai/decisions/ADR-0006-security-privacy-audit-compliance-and-data-governance.md) | Accepted |
| Risk-based testing model | [ADR-0007](../../.ai/decisions/ADR-0007-quality-engineering-testing-validation-and-assurance.md) | Accepted |
| Pilot-scale starting topology with extraction readiness | [ADR-0008](../../.ai/decisions/ADR-0008-devops-environment-cicd-deployment-and-operations.md) | Accepted |
| PMMS Arena design system, frontend-hiding-never-authorization | [ADR-0009](../../.ai/decisions/ADR-0009-design-system-ux-accessibility-and-cross-platform-experience.md) | Accepted |
| AI Gateway pattern, human-in-the-loop authority model | [ADR-0010](../../.ai/decisions/ADR-0010-ai-assisted-platform-architecture.md) | Accepted |
| Process-manager/state-machine workflow architecture | [ADR-0011](../../.ai/decisions/ADR-0011-event-workflow-notification-messaging-and-automation.md) | Accepted |
| Evidence-gated enterprise maturity model, defense-in-depth tenant isolation | [ADR-0012](../../.ai/decisions/ADR-0012-performance-scalability-multitenancy-disaster-recovery-enterprise-readiness.md) | Accepted |

## Accepted With Conditions

| Decision | Source | Condition |
|---|---|---|
| Product identity and vision | [ADR-0001](../../.ai/decisions/ADR-0001-product-foundation.md) | Conditional on OD-02/OD-03 (organizational and meet-launch scope) resolution before Phase 1 scope is finalized |
| Physical schema deferred to implementation | Implied by [ADR-0005](../../.ai/decisions/ADR-0005-data-database-and-information-lifecycle-architecture.md) | Condition: must be resolved as Phase 0.14's explicit first work package (GAP-01), not deferred further |

## Unresolved Decisions (Highest Priority, Cross-Referenced)

See [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md) for the full DR-01 through DR-15 list. The most consequential: DR-01/03/04/05/06 (OD-07/09/10/12/15 cluster), DR-09 (DD-01), DR-11 (DV-01), DR-12 (RPO/RTO chain), DR-13 (WD-08 outbox), DR-14 (DX-01).

## Superseded Decisions

**None found.** No architectural decision from any phase was superseded by a later phase — every subsequent phase extended, never contradicted or replaced, an earlier phase's decision. This is itself a notable finding, restated from [architecture-consistency-and-contradiction-analysis.md](architecture-consistency-and-contradiction-analysis.md): zero contradictions were found across 12 phases of architecture.

## Deferred Decisions (Explicitly, by Design)

| Decision Area | Deferred Until |
|---|---|
| Kubernetes adoption | Measured justification and demonstrated operational capability |
| Database sharding | Stage 6, tenant extraction maturity |
| Multi-region active-active | Stage 5/6, specific data-residency or latency need |
| Dedicated search engine | Demonstrated MySQL-search insufficiency |
| Vector database / AI embeddings | Any AI capability's actual approval (none approved) |
| Data warehouse | Real reporting-need evidence ([DD-25](../01-architecture/domain-open-decisions.md)) |
| SSO/enterprise identity | Stage 5, contractual/enterprise customer need |
| Billing/licensing platform | OD-22 resolution and Stage 5 commercial need |
| Generic workflow engine | Demonstrated insufficiency of hand-rolled state machines |
| Saga framework | Demonstrated cross-context compensation complexity beyond the six named process managers |

## Rejected Options (Consolidated From Every Phase's "Alternatives Considered")

| Rejected Option | Source Phase | Reason |
|---|---|---|
| Microservices from the start | 0.2 | Premature distributed-systems complexity for current team size |
| Full CQRS/event sourcing broadly | 0.4 | Not justified by current consistency requirements |
| Kubernetes as default | 0.8, 0.12 | No measured justification |
| Database-per-tenant initially | 0.12 | Disproportionate cost at current single-organization scale |
| Claiming compliance without validation | 0.6 | Directly contradicts the compliance-language discipline |
| Auto-applying AI recommendations for "low-risk" cases | 0.10, 0.11 | No exception carved from the absolute AI-authority boundary |
| Redefining prior-phase catalogs instead of extending them | 0.9–0.12 (working rule 4 in each) | Would duplicate and risk contradicting existing decisions |

## Summary

Twelve ADRs, zero superseded, two conditionally accepted, the remainder fully accepted subject to the unresolved decisions tracked in [contradiction-and-decision-resolution-register.md](contradiction-and-decision-resolution-register.md). This is the register [phase-0-final-architecture-signoff.md](phase-0-final-architecture-signoff.md) draws from directly.
