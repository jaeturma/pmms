# PMMS Risk-Based Testing Model

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../03-security/security-risk-register.md](../03-security/security-risk-register.md) · [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [test-levels-and-test-types.md](test-levels-and-test-types.md)

This document defines PMMS's quality-risk dimensions and the resulting risk classification driving test depth (working rule 26: "Use risk-based testing rather than equal test depth for every feature"). **No numerical risk score is assigned without an approved method**, per the phase's working instructions.

---

## 1. Quality Risk Dimensions

| Dimension | Question |
|---|---|
| Business impact | How much does a defect here disrupt meet operations? |
| Athlete impact | Could a defect here unfairly affect an athlete's competitive outcome? |
| Public trust impact | Would a defect here be visible to and damage confidence among the public/media? |
| Data sensitivity | What classification tier (per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md)) does this area touch? |
| Financial impact | Could a defect here cause a financial loss or accountability failure? |
| Safety impact | Could a defect here affect athlete physical safety (e.g., medical-alert delivery)? |
| Operational disruption | Would a defect here stop a competition or a committee's ability to function? |
| Likelihood of change | How frequently does this area change, and how much does that increase defect risk? |
| Technical complexity | How intricate is the underlying logic (e.g., bracket generation, offline conflict resolution)? |
| Concurrency | How exposed is this area to simultaneous-access defects? |
| Offline dependence | Does this area operate disconnected, adding synchronization risk? |
| External integration | Does this area depend on a third-party service whose behavior PMMS doesn't control? |
| Recoverability | How hard would it be to recover from a defect here after the fact? |
| Detectability | How likely is a defect here to be caught before it causes real harm? |

## 2. Risk Classification (Conceptual, Not Numerically Scored)

| Tier | Meaning |
|---|---|
| **Critical** | High-integrity domains where a defect directly produces an incorrect official outcome, exposes Highly Restricted data, or halts meet operations — requires the fullest test depth across every applicable test type (functional, negative, boundary, concurrency, recovery, audit, authorization). |
| **High** | Significant operational or reputational consequence, but not a direct high-integrity outcome — requires strong functional, negative, and authorization coverage; concurrency/recovery coverage where the area is genuinely exposed to it. |
| **Moderate** | Real but contained consequence, typically recoverable through an ordinary correction workflow — requires solid functional and negative-path coverage; other test types applied selectively. |
| **Low** | Limited consequence, easily detected and corrected, no institutional-trust exposure — requires basic functional coverage; deeper test types are a judgment call, not a mandate. |

**No numerical score (e.g., "risk = impact × likelihood = 12") is assigned to any area in this documentation** — a formal scoring method, if adopted, is a future decision requiring the Quality owner's approval (tracked in [quality-open-decisions.md](quality-open-decisions.md)).

## 3. Illustrative Classification by Area

| Area | Tier | Rationale |
|---|---|---|
| Eligibility, scoring, official results, protests/appeals, medal tally, accreditation | Critical | Direct high-integrity outcomes; restated from [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) and [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) |
| Access validation, medical operations, finance, audit | Critical | Highly Restricted data or direct financial/safety/accountability consequence |
| Authorization (all enforcement points) | Critical | An authorization defect can silently enable any of the above |
| Participant registry, athlete registration, competition entries, tournament progression | High | Foundational to Critical-tier workflows; errors here propagate downstream |
| Offline synchronization, device/credential management | High | Complex, connectivity-dependent, security-relevant |
| Public publication/projections | High | Public-trust-visible, though rebuildable and correctable |
| Committee operations (logistics: billeting, food, transport, ICT) | Moderate | Operationally important, more readily correctable, lower institutional-trust exposure |
| Reporting/analytics (non-public) | Moderate | Derived, rebuildable, internal-facing |
| Notification delivery | Moderate | Important but generally recoverable (resend), rarely safety-critical except medical-alert paths (Critical for that specific path) |
| General administrative screens, reference-data management | Low | Low-consequence, low-complexity, easily corrected |

This table is illustrative, not exhaustive — every new feature is classified against Section 1's dimensions at design time, not assumed to inherit its bounded context's tier automatically without consideration.

## 4. Test-Depth Consequence by Tier

| Tier | Minimum Required Test Types |
|---|---|
| Critical | Functional, negative, boundary, state-transition, authorization, concurrency, recovery, audit, data-integrity — all required, per [test-levels-and-test-types.md, Section 2](test-levels-and-test-types.md#2-test-types) |
| High | Functional, negative, boundary, authorization required; concurrency/recovery applied where the specific feature is genuinely exposed |
| Moderate | Functional, negative required; other types applied selectively based on specific feature characteristics |
| Low | Functional required; other types at engineer/reviewer discretion |

## 5. Relationship to the Security Risk Register

This model is deliberately distinct from, though informed by, [../03-security/security-risk-register.md](../03-security/security-risk-register.md) (Phase 0.6) — that register tracks security-specific threat/vulnerability risk with its own SR-XX IDs; this model classifies **quality/test-depth** risk across all quality dimensions (not only security). A Critical quality-risk area and a tracked SR-XX security risk often overlap (e.g., authorization) but are not the same artifact.

## 6. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably whether a formal numerical risk-scoring method is adopted, and the process for re-classifying an area's risk tier as the system evolves.
