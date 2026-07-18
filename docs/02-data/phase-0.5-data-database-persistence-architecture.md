# PMMS Phase 0.5 — Data, Database, Persistence, and Information Lifecycle Architecture

## 1. Document Control

| Field | Value |
|---|---|
| Document title | PMMS Phase 0.5 — Data, Database, Persistence, and Information Lifecycle Architecture |
| Project | Provincial Meet Management System (PMMS) |
| Phase | Phase 0.5 — Data, Database, Persistence, and Information Lifecycle Architecture |
| Version | 0.5.0 |
| Status | Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation |
| Date | 2026-07-14 |
| Intended audience | Software architects, data architects, Laravel developers, database engineers, security engineers, QA engineers, DevOps engineers, reporting specialists, privacy stakeholders, future implementation teams |
| Document owner | To be identified (data architect) |
| Review roles | To be identified — data architect, security architect, Data Privacy and Legal Stakeholders, DepEd records-management authority, software architect |
| Related documents | [logical-data-architecture.md](logical-data-architecture.md), [persistence-ownership-map.md](persistence-ownership-map.md), [conceptual-schema-catalog.md](conceptual-schema-catalog.md), [database-naming-and-design-standards.md](database-naming-and-design-standards.md), [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md), [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md), [high-integrity-data-model.md](high-integrity-data-model.md), [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md), [information-classification-and-privacy.md](information-classification-and-privacy.md), [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md), [retention-archival-and-disposal.md](retention-archival-and-disposal.md), [import-export-and-data-exchange.md](import-export-and-data-exchange.md), [identity-resolution-and-duplicate-management.md](identity-resolution-and-duplicate-management.md), [public-reporting-and-projection-data.md](public-reporting-and-projection-data.md), [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md), [transaction-concurrency-and-locking.md](transaction-concurrency-and-locking.md), [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md), [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md), [test-seed-and-reference-data-strategy.md](test-seed-and-reference-data-strategy.md), [data-open-decisions.md](data-open-decisions.md), [README.md](README.md), [../01-architecture/phase-0.4-application-integration-runtime-architecture.md](../01-architecture/phase-0.4-application-integration-runtime-architecture.md), [../../.ai/decisions/ADR-0005-data-database-and-information-lifecycle-architecture.md](../../.ai/decisions/ADR-0005-data-database-and-information-lifecycle-architecture.md) |

### Change History

| Version | Date | Description |
|---|---|---|
| 0.5.0 | 2026-07-14 | Initial Phase 0.5 draft: logical data architecture, persistence ownership, conceptual schema catalog, naming/identifier standards, temporal/versioning model, high-integrity data model, audit/security data architecture, classification/privacy, object metadata, retention/archival, import/export, identity resolution, public/reporting data, offline sync/conflict model, transaction/concurrency, indexing/capacity, backup/recovery, test/seed data strategy, and open decisions — built from the approved Phase 0.1–0.4 foundation. |

---

## 2. Executive Summary

Phase 0.4 gave PMMS's 34 bounded contexts a concrete Laravel module structure and runtime execution model, but deliberately deferred the question this phase answers: **what does the database actually look like?** Not the physical migrations — those remain Phase 0.6's responsibility — but the logical architecture, standards, and lifecycle rules that make Phase 0.6's migrations consistent, correct, and durable.

**Why PMMS requires an explicit data architecture before migration generation.** Thirty-four modules, each independently generating migrations without a shared naming convention, identifier strategy, or versioning discipline, would produce a schema that *works* in the narrow sense of passing tests, while quietly violating the ownership and integrity guarantees established since Phase 0.2. A `status` column here, a `state` column there, one module using auto-increment IDs and another using UUIDs inconsistently, one context soft-deleting eligibility decisions and another hard-deleting them — none of these individually breaks anything on day one, and all of them become expensive, data-loss-risking problems to fix once real meets depend on the schema. Phase 0.5 exists to make these decisions once, consistently, before that cost is incurred.

**Why data ownership must follow bounded contexts.** [persistence-ownership-map.md](persistence-ownership-map.md) is the direct, non-negotiable descendant of [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md) (Phase 0.2) — a schema that lets Finance write to a Scoring table, or lets Public Information hold its own copy of an Official Result, would silently undo two phases of architectural work the moment the first migration ran.

**Why athlete, eligibility, scoring, results, medal tally, medical, accreditation, finance, and audit data require stronger controls.** These are the same 11 high-integrity domains named consistently since Phase 0.2 — [high-integrity-data-model.md](high-integrity-data-model.md) is where "no silent mutation" and "correction supersedes, never overwrites" (both established in [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)) become concrete persistence patterns: append-only history tables, explicit version chains, and a data-shape that makes separation-of-duties violations structurally awkward rather than merely policy-discouraged.

