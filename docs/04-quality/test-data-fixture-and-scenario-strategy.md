# PMMS Test Data, Fixture, and Scenario Strategy

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../02-data/test-seed-and-reference-data-strategy.md](../02-data/test-seed-and-reference-data-strategy.md) · [../03-security/retention-disposal-and-legal-hold-governance.md, "Test and Lower-Environment Data Governance"](../03-security/retention-disposal-and-legal-hold-governance.md#test-and-lower-environment-data-governance)

This document defines test-data categories, synthetic-data requirements, and future fixture/factory/scenario-builder responsibilities. **No factory, fixture, or seeder is created here**, per working rule 14 — this extends [../02-data/test-seed-and-reference-data-strategy.md](../02-data/test-seed-and-reference-data-strategy.md) (Phase 0.5) with quality-engineering-specific detail.

---

## 1. Test-Data Architecture

| Category | Notes |
|---|---|
| Reference data | Sports catalog, organization directory — policy-sensitive entries cite their source, never invented |
| Identity data | Synthetic person/participant/user-account records |
| Meet data | Synthetic meet configurations across the full lifecycle |
| Athlete data | Synthetic athlete records, including synthetic minors |
| Guardian data | Synthetic guardian relationships |
| Eligibility data | Synthetic eligibility cases across every state (submitted, under review, approved, rejected, reopened) |
| Tournament data | Synthetic brackets/schedules/heats |
| Score data | Synthetic scores across valid, invalid, and boundary cases |
| Results | Synthetic official results across certified, held, superseded, published states |
| Medical data | Synthetic, obviously-fake medical records |
| Finance data | Synthetic financial records |
| Accreditation data | Synthetic credentials and QR tokens |
| Device data | Synthetic device/credential records |
| Audit data | Synthetic audit-event history supporting audit-testing scenarios |
| Security events | Synthetic security-event records supporting security-testing scenarios |
| Public projections | Synthetic public-facing data supporting projection/reporting tests |
| Large-volume data | Section 4 |

## 2. Synthetic Data Requirements

Every category above is synthetic by default, with:

- **Non-real names** — clearly fictional, never derived from real people.
- **Non-real contact details** — synthetic email/phone patterns that cannot reach a real person or number.
- **Non-real identifiers** — synthetic government/school ID patterns, never a real individual's actual identifier.
- **Synthetic minors** — clearly, obviously fictional minor-athlete records.
- **Synthetic guardians** — clearly fictional guardian records.
- **Synthetic medical data** — clearly fictional, obviously-fake medical content — restated absolutely from working rule 17.
- **Synthetic finance data** — clearly fictional financial figures and vendor names.
- **Synthetic documents** — placeholder/generated documents, never real scanned evidence.
- **Synthetic photos or placeholders** — generated or placeholder imagery, never a real person's photo.
- **Deterministic generation** — the same seed/scenario produces the same synthetic dataset, supporting reproducible tests.
- **Scenario labeling** — every synthetic dataset is clearly labeled/namespaced as test data, reducing any risk of confusion with real data if it were ever mishandled.

## 3. Fixtures and Factories (Future Responsibilities, Not Created Here)

A future implementation phase's fixtures/factories are expected to provide:

- Small, reusable object creation for a single aggregate/entity.
- Valid defaults that produce a domain-valid object out of the box.
- Explicit override capability for the specific field(s) a test cares about.
- No hidden global state — a factory's output depends only on its explicit inputs.
- Domain-valid state by default, with clear support for constructing deliberately invalid-case state where a negative test needs it.
- Correct relationship ownership (e.g., a factory-created `CompetitionEntry` correctly references a factory-created `Participant` within the same owning context).
- Scenario readability — factory usage in a test should read close to natural language, not require deep knowledge of internal object structure.
- Avoiding brittle database coupling — a factory shouldn't require knowledge of physical schema details a test author shouldn't need to know.

**No factory is created in this phase** — this section documents the standard a Phase 0.8+ factory implementation is expected to meet.

## 4. Scenario Builders

Recommended for composing multiple factories into a realistic, complete situation:

Complete meet · registration cycle · eligibility review · tournament setup · scoring cycle · protest case · medal tally · accreditation · offline venue · medical incident · finance approval · public publication.

A scenario builder (e.g., "build a complete meet with 3 sports, 2 delegations, and a filed protest") reduces test setup duplication and improves readability for complex, multi-context test scenarios — particularly valuable for the Critical-tier workflows in [high-integrity-sports-workflow-testing.md](high-integrity-sports-workflow-testing.md), where a realistic test often needs several related records to exist together correctly. This mirrors and confirms the direction already flagged in [../02-data/test-seed-and-reference-data-strategy.md, Section 5](../02-data/test-seed-and-reference-data-strategy.md#5-open-questions) ("whether a shared scenario-builder utility is warranted").

## 5. Golden Datasets

Controlled datasets with **approved** expected outcomes, used to validate correctness of:

Scoring · ranking · advancement · medal tally · eligibility · public projections · imports · exports · migration reconciliation.

**Golden datasets must cite approved rule versions** — restated absolutely; a golden dataset for medal-tally calculation, for example, is only valid once the underlying medal rules are policy-sourced (per [../00-product/open-decisions.md, OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules)), never built from an engineer's best guess at what the correct outcome "should" be.

## 6. Large-Volume Test Data

Reproducible, privacy-safe, scalable synthetic datasets for:

Athletes · delegations · events · results · access scans · audit events · notifications · public viewers · mobile sync · files.

These support the performance/load testing in [performance-load-concurrency-and-capacity-testing.md](performance-load-concurrency-and-capacity-testing.md) and the capacity categories flagged in [../02-data/indexing-performance-and-capacity.md, Section 2](../02-data/indexing-performance-and-capacity.md#2-capacity-and-growth-categories) — generated deterministically so a performance regression can be attributed to a code change rather than dataset variance between test runs.

## 7. Privacy-Safe Lower-Environment Data

Restated absolutely from working rule 17: **no real minor-athlete, medical, eligibility, guardian, finance, authentication, or audit data is ever used in a lower environment.** Any exceptional, approved need for production-derived data follows the formal masking/approval process in [../03-security/retention-disposal-and-legal-hold-governance.md, "Test and Lower-Environment Data Governance"](../03-security/retention-disposal-and-legal-hold-governance.md#test-and-lower-environment-data-governance) — never a default practice, and never for the categories named in working rule 17 regardless of masking, given their especially high sensitivity.

## 8. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably whether a shared scenario-builder utility is built centrally or left to per-module factories (mirrors the still-open Phase 0.5 question), and golden-dataset ownership once specific rule sources are approved.
