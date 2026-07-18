# ADR-0007: Quality Engineering, Testing, Validation, and Assurance Architecture

## Status

Accepted (as a Phase 0.7 quality-engineering-architecture decision; pending formal quality, engineering, security, domain, and stakeholder sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 through ADR-0006 established PMMS's bounded contexts, authorization model, runtime architecture, data/persistence architecture, and security/privacy/audit/governance architecture. None of them specified how the platform actually proves any of these promises hold — what test depth a Critical-tier eligibility decision requires versus a Low-tier administrative screen, how a sport-specific expected outcome is sourced rather than guessed, what distinguishes an automated test passing from a Tournament Manager actually recognizing their real workflow in the software, or what a release genuinely requires beyond "the tests are green."

Left unspecified, this gap risks two distinct failure modes. First, the same "34 modules inventing their own conventions independently" pattern every prior phase's architecture work exists to prevent — now expressed as inconsistent test depth, ad hoc coverage targets, and no shared vocabulary for what "well-tested" means. Second, and more consequential for an institutional platform: a temptation to treat automated verification as sufficient on its own, when PMMS's actual correctness depends on domain knowledge (sports rules, committee operational practice, usability under real field conditions) that no unit test can encode and no engineer should invent unilaterally.

## Decision

PMMS will use a **risk-based, four-quadrant quality architecture combining automated verification, human validation, controlled pilot testing, and operational readiness assessment — preserving every bounded-context, authorization, runtime, data, and security/privacy/audit/governance rule established in ADR-0002 through ADR-0006 unchanged, and treating every sport-specific or policy-dependent expected test outcome as pending an approved source rather than an engineering guess.**

Specifically:

1. **Test depth is risk-based, not uniform.** Critical-tier domains (eligibility, scoring, official results, protests/appeals, medal tally, accreditation, access validation, medical, finance, audit, authorization) require the fullest test depth — functional, negative, boundary, state-transition, authorization, concurrency, recovery, audit, and data-integrity testing, all required, not a subset. Lower-risk areas receive proportionately less investment.
2. **Verification and validation are distinct and both mandatory.** Automated tests, static analysis, and contract checks (verification) answer whether PMMS was built to specification; tournament-manager review, technical-official validation, committee workflow simulation, pilot testing, and UAT (validation) answer whether PMMS solves the real operational problem. Neither substitutes for the other, and a release cannot rely on verification evidence alone.
3. **The test pyramid is deliberately non-inverted.** A large domain/unit-test base, a strong application/feature layer, focused integration/contract tests, and selective end-to-end tests — explicitly avoiding a browser-test-dominated shape given the fragility that would introduce against PMMS's offline-sync, real-time-broadcast, and multi-role-authorization complexity.
4. **No sport-specific rule, scoring formula, eligibility rule, protest deadline, or medal rule is ever invented for a test.** Every such expected outcome in [../../docs/04-quality/high-integrity-sports-workflow-testing.md](../../docs/04-quality/high-integrity-sports-workflow-testing.md) is marked pending an approved rule source and requires Sports-rule validator confirmation before being trusted for testing or release decisions.
5. **Coverage is evidence, not proof, and is never the sole quality metric.** A multi-dimensional coverage strategy (code, domain-rule, workflow, state-transition, permission, scope, classification, error-path, concurrency, rule-version, browser/device, requirement) is used jointly; no single percentage defines "well-tested."
6. **Test data is always synthetic.** No real minor-athlete, medical, eligibility, guardian, finance, authentication, or audit data is ever used in a lower environment, under any circumstance — restated absolutely, with no masking-based exception for these specific categories.
7. **A release requires more than passing tests.** Release readiness combines automated-gate satisfaction, UAT completion, and (where required) pilot sign-off, with explicit, owned, time-bounded exception management for any accepted gap — no single person unilaterally waives all critical quality controls.
8. **Compliance evidence is never presented as a compliance claim.** A passing test for a control in [../../docs/03-security/compliance-control-framework.md](../../docs/03-security/compliance-control-framework.md) demonstrates that control functions — it never asserts or implies PMMS is compliant with any law, regulation, or standard, extending the compliance-language discipline established in ADR-0006.

**Explicitly not decided by this ADR:** frontend and Flutter test-framework selection, CI platform and pipeline configuration, specific coverage-percentage thresholds, defect-tracking tooling, mutation-testing adoption, data-migration testing scope (pending confirmation of migration necessity itself), numeric performance/load targets, pilot meet selection and timing, and accessibility-conformance target level.

## Rationale

- **Preserves every prior ADR's guarantees at the layer where they are actually proven, not merely documented.** An authorization model, a versioned data model, and an audit architecture are only as trustworthy as the tests confirming they behave as designed under negative, concurrent, and adversarial conditions — this ADR is where that proof obligation is made explicit and structured.
- **Prevents 34 independently-evolving modules from inventing 34 different definitions of "tested."** Centralizing risk classification, test-level/type vocabulary, and acceptance-criteria standards now is far cheaper than reconciling inconsistent quality practices after implementation has scaled.
- **Protects PMMS's institutional trust at the exact point automated testing alone cannot reach.** A technically-passing bracket-generation algorithm that no Tournament Manager recognizes as operationally sound, or a scoring precision test built on an invented rather than sourced rule, is precisely the failure mode human validation and sourced-rule discipline exist to prevent.
- **Matches quality investment to actual consequence.** Uniform test depth across a Critical-tier medal-tally calculation and a Low-tier reference-data screen would either under-protect the former or waste effort on the latter; risk-based testing avoids both.
- **Avoids the specific institutional risk of conflating verification with compliance.** A test suite passing is evidence a control works, never a substitute for the verified policy sourcing and legal review a genuine compliance claim requires — this ADR extends, rather than weakens, the discipline ADR-0006 established.

## Approved Quality Engineering Direction

> Risk-based test depth across a non-inverted pyramid; verification and human validation treated as distinct, jointly-required activities; every sport-specific or policy-dependent test outcome sourced, never invented; synthetic test data always; multi-dimensional coverage never reduced to one percentage; and release readiness requiring evidence beyond a passing test suite.

## Test-Depth Rule (New in This Phase)

Every Critical-tier domain's implementation requires domain/application-layer tests as primary evidence — a Feature-level HTTP test alone is insufficient for a Critical-tier rule. Every permission requires both positive and negative test coverage; every SoD conflict requires a negative test confirming rejection.

## High-Integrity Sports Rule Sourcing Rule (New in This Phase)

No engineer, and no AI-assisted feature, authors an expected test outcome for a sport-specific rule, scoring formula, eligibility threshold, protest deadline, or medal-award rule. Every such value is sourced from an approved authority and confirmed by the Sports-rule validator role before use in any test or golden dataset.

## Test-Data Rule (Carried Forward from ADR-0005/0006, Made Absolute for Testing)

No real minor-athlete, medical, eligibility, guardian, finance, authentication, or audit data appears in any lower environment. This extends, without exception, the test-and-lower-environment governance already established in ADR-0006.

## Release-Readiness Rule (New in This Phase)

A release decision requires evidence from all four quadrants (technology-facing verification, business-facing verification, business-facing validation, technology-facing critique) — never verification evidence alone. Critical-tier gate exceptions require named concurrence from the relevant Security/Privacy/Domain reviewer, never a single approver's unilateral judgment.

## Consequences

**Positive:**
- Phase 0.8 (the first implementation-adjacent phase) inherits a complete risk model, test-level/type taxonomy, acceptance-criteria standard, and release-readiness framework, and can begin writing tests against known, consistent expectations rather than inventing conventions per feature.
- High-integrity domains have their test-evidence obligations named before any implementation exists to under-test them.
- The sports-rule-sourcing discipline prevents a specific, high-consequence failure mode: an engineer's plausible-but-wrong guess at a scoring or medal rule silently becoming the de facto standard through an unreviewed test's expected value.

**Negative / trade-offs:**
- Risk-based, non-uniform test depth requires ongoing classification judgment (is this feature Critical, High, Moderate, or Low?) rather than a simple "test everything equally" rule — accepted because uniform depth would either under-protect Critical-tier domains or waste effort broadly.
- Requiring both verification and human validation for every meaningful release adds real coordination overhead (scheduling Tournament Manager/Technical Official review time) — accepted because automated testing alone cannot answer the questions validation is specifically designed to answer.
- A significant number of decisions remain open (24 items in [../../docs/04-quality/quality-open-decisions.md](../../docs/04-quality/quality-open-decisions.md)), several structurally blocked on real-world data (pilot scheduling, load-model inputs) this documentation-only phase cannot itself produce.

## Alternatives Considered

1. **Defer all quality-engineering architecture until implementation begins, relying on ad hoc per-feature testing decisions.** Rejected — the most direct path to inconsistent test depth and, specifically, to an engineer inventing a sports rule under delivery pressure rather than sourcing one.
2. **Require 100% code coverage as a hard release gate.** Rejected — directly contradicts the "coverage is evidence, not proof" principle and working rule 28; a coverage-percentage mandate incentivizes trivial tests over meaningful negative/concurrency/authorization coverage.
3. **Treat automated test-suite pass as sufficient release evidence, skipping formal UAT/pilot validation.** Rejected — collapses the verification/validation distinction this ADR establishes as load-bearing; automated tests cannot confirm sports-rule correctness, usability, or operational readiness.
4. **Build a heavily browser/end-to-end-test-dominated suite for confidence, given PMMS's complex multi-role workflows.** Rejected — the resulting inverted pyramid would be slow and brittle precisely where PMMS's complexity (offline sync, real-time broadcast) makes end-to-end tests least reliable; a strong domain/application/feature base is prioritized instead.
5. **Allow individual approvers to waive quality gates unilaterally for expediency.** Rejected — per working rule ("no single person should unilaterally waive all critical quality controls"), Critical-tier exceptions require named multi-role concurrence.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated Quality owner, QA lead, technical lead, security reviewer, privacy reviewer, data reviewer, DevOps reviewer, Sports-rule validator, and DepEd Leadership, per [../../docs/04-quality/README.md, "Ownership and Review Expectations"](../../docs/04-quality/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.7 open decisions, per [../../docs/04-quality/quality-open-decisions.md, "Summary of Blocking / High-Priority Quality Decisions"](../../docs/04-quality/quality-open-decisions.md#summary-of-blocking--high-priority-quality-decisions) — notably QD-01/QD-02 (frontend/Flutter test tooling) and QD-09 (data-migration testing scope).
- Continued resolution of the Phase 0.1 policy decisions this ADR's high-integrity sports-workflow test-sourcing rules depend on (eligibility authority, result approval chain, protest authority, medal tally rules, medical-data handling), and the inherited Phase 0.6 open decisions (SD-13, SD-15, SD-16) this phase's security-testing operationalization depends on.

## Related Documents

- [../../docs/04-quality/phase-0.7-quality-engineering-testing-validation-assurance.md](../../docs/04-quality/phase-0.7-quality-engineering-testing-validation-assurance.md)
- [../../docs/04-quality/quality-engineering-strategy.md](../../docs/04-quality/quality-engineering-strategy.md)
- [../../docs/04-quality/risk-based-testing-model.md](../../docs/04-quality/risk-based-testing-model.md)
- [../../docs/04-quality/high-integrity-sports-workflow-testing.md](../../docs/04-quality/high-integrity-sports-workflow-testing.md)
- [../../docs/04-quality/test-data-fixture-and-scenario-strategy.md](../../docs/04-quality/test-data-fixture-and-scenario-strategy.md)
- [../../docs/04-quality/release-readiness-and-quality-signoff.md](../../docs/04-quality/release-readiness-and-quality-signoff.md)
- [../../docs/04-quality/quality-open-decisions.md](../../docs/04-quality/quality-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../testing-strategy.md](../testing-strategy.md)
- [../quality-rules.md](../quality-rules.md)
- [../test-data-rules.md](../test-data-rules.md)
- [../acceptance-rules.md](../acceptance-rules.md)
- [../release-quality-rules.md](../release-quality-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
- [ADR-0004-application-integration-and-runtime-architecture.md](ADR-0004-application-integration-and-runtime-architecture.md)
- [ADR-0005-data-database-and-information-lifecycle-architecture.md](ADR-0005-data-database-and-information-lifecycle-architecture.md)
- [ADR-0006-security-privacy-audit-compliance-and-data-governance.md](ADR-0006-security-privacy-audit-compliance-and-data-governance.md)