**Why MySQL is the authoritative transactional store.** Confirmed across every prior phase and restated here as the unambiguous [source-of-truth model](logical-data-architecture.md#2-source-of-truth-model): MySQL is the only component in the approved stack with the relational integrity, transactional guarantees, and Laravel-native tooling PMMS's high-integrity workflows require.

**Why Redis, MinIO, public projections, search indexes, and offline mobile stores have different responsibilities.** Each store in [logical-data-architecture.md, Section 2](logical-data-architecture.md#2-source-of-truth-model) is good at exactly one thing MySQL is not — and collapsing that distinction (Redis becoming a quasi-database, a public projection becoming a second source of truth) is precisely the failure mode this phase, and the working rules governing it, exist to prevent.

**Why historical meet data must remain reproducible and traceable.** Per [retention-archival-and-disposal.md, Section 3](retention-archival-and-disposal.md#3-meet-closure-and-historical-preservation): a report generated from a 2026 meet's data must produce the same figures in 2036 as it did in 2026, even after later corrections, renamings, or rule revisions — achievable only because every historical reference points at a specific, immutable version, never a live "current value" lookup.

**Why a commercial-quality platform needs defined retention, versioning, correction, import, export, and recovery rules.** This is the persistence-layer expression of the commercial-quality direction established in [Phase 0.1, Section 18](../00-product/phase-0.1-product-foundation.md#18-commercial-quality-product-direction) — a working schema without these disciplines is a prototype; a schema with them is an institutional platform DepEd can actually depend on across meet cycles and, eventually, years.

---

## 3. Data Architecture Goals

One authoritative owner per major data concept · strong referential integrity · historical traceability · high-integrity workflow protection · privacy and security by design · efficient operational querying · public scalability · reliable reporting · offline synchronization support · multi-meet readiness · multi-organization readiness · data portability · controlled imports and exports · auditability · recoverability · maintainable schema evolution · commercial product reuse. Full principle-by-principle treatment: [logical-data-architecture.md, Section 1](logical-data-architecture.md#1-data-architecture-principles).

---

## 4. Data Architecture Principles

Twenty core principles — MySQL as authoritative store, Redis as transient, MinIO storing content not ownership, one owner per concept, downstream/non-authoritative public and reporting models, controlled correction over destructive overwrite, explicit business states, classification-driven access, minimized sensitive data, safe external identifiers, non-shared cross-context ownership, constraint discipline, application-owned complex rules, no trigger-hidden workflows, forward-compatible schema evolution, documented rollback limitations, policy-respecting retention, and protected test data — are defined in full in [logical-data-architecture.md, Section 1](logical-data-architecture.md#1-data-architecture-principles).

---

## 5. Source-of-Truth Model

MySQL is authoritative for organizations, meets, people/participants, user-account relationships, roles/permissions/scopes/assignments, registrations, eligibility decisions, competition entries, tournament structures, scores, official results, protests, medal awards/certified tally, accreditation metadata, access scans, medical operational records, logistics records, finance records, document metadata, audit events, retained notification state, and synchronization/integration state. Redis holds nothing durable. MinIO holds object content only. Mobile local stores, public projections, reporting stores, and search indexes are all downstream and rebuildable. Full table: [logical-data-architecture.md, Section 2](logical-data-architecture.md#2-source-of-truth-model).

---

## 6. Persistence Ownership by Bounded Context

Every one of the 34 approved bounded contexts has an explicit persistence-ownership entry — authoritative records, referenced records, sensitivity, high-integrity status, public exposure, offline replication allowance, retention importance, and correction/archival authority — in [persistence-ownership-map.md](persistence-ownership-map.md). Two contexts (Public Information, BC-29; Reporting and Analytics, BC-33) own zero authoritative persistence by design, the persistence-layer proof that their Phase 0.2 "non-authoritative" designation is real.

---

## 7. Conceptual Schema Groups

34 schema groups, one per bounded context, each documenting purpose, key aggregate candidates, high-integrity level, sensitivity, history requirement, public exposure, offline relevance, expected growth, and open questions: [conceptual-schema-catalog.md, Part 1](conceptual-schema-catalog.md#part-1--conceptual-schema-groups). **No complete column list is defined for any group.**

---

## 8. Aggregate Persistence Boundaries

36 major aggregate candidates (`Organization`, `Meet`, `Participant`, `EligibilityCase`, `ScoreRecord`, `OfficialResult`, `ProtestCase`, `MedalAward`, `AccreditationCredential`, `AccessScan`, `MedicalEncounter`, `AuditEvent`, and more), each with owning context, persistence responsibility, transaction boundary, history/versioning requirement, deletion behavior, audit requirement, offline relevance, volume category, and validation needs: [conceptual-schema-catalog.md, Part 2](conceptual-schema-catalog.md#part-2--aggregate-persistence-boundaries).

---

## 9. Identifier Strategy

**Recommended direction:** unsigned BIGINT auto-increment internal primary keys for high-volume relational efficiency, paired with ULID-based public identifiers for externally exposed, synchronization-sensitive, or offline-originated records — with offline/device-originated records (Scoring, Access Validation) using a client-generated ULID as their permanent identity from the moment of capture, resolving the offline-ID-generation problem directly. Full rationale and identifier-category taxonomy (internal key, public ID, natural key, external source ID, legacy ID, idempotency key, correlation ID, device-generated ID, import batch ID, version ID): [identifier-and-reference-strategy.md, Sections 1–2](identifier-and-reference-strategy.md#1-internal-primary-keys--recommended-direction).

## 10. Public Identifier Rules

Internal sequential IDs are never a security boundary; public IDs are non-guessable, stable, and carry no sensitive meaning; QR tokens are structurally distinct from record IDs; accreditation/athlete numbers are human-readable but never authentication secrets; deleted/archived IDs are never silently reused. Full rule set: [identifier-and-reference-strategy.md, Section 3](identifier-and-reference-strategy.md#3-public-identifier-rules).

## 11. Cross-Context Reference Strategy

Seven approved patterns (authoritative external ID, local projection, immutable snapshot, application-validated reference, same-boundary foreign key, no cross-context ORM mutation, no cross-context cascading delete), applied to nine worked examples spanning the full Registration→Eligibility→Entries→Scoring→Results→Tally chain: [identifier-and-reference-strategy.md, Section 4](identifier-and-reference-strategy.md#4-cross-context-reference-strategy).

## 12. Cross-Context Reference Rules (Foreign Keys vs. Application-Validated)

Same-context, non-polymorphic references prefer real database foreign keys; genuinely polymorphic cross-context references (e.g., Document and Records' variable owning-aggregate type) are application-validated with orphan-detection compensating for the integrity a native foreign key cannot express across an unconstrained type. Full treatment: [identifier-and-reference-strategy.md, Section 5](identifier-and-reference-strategy.md#5-foreign-keys-vs-application-validated-references).

---

## 13. Database Naming Standards

Plural snake_case tables, singular snake_case foreign keys, consistent `_id`/`_at`/`_on`/`_status`/`_type`/`_version`/`_count`/`_amount` suffixes, no bare `data`/`info`/`details`/`value`, no shared global `status` vocabulary, no ambiguous `type` columns. Full standard: [database-naming-and-design-standards.md, Sections 1–2](database-naming-and-design-standards.md#1-table-naming).

## 14. Column Naming Standards

See Section 13 above and [database-naming-and-design-standards.md, Section 2](database-naming-and-design-standards.md#2-column-naming) for the complete purpose-to-convention table.

## 15. Index Naming Standards

`idx_<table>_<columns>` convention; Laravel's default constraint-naming is accepted as consistent with this pattern. Full indexing strategy: [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md).

## 16. Constraint Naming Standards

`uq_<table>_<columns>` for unique constraints, `fk_<table>_<referenced_table>` for foreign keys. See [database-naming-and-design-standards.md, Section 8](database-naming-and-design-standards.md#8-index-constraint-and-naming-suffixes-summary).

## 17. Timestamp Standards

All timestamps stored in UTC; date-only distinguished from date-time; scheduled vs. actual time distinguished; system-recorded vs. user-declared occurrence time distinguished; device-occurred and server-received time both preserved for offline records; client clocks never trusted as sole ordering authority. Full standard: [database-naming-and-design-standards.md, Section 4](database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards).

## 18. Time-Zone Standards

Display in meet/user local time zone at the presentation layer only; a meet's time zone is explicit, stored on the `Meet` aggregate, never inferred from server location. See Section 17 above.

## 19. Monetary-Value Standards

Fixed-precision decimal only, never floating-point; explicit currency; original vs. approved amount stored separately; budget/obligation/disbursement/advance/liquidation/reimbursement/adjustment kept as distinct concepts; no invented government accounting rules. Full standard: [database-naming-and-design-standards.md, Section 5](database-naming-and-design-standards.md#5-monetary-value-standards).

## 20. Measurement and Score-Value Standards

Normalized machine-comparable values preserved alongside display values and units; original entered value, device source, precision, and rule-set version all preserved; no universal "score" column across sports; no floating-point storage for precision-sensitive measurements; no invented scoring formula. Full standard: [database-naming-and-design-standards.md, Section 6](database-naming-and-design-standards.md#6-score-measurement-and-timing-standards).

---

## 21. Status and State-Transition Persistence

Every context owns its own status vocabulary; high-integrity transitions require explicit state-history tables, not merely a mutated current-status column; distinct concepts (cancellation, rejection, revocation, withdrawal, expiration, supersession, deletion) are never collapsed. Full rules: [database-naming-and-design-standards.md, Section 3](database-naming-and-design-standards.md#3-status-and-state-transition-persistence) and [temporal-history-and-versioning-model.md, Section 1](temporal-history-and-versioning-model.md#1-state-and-status-persistence).

## 22. Soft-Delete Standards

Appropriate for draft/recoverable/low-stakes records only; **prohibited** for official scores, certified results, eligibility decisions, protest decisions, medal awards, access scans, medical encounters, financial records, audit events, and assignment history, which instead use cancellation, revocation, reversal, supersession, inactivation, archival, or correction versioning. Full treatment: [temporal-history-and-versioning-model.md, Section 2](temporal-history-and-versioning-model.md#2-soft-deletion).

## 23. Immutable-History Standards

18 append-only candidates (audit events, security events, access scans, score submissions/corrections, result certifications/supersessions, protest decisions, medal tally certifications, eligibility decision history, accreditation issuance/revocation, medical encounter history, financial approval history, assignment lifecycle history, device credential history, import history, consequential AI recommendations, publication history), with append-only intent, controlled correction references, integrity-check candidates, export restrictions, and administrative access restrictions defined for each. Full treatment: [temporal-history-and-versioning-model.md, Section 3](temporal-history-and-versioning-model.md#3-immutable-history-and-append-only-records).

## 24. Versioning and Supersession

13 versioned concepts (eligibility decisions, documents, sports rules, tournament configurations, schedules, scores, official results, public projections, medal tallies, accreditation templates, report templates, publication items, API contracts, import templates), each with version identity, current/previous tracking, effective period, actor/reason, approval, publication status, and rollback-limitation columns defined: [temporal-history-and-versioning-model.md, Section 4](temporal-history-and-versioning-model.md#4-versioning-and-supersession).

## 25. Effective-Dating

15 concepts requiring effective dating (organization hierarchy, role/meet/committee/delegation/official assignments, credential validity, sports rule references, eligibility requirement sets, venue availability, schedule changes, publication windows, access restrictions, device assignments, feature configuration), with effective-from/until, recorded-at, superseded-at, source, reason, overlap rules, and historical-query expectations: [temporal-history-and-versioning-model.md, Section 5](temporal-history-and-versioning-model.md#5-temporal-and-effective-dated-data).

## 26. Temporal-Data Requirements

See Section 25 above.

---

## 27. High-Integrity Data Architecture

Dedicated persistence treatment for Participant Identity, Athlete Registration, Eligibility, Scoring, Official Results, Protest and Appeals, Medal Tally, Accreditation, Medical Operations, Finance, and Audit — each with its specific append-only/versioning/effective-dating mechanism, plus cross-cutting data-quality and correction-architecture principles: [high-integrity-data-model.md](high-integrity-data-model.md). Every domain marked **Critical** retains its full Phase 0.1–0.4 blocking dependency (OD-07/08/09/12/15) — Phase 0.5 defines storage shape, never invented criteria.

## 28. Audit-Data Architecture

`AuditEvent` is append-only with zero exceptions — the only domain where "correction authority" is genuinely "none." Full treatment, including candidate tamper-evidence mechanisms and privileged-access recommendations: [audit-and-security-data-architecture.md, Section 1](audit-and-security-data-architecture.md#1-audit-data-architecture).

## 29. Security-Event Storage

Distinct from general audit events though sharing the append-only discipline; classified with explicit severity/category; retained with equal or greater rigor than general audit events. Full treatment: [audit-and-security-data-architecture.md, Section 2](audit-and-security-data-architecture.md#2-security-event-storage).

---

## 30. File and Object Metadata

22 authoritative metadata concepts (object identifier, storage provider, bucket, object key, filenames, MIME type, size, checksum, classification, owning context/aggregate, uploader, version, retention category, legal hold, scan/encryption/public/archived/deleted status, access policy, last-verified time) and category-specific deletion behavior: [object-metadata-and-file-lifecycle.md, Sections 1–2](object-metadata-and-file-lifecycle.md#1-authoritative-metadata-concepts).

## 31. MinIO Object-Reference Persistence

Database metadata remains authoritative for ownership/access; object keys never expose sensitive data; direct URLs are never authoritative; reconciliation and orphan/missing-object detection are required, not optional. Full rules and reconciliation process: [object-metadata-and-file-lifecycle.md, Sections 3–4](object-metadata-and-file-lifecycle.md#3-rules-restated-and-extended-from-phase-04).

---

## 32. Sensitive-Data Classification

The five-tier classification model (Public/Internal/Confidential/Restricted/Highly Restricted) is carried forward unchanged from [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 21](../01-architecture/phase-0.3-access-and-assignment-architecture.md#21-data-classification-model) and applied at the persistence layer — storage, encryption, logging, export, public-exposure, offline-replication, retention, and incident-response expectations per tier: [information-classification-and-privacy.md, Sections 1–2](information-classification-and-privacy.md#1-classification-tiers). **All classifications remain proposed, requiring formal privacy and security validation (PD-08).**

## 33. Personal and Minor-Athlete Data

Data minimization, purpose limitation, verified guardian relationships, consent-reference retention, restricted public profiles, age-sensitive visibility readiness, limited contact exposure, controlled photo publication, export restrictions, and safe test data — no legal conclusion stated, requiring Data Privacy and Legal Stakeholder validation. Full treatment: [information-classification-and-privacy.md, Section 3](information-classification-and-privacy.md#3-minor-and-athlete-data-protection).

## 34. Medical-Data Segregation

Full dedicated treatment in [high-integrity-data-model.md, "Medical Operations"](high-integrity-data-model.md#medical-operations-bc-21--critical-highly-restricted) — Highly Restricted classification, ACL-derived status flag as the *only* cross-context exposure, emergency-access auditing, and a blocking dependency on [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).

## 35. Eligibility-Document Segregation

Full dedicated treatment in [high-integrity-data-model.md, "Eligibility"](high-integrity-data-model.md#eligibility-bc-09--critical) — Restricted classification, evidence referenced (never embedded) via Document and Records, and a blocking dependency on [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).

## 36. Financial-Data Segregation

Full dedicated treatment in [high-integrity-data-model.md, "Finance"](high-integrity-data-model.md#finance-bc-26) — distinct tables for allocation/expense/approval, fixed-precision monetary storage, SOD-06-enforcing data shape.

## 37. Encryption Requirements

Candidate controls (in-transit, at-rest, field-level for select Highly Restricted values, object-storage encryption, encrypted mobile stores, key rotation, key/data separation, hashing over reversible storage where possible, backup/export encryption) evaluated, not selected: [audit-and-security-data-architecture.md, Section 3](audit-and-security-data-architecture.md#3-encryption-and-sensitive-data). **No cryptographic algorithm chosen.**

## 38. Data Masking and Redaction

UI, report, log, export, support-access, test-environment, public-projection, and audit-export masking/redaction rules, each tied to the classification tier of the underlying data: [audit-and-security-data-architecture.md, Section 4](audit-and-security-data-architecture.md#4-data-masking-and-redaction).

---

## 39. Retention Requirements

15 retention categories (temporary uploads through cache/transient data), each with business purpose, retention-authority placeholder, minimum/maximum retention placeholder, archival requirement, deletion method, legal-hold consideration, post-archival access, review owner, and validation status — **every numeric period explicitly marked as pending DepEd/legal input, none invented.** Full table: [retention-archival-and-disposal.md, Section 1](retention-archival-and-disposal.md#1-retention-categories).

## 40. Archiving

Meet closure archive package, historical read-only mode, database/document/projection/media archival, audit preservation, disposal approval, deletion evidence, orphan cleanup, revoked-account handling, de-identified analytics readiness, legal/operational holds, and rehydration requirements: [retention-archival-and-disposal.md, Section 2](retention-archival-and-disposal.md#2-archiving).

## 41. Legal and Operational Holds

A hold flag overrides any retention-driven deletion schedule regardless of category — restated at [object-metadata-and-file-lifecycle.md, Section 1](object-metadata-and-file-lifecycle.md#1-authoritative-metadata-concepts) and [retention-archival-and-disposal.md, Section 2](retention-archival-and-disposal.md#2-archiving).

## 42. Backup and Restore Data Requirements

Data-category-tiered backup priority (Official Results/Audit/Eligibility highest; Redis explicitly never backed up as authoritative), backup verification, restore drills, offsite storage, and classification-driven backup access control: [backup-restore-and-data-recovery.md, Sections 1–3](backup-restore-and-data-recovery.md#1-backup-coverage-by-data-category). **No numerical RPO/RTO invented** — restated from Phase 0.4, extended here with priority tiering.

---

## 43. Data Import Architecture

13 import categories, a 14-stage lifecycle (Upload through Archive source), and rules requiring validation-before-write, idempotency, row-level error reporting, source-reference preservation, reversibility where feasible, normal domain-rule application post-commit, restricted access for sensitive imports, chunking, and template versioning: [import-export-and-data-exchange.md, Section 1](import-export-and-data-exchange.md#1-import-architecture).

## 44. Data Export Architecture

12 export categories and rules requiring authorization/classification respect, reasoned sensitive exports, asynchronous generation for large exports, file expiry, activity auditing, public-projection-only public exports, CSV formula-injection mitigation, format-specific treatment, and freshness/filtering disclosure: [import-export-and-data-exchange.md, Section 2](import-export-and-data-exchange.md#2-export-architecture).

## 45. Bulk Ingestion

Chunking requirement restated from [../01-architecture/event-and-queue-architecture.md, Section 6](../01-architecture/event-and-queue-architecture.md#6-chunking-and-bulk-work) (Phase 0.4), applied specifically to the import lifecycle in [import-export-and-data-exchange.md, Section 1](import-export-and-data-exchange.md#1-import-architecture).

## 46. Duplicate Detection

See Section 47 below — duplicate detection is the entry point into identity resolution, not a separate mechanism.

## 47. Identity Resolution

Matching factors, match/duplicate states (exact/probable/possible/confirmed/rejected/conflict), merge/unmerge as attributed, retained decisions (never a destructive delete), and the restated AI boundary ("AI may suggest duplicates but must not autonomously merge high-impact identity records"): [identity-resolution-and-duplicate-management.md](identity-resolution-and-duplicate-management.md).

---

## 48. Historical Meet Preservation

At meet closure, meet configuration, organization/delegation snapshots, participation records, eligibility decisions, entries, tournament structures, schedules, official assignments, scores/corrections, certified results, protest decisions, medal awards/tally, accreditation issuance, access records (where retained), medical summaries (privacy-filtered), committee/financial reports, official documents, publication history, audit events, and material rule-set versions are all preserved as a coherent, permanent record. Full treatment: [retention-archival-and-disposal.md, Section 3](retention-archival-and-disposal.md#3-meet-closure-and-historical-preservation).

## 49. Cross-Meet Data Reuse

Reusable master data (Organizations, Schools, People, User Accounts, Officials, Sports Catalog, Venue Catalog, reference values) persists across cycles; meet-specific data is always freshly established per meet, never silently inherited; prior eligibility/assignments never automatically carry forward. Full principles: [logical-data-architecture.md, Section 4](logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles).

## 50. Multi-Organization Readiness

A logical property (every meet/organization-scoped table conceptually carries an organization-ownership path), not a launch requirement — no physical tenant-isolation mechanism selected. Full treatment: [logical-data-architecture.md, Section 4](logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles) and [data-open-decisions.md, PD-01](data-open-decisions.md#pd-01--tenant-column-timing).

## 51. Tenant Isolation Readiness

See Section 50 above.

---

## 52. Public Projections

Eight named public projections (schedule, result, medal tally, athlete profile, delegation, venue, announcement, tournament progress), each rebuildable, version-carrying, freshness-carrying, and privacy-filtered at build time — never at read time. Full rules: [public-reporting-and-projection-data.md, Section 1](public-reporting-and-projection-data.md#1-public-projections).

## 53. Reporting Read Models

20 candidate read models (meet readiness through post-event analytics), each with source contexts, update frequency, freshness expectation, rebuildability, sensitivity, public eligibility, historical-snapshot need, and failure behavior: [public-reporting-and-projection-data.md, Section 2](public-reporting-and-projection-data.md#2-read-models-and-analytics).

## 54. Search Indexes

Staged, evidence-driven approach (MySQL-backed first, dedicated engine only when justified), never provisioned prematurely: [public-reporting-and-projection-data.md, Section 3](public-reporting-and-projection-data.md#3-search-indexes).

## 55. Cache Boundaries

Public projections and read models are natural cache targets given their "near-real-time, not instantaneous" freshness requirement; authoritative data is never cached as a substitute for a real query where correctness matters. Cross-reference: [public-reporting-and-projection-data.md, Section 4](public-reporting-and-projection-data.md#4-cache-boundaries-cross-reference) and [../01-architecture/caching-and-session-architecture.md](../01-architecture/caching-and-session-architecture.md) (Phase 0.4).

---

## 56. Offline Replication

Narrowly-scoped replicable categories (authorization snapshot, device/meet/venue identity, sport/event references, assigned participants, cached credential-validity set, schedule, assigned competition units, meal/billeting/transport assignments, limited medical alerts) versus explicitly prohibited categories (full medical records, full eligibility evidence, full finance attachments, platform-wide directory, unrelated delegations, privileged audit exports, authentication secrets). Full treatment: [offline-sync-and-conflict-data-model.md, Section 1](offline-sync-and-conflict-data-model.md#1-offline-replication-data).

## 57. Synchronization Metadata

19 conceptual fields (sync batch, client/device/user/meet ID, local/server record ID, idempotency key, operation type, client/schema/rule-set version, client-occurred/server-received/accepted time, status, conflict type, resolution, retry count, error category, payload hash, authorization snapshot version): [offline-sync-and-conflict-data-model.md, Section 2](offline-sync-and-conflict-data-model.md#2-synchronization-metadata).

## 58. Conflict-Resolution Data

14 conflict categories and 9 resolution outcomes, with high-integrity conflicts always requiring server authority and human review, never mechanical auto-resolution: [offline-sync-and-conflict-data-model.md, Section 3](offline-sync-and-conflict-data-model.md#3-conflict-resolution-data).

## 59. Idempotency Data

13 operations requiring idempotency and the conceptual key/hash/actor/scope/operation/expiry/response-reference/conflict-behavior/retention model, with Redis as a candidate transient store given idempotency keys' inherently time-bounded, safely-retryable nature: [offline-sync-and-conflict-data-model.md, Section 4](offline-sync-and-conflict-data-model.md#4-idempotency-data).

## 60. Outbox and Event Persistence Evaluation

Evaluated for certified-result, medal-tally, projection-publication, credential-revocation, security-alert, notification, integration, and reporting events — **not implemented.** Recommended direction: start with Laravel's `after_commit` dispatch semantics, adopt a formal outbox only if measured event-loss incidents demonstrate the need. Full evaluation: [transaction-concurrency-and-locking.md, Section 5](transaction-concurrency-and-locking.md#5-outbox-and-event-persistence-evaluation).

---

## 61. Transaction Boundaries

A transaction normally remains within one bounded context; remote calls never occur inside a long transaction; external side effects happen after safe persistence; cross-context workflows use staged states/events/orchestration, never distributed transactions; failed transitions leave unambiguous state. Full treatment: [transaction-concurrency-and-locking.md, Section 1](transaction-concurrency-and-locking.md#1-transaction-boundaries).

## 62. Concurrency Control

Optimistic locking (version columns) as the default for high-integrity aggregates, unique constraints, database transactions, narrowly-scoped pessimistic locks, Redis distributed locks for cross-request coordination (never as the authoritative correctness mechanism), idempotency keys, and state-condition updates. Full treatment: [transaction-concurrency-and-locking.md, Section 2](transaction-concurrency-and-locking.md#2-concurrency-control).

## 63. Locking Strategy

See Section 62 above.

## 64. Optimistic Versioning

Version-column pattern applied at minimum to every aggregate marked **Critical** in [conceptual-schema-catalog.md](conceptual-schema-catalog.md); see [data-open-decisions.md, PD-20](data-open-decisions.md#pd-20--optimistic-locking-scope) for the uniform-vs-selective open question.

## 65. Result and Tally Integrity

Medal Tally reads exclusively from certified/published Official Results, enforced by reference pattern (never a raw-score reference); result certification verifies score-validation state within the same atomic transaction. Full treatment: [transaction-concurrency-and-locking.md, Section 3](transaction-concurrency-and-locking.md#3-result-and-tally-integrity-persistence-expression).

## 66. Referential Integrity

Same-context foreign keys use real database constraints with explicit (usually `RESTRICT`, rarely `CASCADE`) behavior; cross-context deletion cascades are avoided entirely; historical records use restrict/nullify/snapshot/archive strategies; orphan prevention is mandatory for file metadata, assignments, and high-integrity records; imported external references carry source-system qualifiers. Full treatment: [transaction-concurrency-and-locking.md, Section 4](transaction-concurrency-and-locking.md#4-referential-integrity).

## 67. Data Quality Controls

Ten quality dimensions (completeness, validity, consistency, uniqueness, timeliness, accuracy, traceability, referential integrity, conformance, reproducibility) mapped to their specific controls across registration, eligibility, entries, scoring, results, tally, accreditation, logistics, finance, documents, and public projections: [high-integrity-data-model.md, "Data Quality Controls"](high-integrity-data-model.md#data-quality-controls).

## 68. Data Correction Architecture

Twelve correction mechanisms (draft edit through data repair under support control), required fields for any correction (actor, reason, time, approval, before/after references, affected projections, recalculation/notification triggers, audit), and an explicit prohibition on direct production database edits outside a documented emergency-repair procedure. Full treatment: [high-integrity-data-model.md, "Data Correction Architecture"](high-integrity-data-model.md#data-correction-architecture).

---

## 69. Data Migration Readiness

Nine migration categories and a 13-phase migration lifecycle (Discovery through Archive), with source-inventory, data-owner, mapping-rule, exception-handling, rollback, reconciliation, sign-off, and security-review requirements — **no migration performed in this phase**, and historical-data existence itself is unconfirmed (per [Phase 0.1 product-scope.md, Section 14](../00-product/product-scope.md#14-data-migration-scope)). Full treatment: [import-export-and-data-exchange.md, Section 3](import-export-and-data-exchange.md#3-data-migration-readiness-future-historical-data).

## 70. Seed-Data Categories

Platform, organization, sports, and meet-configuration reference data, each with a named owner, versioning candidacy, and — for policy-sensitive data specifically — a mandatory source citation, never an invented value. Full treatment: [test-seed-and-reference-data-strategy.md, Section 1](test-seed-and-reference-data-strategy.md#1-reference-and-seed-data-classification).

## 71. Test-Data Strategy

14 test-data categories and rules requiring synthetic-only data by default, formally-approved masking for any production-derived exception, full state-transition coverage, preserved history for high-integrity scenarios, and reproducible performance datasets. Full treatment: [test-seed-and-reference-data-strategy.md, Section 3](test-seed-and-reference-data-strategy.md#3-test-data-strategy).

---

## 72. Performance and Indexing Principles

Foreign-key, scope-filter, active-assignment, participant/public-identifier, status-plus-scope, schedule-time-range, tournament-relationship, result-publication, access-scan, sync-status, and audit-dimension indexing, balanced against write-amplification review and query-plan validation discipline. Full treatment: [indexing-performance-and-capacity.md, Section 1](indexing-performance-and-capacity.md#1-indexing-strategy).

## 73. Partitioning and Archival Readiness

No physical partitioning selected; the very-high-volume/long-term-historical tables (`ScoreRecord`, `AccessScan`, `AuditEvent`, result/tally history) are the primary future candidates, supported (not required) by the identifier and versioning strategies already established. Full treatment: [indexing-performance-and-capacity.md, Section 3](indexing-performance-and-capacity.md#3-partitioning-and-archival-readiness).

## 74. Database Observability

Query latency, connection-pool utilization, index-usage statistics, table growth rate, replication lag (if introduced), lock-wait/deadlock frequency, and backup job success/duration — extending [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) (Phase 0.4) with database-specific signals. Full treatment: [indexing-performance-and-capacity.md, Section 4](indexing-performance-and-capacity.md#4-database-observability).

## 75. Disaster Recovery Data Requirements

DR replication of "Highest"/"High" priority data tiers at minimum, lock-step MinIO/metadata replication, auditable failover events, and post-failover integrity reconciliation before the DR environment is trusted as primary. Full treatment: [backup-restore-and-data-recovery.md, Section 5](backup-restore-and-data-recovery.md#5-disaster-recovery-data-requirements).

---

## 76. Risks, Assumptions, Tradeoffs, and Open Decisions

### Key Risks
- **Retention-policy vacuum** (PD-04, mirrors [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)) — the single largest gap blocking Phase 0.6 physical partitioning/archival design.
- **Classification model unvalidated** (PD-08) — every access/encryption/logging rule in this package keys off a five-tier model that has not yet received formal privacy/legal sign-off.
- **Identity-resolution accuracy unknown pre-pilot** (PD-16) — duplicate-detection tuning requires real operational data this phase cannot invent.
- **Historical migration scope unconfirmed** (PD-13) — whether legacy spreadsheet data is even migratable remains an open discovery question.

### Key Assumptions
- MySQL, Redis, MinIO retain their Phase 0.1–0.4 approved roles unchanged (confirmed, not merely assumed, throughout this package).
- The 34-bounded-context decomposition from Phase 0.2 remains stable enough to anchor a persistence-ownership map without near-term restructuring.
- ULID (or an equivalent time-sortable identifier) is available in the PHP/Laravel ecosystem with adequate tooling maturity by Phase 0.6.

### Key Tradeoffs
- **BIGINT + public-ID pattern** (Section 9) trades a small amount of schema complexity (two ID columns per table instead of one) for security, offline-compatibility, and multi-organization-merge safety — assessed as clearly worthwhile given PMMS's offline-critical and public-facing surfaces.
- **Deferred outbox pattern** (Section 60) trades a theoretical reliability gap (a crash between commit and event dispatch) for avoiding premature infrastructure investment — accepted given `after_commit` semantics are already framework-native and no evidence of actual event loss exists yet.
- **No physical tenant isolation selected** (Section 50) trades near-term simplicity for a documented, deliberate readiness posture rather than either fully building or fully ignoring multi-organization support.

### Open Decisions
27 data-specific open decisions (PD-01 through PD-27), cross-referenced against Phase 0.1's OD-XX, Phase 0.2's DD-XX, Phase 0.3's AD-XX, and Phase 0.4's RD-XX series: [data-open-decisions.md](data-open-decisions.md).

---

## 77. Phase 0.5 Acceptance Criteria

- [x] Data architecture principles documented (20 principles).
- [x] Logical data domains documented (34 schema groups).
- [x] Persistence ownership by bounded context documented (all 34 contexts).
- [x] MySQL confirmed as authoritative system of record; Redis/MinIO/projection/search boundaries documented.
- [x] Database boundary strategy documented (modular-monolith-consistent, no cross-context table sharing).
- [x] Conceptual schema groups and aggregate persistence boundaries documented (34 groups, 36 aggregates).
- [x] Identifier strategy, public-identifier rules, and cross-context reference strategy documented and reasoned.
- [x] Naming conventions (tables, columns, indexes, constraints) documented.
- [x] Timestamp, time-zone, monetary, and measurement/score standards documented.
- [x] Status/state persistence, soft-delete, immutable-history, versioning, and effective-dating rules documented.
- [x] High-integrity data model documented for all 11 high-integrity domains.
- [x] Audit and security-event data architecture documented.
- [x] Information classification (5 tiers) and minor/athlete data protection documented.
- [x] Object metadata and MinIO reference persistence documented.
- [x] Retention, archival, disposal, and meet-closure preservation documented (no invented periods).
- [x] Import, export, and data-migration-readiness architecture documented.
- [x] Identity resolution and duplicate-management data model documented (AI advisory-only, restated).
- [x] Multi-meet, multi-organization, and tenant-isolation readiness documented (logical only).
- [x] Public projections, reporting read models, search indexes, and cache boundaries documented.
- [x] Offline replication, synchronization metadata, conflict-resolution data, and idempotency data documented.
- [x] Outbox pattern evaluated (not implemented).
- [x] Transaction boundaries, concurrency control, locking strategy, and referential integrity documented.
- [x] Data quality and correction architecture documented (direct production edits prohibited outside documented emergency repair).
- [x] Reference/seed data and test-data strategy documented (no seeders created, no real protected data in test data).
- [x] Indexing, performance, capacity/growth, and database-observability principles documented.
- [x] Backup, restore, and disaster-recovery data requirements documented (no invented RPO/RTO).
- [x] Open decisions recorded (27 items, cross-referenced against all prior phases).
- [x] AI workspace updated.
- [x] No migration, SQL schema, Eloquent model, seeder, factory, or implementation code generated.
- [x] No database package installed; no database connected to or modified.
- [x] No official sports/DepEd/eligibility/scoring/medal/protest/medical/financial/retention rule invented.
- [x] Documents internally consistent (cross-reference verified — see completion report).

## 78. Preparation Requirements for Phase 0.6

Phase 0.6 (physical schema design — table structures, column definitions, migrations) can proceed once it has:

- This package's naming conventions ([database-naming-and-design-standards.md](database-naming-and-design-standards.md)) as a binding style guide.
- The identifier strategy ([identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)) as the default pattern for every new table.
- The persistence-ownership map ([persistence-ownership-map.md](persistence-ownership-map.md)) to know which module owns which table.
- The high-integrity data model ([high-integrity-data-model.md](high-integrity-data-model.md)) to know which tables require append-only/versioned treatment from their very first migration.
- Resolution (or, at minimum, an accepted placeholder plan) for the highest-priority open decisions: **PD-04** (retention periods), **PD-08** (classification validation), **PD-23** (RPO/RTO), plus the still-blocking Phase 0.1 decisions (OD-07/08/09/12/15) that determine several tables' actual authority columns.

Phase 0.5 does not itself perform any of Phase 0.6's work — this section exists so Phase 0.6 does not need to rediscover these foundations.

## 79. Next Phase

```text
Phase 0.6 — Physical Schema and Migration Design
```

Phase 0.6 is not started as part of this task.
