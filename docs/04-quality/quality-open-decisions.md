# PMMS Quality Engineering, Testing, Validation, and Assurance — Open Decisions

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [quality-engineering-strategy.md](quality-engineering-strategy.md) · [../03-security/security-open-decisions.md](../03-security/security-open-decisions.md) · [../02-data/data-open-decisions.md](../02-data/data-open-decisions.md)

This document tracks every unresolved Phase 0.7 decision using Decision ID prefix `QD-` (Quality Decision), distinct from Phase 0.1's `OD-`, Phase 0.2's `DD-`, Phase 0.3's `AD-`, Phase 0.4's `RD-`, Phase 0.5's `PD-`, and Phase 0.6's `SD-` series. Each entry follows the established format: Question / Areas Affected / Why It Matters / Options / Recommended Direction / Evidence Required / Decision Owner / Target Phase / Status.

---

### QD-01 — Frontend Test Framework Selection

- **Question:** Which test framework (Vitest, Jest, Testing Library, Playwright, Cypress) is adopted for React/TypeScript testing?
- **Areas affected:** [laravel-inertia-react-testing.md, Section 4](laravel-inertia-react-testing.md#4-frontend-test-framework-not-yet-selected)
- **Why it matters:** No frontend test framework currently exists in `package.json` (confirmed during this phase's inspection) — React component and Inertia-page testing cannot begin without one.
- **Options:** Vitest (natural fit given the existing Vite-based build) + Testing Library for component tests, plus Playwright for selective end-to-end tests.
- **Recommended direction:** Vitest + Testing Library as the primary combination, given tooling alignment with the existing Vite build; Playwright evaluated specifically for the "selective end-to-end tests" layer in the test pyramid.
- **Evidence required:** Technical-lead tooling evaluation.
- **Decision owner:** Technical lead
- **Target phase:** 0.8 (implementation)
- **Status:** Open

### QD-02 — Flutter Test Tooling and Structure

- **Question:** What is the `mobile/` directory's test structure and tooling, once the Flutter app is scaffolded?
- **Areas affected:** [flutter-mobile-device-and-offline-testing.md](flutter-mobile-device-and-offline-testing.md)
- **Why it matters:** `mobile/` does not yet exist (confirmed during this phase's inspection) — this documentation anticipates its structure without a concrete app to test.
- **Options:** Standard Flutter `test/` directory conventions (widget tests, unit tests) plus `integration_test/` for device-level scenarios.
- **Recommended direction:** Standard Flutter conventions, mirroring the Pest/PHPUnit organizational pattern already established for the backend.
- **Evidence required:** Flutter-app scaffolding decision (a Phase 0.8+ prerequisite).
- **Decision owner:** Technical lead
- **Target phase:** 0.8+ (once `mobile/` is scaffolded)
- **Status:** Open

### QD-03 — CI Platform and Pipeline Configuration

- **Question:** Which CI platform runs the automation suites in [automation-ci-and-quality-gates.md](automation-ci-and-quality-gates.md)?
- **Areas affected:** [automation-ci-and-quality-gates.md](automation-ci-and-quality-gates.md)
- **Why it matters:** No CI configuration currently exists (the prior `.github/workflows/` was removed before this session, unrelated to Phase 0.7).
- **Options:** GitHub Actions (natural fit given the confirmed GitHub direction) vs. an alternative CI platform.
- **Recommended direction:** GitHub Actions, consistent with the approved technology direction naming GitHub explicitly — final configuration is out of scope for this documentation-only phase.
- **Evidence required:** DevOps-phase tooling decision.
- **Decision owner:** DevOps reviewer
- **Target phase:** 0.8+ or a dedicated infrastructure phase
- **Status:** Open

### QD-04 — Coverage-Reporting Thresholds Per Risk Tier

- **Question:** What specific coverage percentage (if any) is expected per risk tier?
- **Areas affected:** [quality-metrics-reporting-and-evidence.md, Section 2](quality-metrics-reporting-and-evidence.md#2-coverage-strategy)
- **Why it matters:** Coverage is deliberately not the sole quality metric, but some baseline expectation still needs to exist for CI gating purposes.
- **Options:** No blocking threshold (report only) vs. a minimum threshold for Critical-tier modules specifically.
- **Recommended direction:** Report-only initially; introduce a Critical-tier minimum once a baseline suite exists to measure against.
- **Evidence required:** Baseline coverage measurement once Critical-tier domain/application tests exist.
- **Decision owner:** Quality owner
- **Target phase:** 0.8+
- **Status:** Open

### QD-05 — Risk-Scoring Methodology

- **Question:** Is a formal numerical risk-scoring method adopted for [risk-based-testing-model.md](risk-based-testing-model.md)?
- **Areas affected:** [risk-based-testing-model.md, Section 2](risk-based-testing-model.md#2-risk-classification-conceptual-not-numerically-scored)
- **Why it matters:** Mirrors [../03-security/security-open-decisions.md, SD-13](../03-security/security-open-decisions.md#sd-13--risk-scoring-methodology) — a consistent method would allow more precise test-depth prioritization across both quality and security risk.
- **Options:** Continue with qualitative Critical/High/Moderate/Low tiers vs. adopt a shared quantitative method with the Phase 0.6 security risk register.
- **Recommended direction:** Align with whatever SD-13 resolves to, rather than adopting a separate method — quality risk and security risk should share a scoring approach where they overlap.
- **Evidence required:** SD-13 resolution.
- **Decision owner:** Quality owner + Security owner (joint)
- **Target phase:** 0.8+
- **Status:** Open — depends on SD-13

### QD-06 — Severity-to-Response-Time Mapping

- **Question:** What response/resolution-time expectation applies per defect severity level?
- **Areas affected:** [defect-triage-root-cause-and-quality-debt.md, Section 2](defect-triage-root-cause-and-quality-debt.md#2-severity-model)
- **Why it matters:** Restated per the phase's own working instruction not to define final service-level timelines here.
- **Options:** None — requires operational-policy input once a support model exists.
- **Recommended direction:** None — genuinely deferred pending operational planning.
- **Evidence required:** DevOps/support-model definition.
- **Decision owner:** Quality owner
- **Target phase:** Pre-launch
- **Status:** Open

### QD-07 — Defect-Tracking Tool Selection

- **Question:** What tool implements the defect-management model in [defect-triage-root-cause-and-quality-debt.md](defect-triage-root-cause-and-quality-debt.md)?
- **Areas affected:** [defect-triage-root-cause-and-quality-debt.md, Section 1](defect-triage-root-cause-and-quality-debt.md#1-defect-management)
- **Why it matters:** No tool is assumed in this documentation-only phase.
- **Options:** GitHub Issues (natural fit given the GitHub direction) vs. a dedicated issue tracker.
- **Recommended direction:** GitHub Issues as the practical starting point, consistent with the approved GitHub direction.
- **Evidence required:** None — a low-risk, easily-changed implementation decision.
- **Decision owner:** QA lead
- **Target phase:** 0.8
- **Status:** Open

### QD-08 — Mutation-Testing Adoption Timing

- **Question:** Is mutation testing adopted, and for which modules first?
- **Areas affected:** [quality-metrics-reporting-and-evidence.md, Section 4](quality-metrics-reporting-and-evidence.md#4-mutation-testing-readiness)
- **Why it matters:** A candidate technique for validating Critical-tier test-suite effectiveness, not yet justified without a mature baseline suite.
- **Options:** Not adopted initially vs. adopted for Scoring/Eligibility/Medal-Tally domain logic once those modules have baseline coverage.
- **Recommended direction:** Deferred until the Critical-tier domains have mature baseline test suites — premature mutation testing against an incomplete suite produces noise, not signal.
- **Evidence required:** Baseline Critical-tier domain test-suite maturity.
- **Decision owner:** Quality owner
- **Target phase:** Post-0.8
- **Status:** Open

### QD-09 — Real-Data-Migration Testing Scope

- **Question:** Is historical data-migration testing (per [data-database-migration-and-quality-testing.md, Section 5](data-database-migration-and-quality-testing.md#5-data-migration-testing)) scoped at all?
- **Areas affected:** [data-database-migration-and-quality-testing.md, Section 5](data-database-migration-and-quality-testing.md#5-data-migration-testing)
- **Why it matters:** Directly depends on whether historical data migration is confirmed in scope at all.
- **Options:** N/A pending [../00-product/product-scope.md, Section 14](../00-product/product-scope.md#14-data-migration-scope) confirmation.
- **Recommended direction:** None — mirrors the still-unconfirmed Phase 0.1 scope question exactly.
- **Evidence required:** Confirmation of whether legacy spreadsheet/historical data exists and is migratable.
- **Decision owner:** Product owner
- **Target phase:** Pre-0.8
- **Status:** Open — mirrors unresolved Phase 0.1 scope question

### QD-10 — Static-Analysis Security Scanner and Dependency/Secret Scanning Tooling

- **Question:** Which specific static-analysis/dependency/secret-scanning tools are adopted?
- **Areas affected:** [security-privacy-audit-and-compliance-assurance.md, Section 3](security-privacy-audit-and-compliance-assurance.md#3-security-testing), [automation-ci-and-quality-gates.md, Section 2](automation-ci-and-quality-gates.md#2-ci-quality-gates-candidates)
- **Why it matters:** Directly mirrors [../03-security/security-open-decisions.md, SD-16](../03-security/security-open-decisions.md#sd-16--static-analysis-security-scanner-selection).
- **Options:** Per SD-16.
- **Recommended direction:** Align with SD-16's eventual resolution rather than deciding independently.
- **Evidence required:** SD-16 resolution, CI platform decision (QD-03).
- **Decision owner:** Application owner (per Phase 0.6) + Technical lead
- **Target phase:** 0.8+
- **Status:** Open — depends on SD-16 and QD-03

### QD-11 — Penetration-Testing Scheduling

- **Question:** When is the first penetration test scheduled relative to the pilot?
- **Areas affected:** [security-privacy-audit-and-compliance-assurance.md, Section 3](security-privacy-audit-and-compliance-assurance.md#3-security-testing)
- **Why it matters:** Mirrors [../03-security/security-open-decisions.md, SD-15](../03-security/security-open-decisions.md#sd-15--penetration-testing-scope-vendor-and-timing).
- **Options:** Before pilot vs. before general rollout only.
- **Recommended direction:** Before pilot, given the pilot involves real (if limited) operational exposure.
- **Evidence required:** Implementation roadmap and budget approval.
- **Decision owner:** Security owner + DepEd Leadership
- **Target phase:** Pre-pilot
- **Status:** Open — depends on SD-15

### QD-12 — Load-Model Input Availability Timing

- **Question:** When do real load-model inputs (athlete/delegation/venue counts, etc.) become available?
- **Areas affected:** [performance-load-concurrency-and-capacity-testing.md, Section 2](performance-load-concurrency-and-capacity-testing.md#2-load-model-inputs)
- **Why it matters:** No meaningful performance target can be set without them.
- **Options:** From DepEd planning figures for a typical provincial meet, vs. only from the pilot itself.
- **Recommended direction:** Seek DepEd planning figures as an early estimate, refined by actual pilot data.
- **Evidence required:** DepEd operational planning data.
- **Decision owner:** Product owner
- **Target phase:** Pre-pilot
- **Status:** Open

### QD-13 — Pilot Meet Selection and Timing

- **Question:** Which specific meet (or simulation) serves as the pilot, and when?
- **Areas affected:** [pilot-operational-and-stakeholder-validation.md, Section 1](pilot-operational-and-stakeholder-validation.md#1-pilot-validation)
- **Why it matters:** Directly gates most of Phase 0.7's real-world validation activities.
- **Options:** A real, scaled-down provincial meet vs. a fully simulated pilot.
- **Recommended direction:** A real, scaled-down provincial meet if DepEd scheduling allows, given the operational realism a simulation cannot fully replicate.
- **Evidence required:** DepEd scheduling and approval.
- **Decision owner:** Product owner + DepEd Leadership
- **Target phase:** Pre-pilot
- **Status:** Open — outside this documentation's control

### QD-14 — Accessibility Conformance Target Level

- **Question:** What specific WCAG conformance level (A, AA, AAA) does PMMS target?
- **Areas affected:** [accessibility-usability-and-compatibility-testing.md, Section 1](accessibility-usability-and-compatibility-testing.md#1-accessibility-testing)
- **Why it matters:** WCAG is currently only a candidate reference requiring validation.
- **Options:** AA (the common government/public-sector baseline) vs. a lower/higher target.
- **Recommended direction:** AA as the practical starting target, pending formal accessibility-policy confirmation (tracked in [../03-security/policy-source-registry.md, POL-13](../03-security/policy-source-registry.md#registry)).
- **Evidence required:** Formal accessibility-policy source, and a dedicated accessibility review.
- **Decision owner:** QA lead + Product owner
- **Target phase:** 0.8+
- **Status:** Open

### QD-15 — Device and Browser Compatibility Matrix

- **Question:** What is the actual supported browser/OS/device list?
- **Areas affected:** [accessibility-usability-and-compatibility-testing.md, Section 3](accessibility-usability-and-compatibility-testing.md#3-compatibility-testing)
- **Why it matters:** No matrix is finalized without evidence, per the phase's working instructions.
- **Options:** N/A — requires real device-inventory/analytics data.
- **Recommended direction:** Establish once DepEd's actual field-device inventory and public-portal analytics are available.
- **Evidence required:** DepEd device-inventory data, early public-portal usage data.
- **Decision owner:** QA lead
- **Target phase:** Pre-launch
- **Status:** Open

### QD-16 — Disaster-Recovery Exercise Cadence

- **Question:** How often is a full DR exercise performed?
- **Areas affected:** [resilience-backup-recovery-and-continuity-testing.md, Section 4](resilience-backup-recovery-and-continuity-testing.md#4-disaster-recovery-testing)
- **Why it matters:** Mirrors the still-open Phase 0.5 question of whether a DR environment exists before or after the first pilot meet.
- **Options:** Annual vs. semi-annual vs. tied to major release cycles.
- **Recommended direction:** None — depends on DR-environment provisioning timing itself, which is unresolved.
- **Evidence required:** DR-environment provisioning decision, per [../02-data/backup-restore-and-data-recovery.md, Section 6](../02-data/backup-restore-and-data-recovery.md#6-open-questions).
- **Decision owner:** Infrastructure owner
- **Target phase:** Pre-launch
- **Status:** Open

### QD-17 — Scenario-Builder Utility Ownership

- **Question:** Is a shared scenario-builder utility built centrally, or left to per-module factories?
- **Areas affected:** [test-data-fixture-and-scenario-strategy.md, Section 4](test-data-fixture-and-scenario-strategy.md#4-scenario-builders)
- **Why it matters:** Directly mirrors the still-open Phase 0.5 question in [../02-data/test-seed-and-reference-data-strategy.md, Section 5](../02-data/test-seed-and-reference-data-strategy.md#5-open-questions).
- **Options:** A shared, centrally-owned scenario-builder library vs. per-module factories composed ad hoc.
- **Recommended direction:** A shared utility, given the frequency with which Critical-tier tests need multi-context realistic scenarios (a complete meet, a filed protest, etc.).
- **Evidence required:** None — a low-risk, reversible implementation decision.
- **Decision owner:** Technical lead
- **Target phase:** 0.8
- **Status:** Open

### QD-18 — Golden-Dataset Ownership and Sourcing Process

- **Question:** Who owns sourcing and maintaining golden datasets once sport-specific rules are approved?
- **Areas affected:** [test-data-fixture-and-scenario-strategy.md, Section 5](test-data-fixture-and-scenario-strategy.md#5-golden-datasets)
- **Why it matters:** Golden datasets require an approved rule source before they can be trusted — the process for obtaining and maintaining that sourcing isn't yet defined.
- **Options:** QA lead maintains, sourced from Sports-rule validator input, vs. a dedicated data-quality role.
- **Recommended direction:** QA lead maintains, with mandatory Sports-rule validator sign-off on every golden dataset before use.
- **Evidence required:** None — a process decision.
- **Decision owner:** Quality owner
- **Target phase:** 0.8+
- **Status:** Open

### QD-19 — UAT Participant Recruitment and Scheduling

- **Question:** How are real DepEd/committee stakeholders recruited and scheduled for UAT, given their own operational duties?
- **Areas affected:** [regression-smoke-exploratory-and-uat-strategy.md, Section 5](regression-smoke-exploratory-and-uat-strategy.md#5-user-acceptance-testing)
- **Why it matters:** UAT's validity depends on genuine stakeholder participation, which competes with those stakeholders' real DepEd responsibilities.
- **Options:** Dedicated UAT sessions scheduled around DepEd's calendar vs. folding UAT into pilot preparation activities.
- **Recommended direction:** Folding UAT into pilot-preparation activities where practical, minimizing the burden of a wholly separate UAT event.
- **Evidence required:** DepEd stakeholder availability and Product-owner coordination.
- **Decision owner:** UAT coordinator
- **Target phase:** Pre-pilot
- **Status:** Open

### QD-20 — Physical Test-Suite Organization Convention

- **Question:** How are Pest test files organized once PMMS domain tests begin (per bounded-context module vs. per test level)?
- **Areas affected:** [test-levels-and-test-types.md, Section 3](test-levels-and-test-types.md#3-test-case-and-test-scenario-architecture)
- **Why it matters:** A consistent convention reduces friction as 34 bounded-context modules each accumulate tests.
- **Options:** `tests/Feature/<Context>/`, `tests/Domain/<Context>/`, etc. (module-first) vs. `tests/Unit/`, `tests/Feature/` with context subfolders (level-first, matching the existing starter-kit convention).
- **Recommended direction:** Level-first, extending the existing starter-kit convention (`tests/Feature/`, `tests/Unit/`) with context subfolders, minimizing disruption to the already-established pattern.
- **Evidence required:** None — a low-risk, reversible implementation decision.
- **Decision owner:** Technical lead
- **Target phase:** 0.8
- **Status:** Open

### QD-21 — Integration-Test Infrastructure Approach

- **Question:** Do integration tests run against real Docker-composed MySQL/Redis/MinIO, or a lightweight in-memory equivalent?
- **Areas affected:** [api-contract-and-integration-testing.md, Section 4](api-contract-and-integration-testing.md#4-open-questions), [test-environment-and-service-virtualization.md, Section 1](test-environment-and-service-virtualization.md#1-test-environment-strategy)
- **Why it matters:** Directly affects integration-suite speed/fidelity trade-off and depends on Docker's introduction timing (explicitly deferred, per [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md)).
- **Options:** Real infrastructure via Docker Compose vs. SQLite/array-driver equivalents for most tests, real infrastructure reserved for a smaller dedicated Integration suite.
- **Recommended direction:** The latter — mirrors the existing `phpunit.xml` pattern (SQLite in-memory, array drivers) for Feature tests, with a smaller, slower Integration suite specifically exercising real MySQL/Redis/MinIO.
- **Evidence required:** Docker introduction timing (a later infrastructure-phase decision).
- **Decision owner:** DevOps reviewer + Technical lead
- **Target phase:** 0.8+
- **Status:** Open

### QD-22 — Release Cadence

- **Question:** Does PMMS ship via continuous delivery or scheduled releases once implementation begins?
- **Areas affected:** [release-readiness-and-quality-signoff.md, Section 5](release-readiness-and-quality-signoff.md#5-open-questions)
- **Why it matters:** Shapes how frequently the release sign-off process actually executes and how automation suites are scheduled.
- **Options:** Continuous delivery (frequent small releases) vs. scheduled releases (tied to meet-cycle timing, given PMMS's inherently cyclical operational rhythm).
- **Recommended direction:** Scheduled releases aligned to meet-cycle timing initially, given the operational risk of a mid-meet-cycle release — revisit toward continuous delivery once the platform is stable and post-launch.
- **Evidence required:** Operational risk-tolerance discussion with DepEd Leadership.
- **Decision owner:** Release approver + Product owner
- **Target phase:** 0.8+
- **Status:** Open

### QD-23 — Traceability Tooling (AC-XX / TS-XX Identifier Management)

- **Question:** Is a physical requirements-management/traceability tool adopted, or does the AC-XX/TS-XX scheme remain an informal documentation discipline?
- **Areas affected:** [requirements-traceability-model.md, Section 2](requirements-traceability-model.md#2-traceability-identifiers-conceptual)
- **Why it matters:** A physical tool improves traceability rigor at the cost of additional process overhead.
- **Options:** A dedicated requirements-management tool vs. tracking via the issue tracker (per QD-07) plus documentation conventions.
- **Recommended direction:** Start informal (issue tracker + documentation), given the platform's current pre-implementation stage — revisit once traceability gaps are observed in practice.
- **Evidence required:** None — a low-risk, reversible process decision.
- **Decision owner:** Quality owner
- **Target phase:** 0.8+
- **Status:** Open

### QD-24 — Service-Virtualization Tooling Selection

- **Question:** What specific tooling implements the mock-server/service-virtualization boundaries in [test-environment-and-service-virtualization.md, Section 3](test-environment-and-service-virtualization.md#3-service-virtualization)?
- **Areas affected:** [test-environment-and-service-virtualization.md, Section 3](test-environment-and-service-virtualization.md#3-service-virtualization)
- **Why it matters:** Every external boundary is virtualized by necessity today (no vendor is currently approved), so tooling choice affects nearly every integration test.
- **Options:** A lightweight local mock-server framework vs. Laravel's built-in HTTP-client faking for outbound calls, plus a dedicated tool for inbound webhook simulation once any webhook is approved.
- **Recommended direction:** Laravel's built-in faking (`Http::fake()`-equivalent patterns) for most outbound-boundary simulation, given it requires no additional dependency and fits the confirmed Laravel direction; a dedicated tool evaluated only if a specific boundary's complexity outgrows it.
- **Evidence required:** None — a low-risk, reversible implementation decision.
- **Decision owner:** Technical lead
- **Target phase:** 0.8
- **Status:** Open

---

## Summary of Blocking / High-Priority Quality Decisions

| Decision | Why It Blocks |
|---|---|
| **QD-09** | Data-migration testing scope depends entirely on the still-unconfirmed Phase 0.1 data-migration-scope question |
| **QD-12 / QD-13** | Performance targets and pilot-dependent validation cannot proceed meaningfully without real load-model inputs and a scheduled pilot, both outside this documentation's control |
| **QD-05 / QD-10 / QD-11** | Mirror still-open Phase 0.6 security decisions (SD-13, SD-16, SD-15) — resolving those first avoids duplicated, potentially divergent quality-side decisions |
| **QD-01 / QD-02** | Frontend and Flutter test tooling selection blocks any actual test-writing for those layers once Phase 0.8 implementation begins |
