# PMMS Test, Seed, and Reference Data Strategy

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [information-classification-and-privacy.md](information-classification-and-privacy.md) · [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md) · [conceptual-schema-catalog.md](conceptual-schema-catalog.md)

This document classifies reference/seed data and defines test-data principles. **No seeder, factory, or fixture is created here.**

---

## 1. Reference and Seed Data Classification

### Platform Reference Data
Geographic references where needed, data classifications (the five tiers themselves, as a controlled enumeration), system-level categories, technical statuses where globally stable (e.g., a generic "active/inactive" pattern used consistently across contexts that genuinely share that exact binary meaning).

### Organization Reference Data
Regions, divisions, districts, schools — per [../01-architecture/bounded-context-catalog.md, BC-03](../01-architecture/bounded-context-catalog.md#bc-03--organization-directory).

### Sports Reference Data
Sports, disciplines, events, categories, rule-source references — per [../01-architecture/bounded-context-catalog.md, BC-10](../01-architecture/bounded-context-catalog.md#bc-10--sports-catalog). **Policy-sensitive** — every entry must cite its authoritative source, never an invented value, per working rule 31.

### Meet Configuration Data
Committees (canonical list, if one is confirmed), venues, accreditation categories, operational statuses.

## 2. Rules

- **Reference data has an owner** — every reference dataset is attributed to its owning bounded context (per [persistence-ownership-map.md](persistence-ownership-map.md)); there is no ownerless, ambient "lookup data" pool.
- **Reference data may be versioned** — per [temporal-history-and-versioning-model.md, Section 4](temporal-history-and-versioning-model.md#4-versioning-and-supersession), particularly for Sports Catalog and Configuration and Reference Data (BC-34) content.
- **Policy-sensitive data cites its source** — a Sports Catalog entry's `rule_source_reference` field is mandatory, never left blank with an assumed/invented rule behind it.
- **Seeders never contain live credentials** — no seeder for any environment embeds a real password, API key, or production secret.
- **Seeders never invent official rules** — restated as an absolute rule (working rule 31); a seeder populating Sports Catalog entries uses only DepEd/sports-specialist-confirmed values, or is deliberately left empty/marked pending until such confirmation exists.
- **Environment-specific demo data remains separate** — a Development-environment "demo meet" dataset is clearly distinguished from genuine platform reference data, never risking confusion about which is authoritative.
- **Production seed data is reviewed** — any seed data destined for the Production environment (e.g., an initial organization record, an initial reference-data set) requires the same review rigor as any other production-affecting change.

## 3. Test Data Strategy

### Categories
Synthetic identities, synthetic minor-athlete data, synthetic medical records, synthetic financial records, deterministic test fixtures, factories, scenario builders, meet-based test datasets, boundary-condition data, concurrency test data, offline sync datasets, large-volume datasets, public projection datasets, privacy-safe staging data.

### Rules

- **Real production records are never used by default.** Every Development/Test/Staging dataset is synthetic, generated to resemble realistic PMMS data shapes without containing any actual person's information.
- **Masked production data requires formal approval.** If a future need genuinely requires production-derived data in a lower environment (e.g., reproducing a hard-to-synthesize bug), it is masked/de-identified per [audit-and-security-data-architecture.md, Section 4](audit-and-security-data-architecture.md#4-data-masking-and-redaction) and requires explicit, documented approval — never a default practice.
- **Test data covers state transitions** — a test dataset for `EligibilityCase` includes examples in every state (submitted, under review, approved, rejected, reopened), not just "happy path" approved cases, so state-transition logic (per [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md)) is actually exercised.
- **High-integrity test scenarios preserve history** — a test fixture for a corrected score includes both the original and corrected versions, exercising the versioning/supersession model itself (per [high-integrity-data-model.md](high-integrity-data-model.md)), not just the final "current" value.
- **Performance datasets are reproducible** — a large-volume test dataset (simulating, e.g., 10,000 `AccessScan` rows for load testing) is generated deterministically, so a performance regression can be attributed to a code change rather than dataset variance between test runs.
- **Synthetic minor-athlete and medical data are explicitly synthetic in a way that's obviously fake** — e.g., clearly placeholder names/values — reducing any risk of synthetic test data being mistaken for real data if it ever leaked or was mishandled.

## 4. Relationship to Phase 0.4 Testing Architecture

This document's Section 3 directly informs [../01-architecture/testing-architecture.md, Section 11](../01-architecture/testing-architecture.md#11-open-questions) ("Test-data management strategy") and [../01-architecture/runtime-open-decisions.md, RD-28](../01-architecture/runtime-open-decisions.md#rd-28--test-data-management-strategy) (recommended direction: standard Laravel factories per aggregate root, owned by each domain module's own test suite) — Phase 0.5 confirms and extends that direction with the privacy-safety and state-coverage requirements above.

## 5. Open Questions

- Whether a shared "scenario builder" utility (composing multiple factories into a realistic full-meet test scenario) is warranted, or per-module factories suffice — implementation-phase decision.
- Formal approval process for any future masked-production-data use in lower environments (Section 3) — not yet defined.

Tracked in [data-open-decisions.md](data-open-decisions.md).
