# PMMS Data Open Decisions

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [phase-0.5-data-database-persistence-architecture.md](phase-0.5-data-database-persistence-architecture.md) · [../01-architecture/domain-open-decisions.md](../01-architecture/domain-open-decisions.md) (Phase 0.2) · [../01-architecture/access-open-decisions.md](../01-architecture/access-open-decisions.md) (Phase 0.3) · [../01-architecture/runtime-open-decisions.md](../01-architecture/runtime-open-decisions.md) (Phase 0.4)

This document records unresolved **data, database, persistence, and information-lifecycle** questions identified during Phase 0.5, using `PD-XX` (Persistence Decision) identifiers, distinct from Phase 0.2's `DD-XX`, Phase 0.3's `AD-XX`, and Phase 0.4's `RD-XX`. **No decision below is final.**

---

### PD-01 — Tenant-Column Timing
- **Question:** Does every meet/organization-scoped table carry a nullable `organization_id` from the first migration, or is it added when a second organization is actually onboarded?
- **Areas affected:** [logical-data-architecture.md, Section 4](logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles).
- **Why it matters:** Retrofitting a tenant column across dozens of tables after real data exists is materially more expensive than including it from the start; but including it prematurely for a single-organization launch adds unused complexity.
- **Options:** (a) Include nullable `organization_id` from day one, defaulting to the single DepEd organization; (b) Add only when a second organization is confirmed.
- **Recommended direction:** (a) — mirrors the identical reasoning already applied to authorization scope in [../01-architecture/domain-open-decisions.md, DD-21](../01-architecture/domain-open-decisions.md#dd-21--tenant-boundaries), at low marginal cost if designed in from the start.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (data architect)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-02 — ULID vs. UUIDv7 Format
- **Question:** Which specific time-sortable identifier format is adopted for public IDs?
- **Areas affected:** [identifier-and-reference-strategy.md, Section 1](identifier-and-reference-strategy.md#1-internal-primary-keys--recommended-direction).
- **Why it matters:** Both satisfy the stated requirements (sortable, collision-resistant, offline-generatable); the choice affects library/tooling availability in the PHP/Laravel ecosystem.
- **Options:** ULID; UUIDv7.
- **Recommended direction:** None — either is acceptable; deferred to Phase 0.6 based on Laravel-ecosystem library maturity at implementation time.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6
- **Status:** Open

### PD-03 — Public-ID Rollout Scope
- **Question:** Is the internal-key/public-ID pattern applied uniformly across all 34 schema groups from the first migration, or introduced incrementally starting with offline-critical and public-facing contexts?
- **Areas affected:** [identifier-and-reference-strategy.md, Section 6](identifier-and-reference-strategy.md#6-open-questions).
- **Why it matters:** Uniform adoption is simpler to reason about; incremental adoption reduces upfront work for contexts that may never need public exposure.
- **Options:** Uniform from day one; incremental, starting with Scoring, Access Validation, Official Results, Medal Tally.
- **Recommended direction:** Uniform — the pattern's cost is low and applying it selectively risks an inconsistent codebase where "does this table have a public_id" becomes a recurring question.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-04 — Retention Periods (8 Categories)
- **Question:** What are the actual minimum/maximum retention periods for operational, official-meet, financial, medical, security, audit, public, and report record categories?
- **Areas affected:** [retention-archival-and-disposal.md, Section 1](retention-archival-and-disposal.md#1-retention-categories).
- **Why it matters:** Every placeholder in that table blocks finalizing partitioning/archival physical design in Phase 0.6.
- **Options:** N/A pending policy input.
- **Recommended direction:** None.
- **Evidence required:** DepEd records-management policy; legal input for medical/financial/audit categories specifically.
- **Decision owner:** To be identified (DepEd Leadership, Data Privacy and Legal Stakeholders)
- **Target phase:** Phase 0.6, ideally before
- **Status:** Open — **blocking**, mirrors [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)

### PD-05 — Audit Log Tamper-Evidence Mechanism
- **Question:** Does PMMS implement a cryptographic tamper-evidence mechanism (e.g., a hash chain) for the audit event table?
- **Areas affected:** [audit-and-security-data-architecture.md, Section 1](audit-and-security-data-architecture.md#1-audit-data-architecture).
- **Why it matters:** Strengthens the audit log's evidentiary value against sophisticated tampering, at implementation and verification cost.
- **Options:** No additional mechanism (rely on restricted database grants alone); hash-chain or equivalent tamper-evidence.
- **Recommended direction:** None — a security-team risk-assessment call, not an architecture-team default.
- **Evidence required:** Security review.
- **Decision owner:** To be identified (Security Administrator)
- **Target phase:** Phase 0.6
- **Status:** Open

### PD-06 — Field-Level Encryption Candidate List
- **Question:** Which specific Highly Restricted fields warrant field-level encryption beyond database-at-rest volume encryption?
- **Areas affected:** [audit-and-security-data-architecture.md, Section 3](audit-and-security-data-architecture.md#3-encryption-and-sensitive-data).
- **Why it matters:** Field-level encryption has query/performance costs; applying it universally to every Highly Restricted field may be unnecessary if volume-level encryption already meets the risk bar.
- **Options:** Volume-level only; field-level for a specific named list (e.g., detailed medical notes, authentication secrets); field-level for all Highly Restricted fields.
- **Recommended direction:** None — security-team risk assessment required.
- **Evidence required:** Security/privacy review.
- **Decision owner:** To be identified (Security Administrator, Data Privacy and Legal Stakeholders)
- **Target phase:** Phase 0.6
- **Status:** Open

### PD-07 — Encryption Key-Management Approach
- **Question:** Is encryption key management application-managed or handled by a dedicated key-management service?
- **Areas affected:** [audit-and-security-data-architecture.md, Section 3](audit-and-security-data-architecture.md#3-encryption-and-sensitive-data).
- **Why it matters:** A dedicated KMS is more robust but adds infrastructure; application-managed keys are simpler but concentrate risk.
- **Options:** Application-managed (e.g., Laravel's built-in encryption with environment-secured keys); dedicated KMS.
- **Recommended direction:** None — deferred to Phase 0.6/infrastructure-phase, informed by PD-06's outcome.
- **Evidence required:** None blocking for deferral.
- **Decision owner:** To be identified (software architect, DevOps)
- **Target phase:** Later infrastructure phase
- **Status:** Open

### PD-08 — Formal Classification Tier Validation
- **Question:** Do the five proposed classification tiers (Public/Internal/Confidential/Restricted/Highly Restricted) and their example mappings hold up under formal privacy/legal review?
- **Areas affected:** [information-classification-and-privacy.md](information-classification-and-privacy.md).
- **Why it matters:** The entire persistence architecture's access/encryption/logging rules key off this classification; an unvalidated model risks needing rework.
- **Options:** N/A pending review.
- **Recommended direction:** None.
- **Evidence required:** Data Privacy and Legal Stakeholder review.
- **Decision owner:** To be identified
- **Target phase:** Before Phase 0.6 physical schema finalization
- **Status:** Open — high priority

### PD-09 — Age-Band-Specific Visibility Rules
- **Question:** Does DepEd policy require different public-visibility rules for different age bands of minor athletes?
- **Areas affected:** [information-classification-and-privacy.md, Section 3](information-classification-and-privacy.md#3-minor-and-athlete-data-protection).
- **Why it matters:** Affects the public-projection filtering logic in [public-reporting-and-projection-data.md](public-reporting-and-projection-data.md).
- **Options:** Uniform rule for all minors; age-band-differentiated rules.
- **Recommended direction:** None — requires DepEd policy input.
- **Evidence required:** DepEd/legal policy confirmation.
- **Decision owner:** To be identified
- **Target phase:** Phase 0.6
- **Status:** Open

### PD-10 — Restricted-Tier Physical Schema Separation
- **Question:** Do Restricted-classification tables warrant physical/logical database separation (distinct schema, distinct database user grants) in Phase 0.6, or is application-layer scoping sufficient?
- **Areas affected:** [information-classification-and-privacy.md, Section 2](information-classification-and-privacy.md#2-storage-expectations-by-tier-persistence-layer-detail).
- **Why it matters:** Physical separation adds defense-in-depth at the cost of query/join complexity across the boundary.
- **Options:** Application-layer scoping only; physical schema separation for Restricted+ tiers.
- **Recommended direction:** None — a Phase 0.6 architecture/security joint decision.
- **Evidence required:** Security review.
- **Decision owner:** To be identified (software architect, Security Administrator)
- **Target phase:** Phase 0.6
- **Status:** Open

### PD-11 — Object Reconciliation Frequency and Orphan-Cleanup Grace Period
- **Question:** How often does metadata-to-object reconciliation run, and how long does an orphaned object wait before cleanup?
- **Areas affected:** [object-metadata-and-file-lifecycle.md, Section 4](object-metadata-and-file-lifecycle.md#4-reconciliation-process-conceptual).
- **Why it matters:** Too frequent wastes resources; too infrequent delays detection of storage inconsistencies.
- **Options:** Deferred to implementation tuning.
- **Recommended direction:** Start with a daily reconciliation job and a multi-day orphan grace period, adjusted based on real operational observation.
- **Evidence required:** None blocking for an initial value; refine post-pilot.
- **Decision owner:** To be identified (ICT Committee)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-12 — Checksum Algorithm for Object Integrity
- **Question:** Does object-integrity verification use a cryptographic hash (SHA-256) or a faster non-cryptographic checksum?
- **Areas affected:** [object-metadata-and-file-lifecycle.md, Section 5](object-metadata-and-file-lifecycle.md#5-open-questions).
- **Why it matters:** Cryptographic hashing is more tamper-resistant; a faster checksum reduces compute cost at high upload volume.
- **Options:** SHA-256; a faster checksum (e.g., CRC32/xxHash).
- **Recommended direction:** SHA-256 given the evidentiary role documents play in high-integrity domains — integrity assurance outweighs the modest compute cost at PMMS's expected volume.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-13 — Historical Spreadsheet Data Migratability
- **Question:** Do usable historical spreadsheet-based meet records actually exist in a migratable format?
- **Areas affected:** [import-export-and-data-exchange.md, Section 3](import-export-and-data-exchange.md#3-data-migration-readiness-future-historical-data).
- **Why it matters:** Determines whether the migration-readiness framework in that section is ever activated.
- **Options:** N/A pending discovery.
- **Recommended direction:** None — requires a DepEd-side inventory exercise.
- **Evidence required:** DepEd source-data inventory.
- **Decision owner:** To be identified (Secretariat)
- **Target phase:** Future scope
- **Status:** Open

### PD-14 — CSV Formula-Injection Mitigation Approach
- **Question:** What specific technique neutralizes formula-injection risk in spreadsheet-format exports?
- **Areas affected:** [import-export-and-data-exchange.md, Section 2](import-export-and-data-exchange.md#2-export-architecture).
- **Why it matters:** A known, well-understood risk class; the specific mitigation library/technique is an implementation detail.
- **Options:** Standard prefixing/escaping of leading special characters; a vetted export library with built-in protection.
- **Recommended direction:** A vetted library approach, to avoid hand-rolled escaping bugs.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-15 — Export File Expiry Duration
- **Question:** How long does a generated export file remain downloadable before automatic deletion?
- **Areas affected:** [import-export-and-data-exchange.md, Section 2](import-export-and-data-exchange.md#2-export-architecture).
- **Why it matters:** Balances user convenience against minimizing the window a sensitive export exists outside its source system.
- **Options:** Short (hours); medium (days).
- **Recommended direction:** Short, on the order of hours to a day, given that most exports are re-generatable on demand.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-16 — Identity-Matching Algorithm Approach
- **Question:** Is participant duplicate-detection rule-based, AI-assisted, or hybrid, and what specific confidence-scoring approach is used?
- **Areas affected:** [identity-resolution-and-duplicate-management.md, Section 6](identity-resolution-and-duplicate-management.md#6-open-questions).
- **Why it matters:** Directly affects RSK-02 (duplicate athlete records) mitigation effectiveness.
- **Options:** Rule-based only; AI-assisted; hybrid.
- **Recommended direction:** Hybrid — rule-based for high-confidence exact/near-exact matches, AI-assisted (advisory only, per [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)) for probable/possible matches requiring human review.
- **Evidence required:** Real duplicate-rate data from a pilot meet to tune thresholds.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6, refined post-pilot
- **Status:** Open — recommended direction stated

### PD-17 — Sex as an Identity-Matching Factor
- **Question:** Is "sex" a lawful and operationally appropriate identity-matching factor, and under what basis?
- **Areas affected:** [identity-resolution-and-duplicate-management.md, Section 1](identity-resolution-and-duplicate-management.md#1-matching-factors-conceptual).
- **Why it matters:** A sensitive field requiring explicit legal basis before being used even for internal matching purposes.
- **Options:** N/A pending legal input.
- **Recommended direction:** None.
- **Evidence required:** Data Privacy and Legal Stakeholder review.
- **Decision owner:** To be identified
- **Target phase:** Phase 0.6
- **Status:** Open

### PD-18 — Read-Model Freshness Targets
- **Question:** What specific freshness targets (seconds vs. minutes) apply to each candidate read model?
- **Areas affected:** [public-reporting-and-projection-data.md, Section 2](public-reporting-and-projection-data.md#2-read-models-and-analytics).
- **Why it matters:** Directly affects the projection-rebuild queue-priority and caching-strategy decisions in [../01-architecture/caching-and-session-architecture.md](../01-architecture/caching-and-session-architecture.md).
- **Options:** N/A pending operational data.
- **Recommended direction:** Deferred to post-pilot baseline, consistent with [Phase 0.1 success-framework.md](../00-product/success-framework.md#11-baseline-requirements).
- **Evidence required:** Pilot-meet operational data.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### PD-19 — Idempotency-Key Expiry Window
- **Question:** How long is an idempotency key retained before it can be reused?
- **Areas affected:** [offline-sync-and-conflict-data-model.md, Section 4](offline-sync-and-conflict-data-model.md#4-idempotency-data).
- **Why it matters:** Too short risks a legitimate late retry being treated as a new request; too long wastes Redis memory unnecessarily.
- **Options:** Deferred to implementation tuning, informed by realistic offline-reconnection windows (per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md)).
- **Recommended direction:** A window at least as long as the maximum expected offline-reconnection delay, tuned post-pilot.
- **Evidence required:** Pilot-meet connectivity data.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6, refined post-pilot
- **Status:** Open — recommended direction stated

### PD-20 — Optimistic-Locking Scope
- **Question:** Are version columns added to every high-integrity aggregate uniformly, or only where concurrent-write contention is realistically expected?
- **Areas affected:** [transaction-concurrency-and-locking.md, Section 6](transaction-concurrency-and-locking.md#6-open-questions).
- **Why it matters:** Uniform application is simpler and safer by default; selective application reduces unnecessary overhead on genuinely low-contention tables.
- **Options:** Uniform; selective.
- **Recommended direction:** Uniform for all aggregates listed as **Critical** in [conceptual-schema-catalog.md, Part 2](conceptual-schema-catalog.md#part-2--aggregate-persistence-boundaries) at minimum; selective elsewhere.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-21 — Outbox Pattern Adoption Trigger
- **Question:** What specific operational evidence would trigger adopting a formal outbox table over the current `after_commit`-dispatch default?
- **Areas affected:** [transaction-concurrency-and-locking.md, Section 5](transaction-concurrency-and-locking.md#5-outbox-and-event-persistence-evaluation); cross-references [../01-architecture/runtime-open-decisions.md, RD-01](../01-architecture/runtime-open-decisions.md#rd-01--reliable-event-delivery-mechanism).
- **Why it matters:** Avoids premature infrastructure investment while defining what "premature" no longer means.
- **Options:** A measured event-loss incident; a specific reliability SLA commitment that `after_commit` cannot satisfy.
- **Recommended direction:** Adopt only after a measured event-loss incident or once a specific reliability commitment (e.g., to DepEd) requires stronger guarantees than `after_commit` provides.
- **Evidence required:** Operational incident data or an explicit reliability requirement.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated, mirrors RD-01

### PD-22 — Partitioning Trigger Threshold
- **Question:** At what row count/table size does the highest-volume tables (`ScoreRecord`, `AccessScan`, `AuditEvent`) warrant physical partitioning?
- **Areas affected:** [indexing-performance-and-capacity.md, Section 3](indexing-performance-and-capacity.md#3-partitioning-and-archival-readiness).
- **Why it matters:** Premature partitioning adds operational complexity without benefit; delayed partitioning risks performance degradation at scale.
- **Options:** A fixed row-count threshold; a measured-performance trigger (query latency degradation).
- **Recommended direction:** Measured-performance trigger — partition when query latency data (per [indexing-performance-and-capacity.md, Section 4](indexing-performance-and-capacity.md#4-database-observability)) shows actual degradation, not a speculative row count.
- **Evidence required:** Production/pilot query-performance data.
- **Decision owner:** To be identified (data architect)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### PD-23 — RPO/RTO Numeric Targets
- **Question:** What are PMMS's actual Recovery Point Objective and Recovery Time Objective, per the priority tiers in [backup-restore-and-data-recovery.md, Section 1](backup-restore-and-data-recovery.md#1-backup-coverage-by-data-category)?
- **Areas affected:** Backup, restore, and disaster recovery architecture.
- **Why it matters:** Directly shapes backup frequency/mechanism investment.
- **Options:** N/A pending institutional input.
- **Recommended direction:** None.
- **Evidence required:** DepEd institutional-record requirements, legal/compliance input.
- **Decision owner:** To be identified (DepEd Leadership)
- **Target phase:** Phase 0.6
- **Status:** Open — mirrors the identical open item in [../01-architecture/runtime-open-decisions.md, RD-18](../01-architecture/runtime-open-decisions.md#rd-18--rporto-targets)

### PD-24 — Backup Frequency Mechanism per Tier
- **Question:** Does the "Highest" priority data tier warrant continuous point-in-time recovery (binary log shipping) versus periodic snapshots for lower tiers?
- **Areas affected:** [backup-restore-and-data-recovery.md, Section 2](backup-restore-and-data-recovery.md#2-backup-requirements).
- **Why it matters:** Continuous PITR is more robust but operationally heavier than periodic snapshots.
- **Options:** Uniform periodic snapshots; tiered (PITR for Highest tier, snapshots for others).
- **Recommended direction:** Tiered — PITR for Official Results/Audit/Eligibility given their institutional-integrity stakes; periodic snapshots elsewhere.
- **Evidence required:** None blocking for the recommendation; confirmed once PD-23's RPO is set.
- **Decision owner:** To be identified (ICT Committee)
- **Target phase:** Phase 0.6
- **Status:** Open — recommended direction stated

### PD-25 — Disaster Recovery Environment Provisioning Timing
- **Question:** Is a Disaster Recovery environment provisioned before or only after the first pilot meet?
- **Areas affected:** [backup-restore-and-data-recovery.md, Section 5](backup-restore-and-data-recovery.md#5-disaster-recovery-data-requirements).
- **Why it matters:** DR infrastructure has real cost; provisioning it before real operational experience risks over- or under-building it.
- **Options:** Before pilot; after pilot, informed by real risk assessment.
- **Recommended direction:** After the pilot, once real operational stakes and risk tolerance are better understood — consistent with [../01-architecture/environment-and-configuration-model.md, Section 1](../01-architecture/environment-and-configuration-model.md#1-environment-model)'s "not every environment must exist immediately."
- **Evidence required:** Pilot-meet risk assessment.
- **Decision owner:** To be identified (DepEd Leadership, ICT Committee)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### PD-26 — Scenario-Builder Test Utility
- **Question:** Is a shared "scenario builder" utility (composing multiple factories into a realistic full-meet test scenario) built, or do per-module factories suffice?
- **Areas affected:** [test-seed-and-reference-data-strategy.md, Section 5](test-seed-and-reference-data-strategy.md#5-open-questions).
- **Why it matters:** A scenario builder reduces duplication across integration tests that need a realistic cross-context dataset (e.g., a full registration-through-result test), at the cost of an additional test-infrastructure investment.
- **Options:** Per-module factories only; a shared scenario-builder utility.
- **Recommended direction:** Start with per-module factories; introduce a scenario builder once cross-context integration tests demonstrate real duplication pain.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (QA lead / software architect)
- **Target phase:** Phase 0.6 / first implementation phase
- **Status:** Open — recommended direction stated

### PD-27 — Masked-Production-Data Approval Process
- **Question:** What is the formal approval process for using masked/de-identified production data in a lower environment?
- **Areas affected:** [test-seed-and-reference-data-strategy.md, Section 3](test-seed-and-reference-data-strategy.md#3-test-data-strategy).
- **Why it matters:** This is explicitly an exceptional practice, not a default — the approval gate needs to be real, not rubber-stamped, given the sensitivity of PMMS's underlying data.
- **Options:** None yet defined.
- **Recommended direction:** None — requires a named approval authority (likely Security Administrator + Data Privacy and Legal Stakeholders jointly) before this practice is ever used, not merely a documented preference.
- **Evidence required:** Security/privacy policy definition.
- **Decision owner:** To be identified (Security Administrator, Data Privacy and Legal Stakeholders)
- **Target phase:** Phase 0.6
- **Status:** Open

---

## Summary of Blocking / High-Priority Data Decisions

- **PD-04** — Retention periods across 8 categories, blocking Phase 0.6 partitioning/archival physical design, mirrors [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements) and [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).
- **PD-08** — Formal classification-tier validation, since every access/encryption/logging rule in this documentation package keys off it.
- **PD-23** — RPO/RTO numeric targets, mirrors [../01-architecture/runtime-open-decisions.md, RD-18](../01-architecture/runtime-open-decisions.md#rd-18--rporto-targets).

These should be prioritized for DepEd/security/privacy consultation before Phase 0.6 physical schema design proceeds.
