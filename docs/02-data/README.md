# PMMS Data Architecture Documentation — `docs/02-data/`

This directory contains the Phase 0.5 (data, database, persistence, and information lifecycle architecture) documentation for the **Provincial Meet Management System (PMMS)**. It builds directly on the bounded contexts (Phase 0.2), authorization model (Phase 0.3), and application/runtime architecture (Phase 0.4) in [../01-architecture/](../01-architecture/) to define how PMMS's data is logically organized, owned, classified, versioned, protected, and preserved — before any physical schema or migration is designed.

**No migration, SQL schema file, Eloquent model, seeder, factory, fixture, database package installation, database connection/modification, or physical table creation is contained in this directory.** It is data architecture documentation only, per the Phase 0.5 working rules. No official sports/DepEd/eligibility/scoring/medal/protest/medical/financial/retention rule is invented; every such dependency is marked as a placeholder or cross-referenced to the specific open decision that must resolve it first.

## Purpose

Phase 0.5 exists to answer, once and consistently, the questions that 34 independently-migrating Laravel modules would otherwise answer 34 different (and mutually inconsistent) ways: which store is authoritative for what, how records are identified and referenced across context boundaries, how high-integrity data is protected from silent mutation, how classification drives storage/access/encryption/masking, how long data is retained, and how offline, public, and reporting data relate to the authoritative core. See [phase-0.5-data-database-persistence-architecture.md, Section 2](phase-0.5-data-database-persistence-architecture.md#2-executive-summary) for the full rationale.

## Document Index

| Document | Purpose |
|---|---|
| [phase-0.5-data-database-persistence-architecture.md](phase-0.5-data-database-persistence-architecture.md) | Primary Phase 0.5 document: goals/principles, source-of-truth model, persistence ownership, conceptual schema, identifier/naming/temporal/versioning standards, high-integrity data model, audit/security/classification/privacy, object metadata, retention, import/export, identity resolution, public/reporting data, offline/sync/conflict/idempotency, transaction/concurrency, indexing/capacity, backup/recovery, risks/tradeoffs, acceptance/exit criteria |
| [logical-data-architecture.md](logical-data-architecture.md) | 20 data architecture principles; the Source-of-Truth Model (MySQL/Redis/MinIO/mobile/projections/reporting/search); multi-meet and multi-organization readiness principles |
| [persistence-ownership-map.md](persistence-ownership-map.md) | Full persistence-ownership table for all 34 bounded contexts — authoritative records, sensitivity, high-integrity status, correction/archival authority |
| [conceptual-schema-catalog.md](conceptual-schema-catalog.md) | 34 conceptual schema groups plus ~36-row aggregate persistence boundaries table covering every major aggregate |
| [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md) | Internal BIGINT + public ULID identifier strategy, identifier categories, public-ID rules, cross-context reference strategy |
| [database-naming-and-design-standards.md](database-naming-and-design-standards.md) | Table/column naming, status persistence, timestamp/time-zone, monetary, and score/measurement standards |
| [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md) | Soft-deletion rules, immutable/append-only history, versioning and supersession, effective-dated data |
| [high-integrity-data-model.md](high-integrity-data-model.md) | Persistence treatment for the 11 high-integrity domains; data-quality controls; data-correction architecture |
| [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md) | Audit-event and security-event storage; encryption and masking/redaction candidate controls |
| [information-classification-and-privacy.md](information-classification-and-privacy.md) | Five-tier classification model at the persistence layer; minor/athlete data protection; classification-to-persistence mapping |
| [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md) | MinIO object metadata concepts, deletion behavior by document category, reconciliation process |
| [retention-archival-and-disposal.md](retention-archival-and-disposal.md) | Retention categories (placeholders pending DepEd input), archiving, meet closure and historical preservation |
| [import-export-and-data-exchange.md](import-export-and-data-exchange.md) | Import lifecycle, export architecture, data migration readiness |
| [identity-resolution-and-duplicate-management.md](identity-resolution-and-duplicate-management.md) | Matching factors, match/duplicate states, merge/unmerge persistence model, AI boundary |
| [public-reporting-and-projection-data.md](public-reporting-and-projection-data.md) | Public projections, reporting read models, search-index staging, cache-boundary cross-reference |
| [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md) | Offline replication data, synchronization metadata, conflict-resolution data, idempotency data |
| [transaction-concurrency-and-locking.md](transaction-concurrency-and-locking.md) | Transaction boundaries, concurrency control, result/tally integrity, referential integrity, outbox evaluation |
| [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md) | Indexing strategy, capacity/growth categories, partitioning readiness, database observability |
| [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md) | Backup coverage by data category, backup requirements, disaster-recovery data requirements |
| [test-seed-and-reference-data-strategy.md](test-seed-and-reference-data-strategy.md) | Reference/seed data classification, test-data strategy |
| [data-open-decisions.md](data-open-decisions.md) | 27 unresolved data-architecture decisions (PD-01–PD-27), cross-referenced against Phase 0.1–0.4 open decisions |

## Reading Order

1. [phase-0.5-data-database-persistence-architecture.md](phase-0.5-data-database-persistence-architecture.md) — read first; establishes goals and cross-references every supporting document.
2. [logical-data-architecture.md](logical-data-architecture.md) — the foundational source-of-truth model everything else depends on.
3. [persistence-ownership-map.md](persistence-ownership-map.md) and [conceptual-schema-catalog.md](conceptual-schema-catalog.md) — what data exists and who owns it.
4. [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md) and [database-naming-and-design-standards.md](database-naming-and-design-standards.md) — the standards every future table follows.
5. [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md) and [high-integrity-data-model.md](high-integrity-data-model.md) — how PMMS's most sensitive workflows are protected at the data layer.
6. [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md), [information-classification-and-privacy.md](information-classification-and-privacy.md), [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md) — security, privacy, and file-lifecycle detail.
7. [retention-archival-and-disposal.md](retention-archival-and-disposal.md), [import-export-and-data-exchange.md](import-export-and-data-exchange.md), [identity-resolution-and-duplicate-management.md](identity-resolution-and-duplicate-management.md) — lifecycle and data-quality operations.
8. [public-reporting-and-projection-data.md](public-reporting-and-projection-data.md), [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md), [transaction-concurrency-and-locking.md](transaction-concurrency-and-locking.md) — derived data, disconnected operation, and correctness guarantees.
9. [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md), [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md), [test-seed-and-reference-data-strategy.md](test-seed-and-reference-data-strategy.md) — operational readiness.
10. [data-open-decisions.md](data-open-decisions.md) — read last; everything still unresolved.

## Status Legend

| Status | Meaning |
|---|---|
| Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation | Phase 0.5 status: content complete, no formal data-architecture/security/privacy/domain-expert/engineering sign-off yet |
| Validated | Reviewed and confirmed by identified stakeholders (not yet reached for any document) |
| Superseded | Replaced by a later-phase document (not yet applicable) |

## Ownership and Review Expectations

A document owner (data architect) and reviewer set (security architect, Data Privacy and Legal Stakeholders, DepEd records-management authority, software architect, database/engineering leads) are to be identified — see [../00-product/stakeholder-register.md](../00-product/stakeholder-register.md). Until formal review occurs, this documentation should be treated as a structured, defensible proposal built from the approved Phase 0.1–0.4 foundation, not as an approved specification.

## Relationship to Phase 0.2, 0.3, and 0.4

Every persistence-ownership entry traces back to a bounded context in [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md); every high-integrity data protection restates a safeguard first established in [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md); every classification tier is carried unchanged from [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 21](../01-architecture/phase-0.3-access-and-assignment-architecture.md#21-data-classification-model); every source-of-truth boundary (MySQL authoritative, Redis transient, MinIO content-only) restates [../01-architecture/phase-0.4-application-integration-runtime-architecture.md](../01-architecture/phase-0.4-application-integration-runtime-architecture.md). Phase 0.5 does not redefine any of these — it extends them to the persistence layer.

## Relationship to Phase 0.6

**Phase 0.6 — Security, Privacy, Audit, Compliance, and Data Governance Architecture is now complete** — see [../03-security/README.md](../03-security/README.md). Rather than proceeding directly to physical schema design, Phase 0.6 consumed this directory's classification model, retention categories, high-integrity data model, and audit-data architecture to define the security, privacy, and governance controls those physical tables must eventually satisfy. No rule defined in this directory was altered by Phase 0.6's work — [../03-security/audit-and-security-event-architecture.md](../03-security/audit-and-security-event-architecture.md) and [../03-security/information-classification-and-privacy.md](information-classification-and-privacy.md) build directly on, and do not redefine, [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md) and [information-classification-and-privacy.md](information-classification-and-privacy.md).

## Relationship to Phase 0.7

**Phase 0.7 — Quality Engineering, Testing, Validation, and Assurance Architecture is now complete** — see [../04-quality/README.md](../04-quality/README.md). It consumed this directory's high-integrity data model and data-quality controls to define [../04-quality/data-database-migration-and-quality-testing.md](../04-quality/data-database-migration-and-quality-testing.md) and [../04-quality/high-integrity-sports-workflow-testing.md](../04-quality/high-integrity-sports-workflow-testing.md). No rule defined in this directory was altered by Phase 0.7's work.

## Relationship to Phase 0.8

**Phase 0.8 — DevOps, Environment, CI/CD, Deployment, Observability, and Operations Architecture is complete** — see [../05-devops/README.md](../05-devops/README.md). It consumed this directory's backup/restore architecture and indexing/capacity model to define [../05-devops/backup-restore-disaster-recovery-and-continuity.md](../05-devops/backup-restore-disaster-recovery-and-continuity.md) and [../05-devops/service-level-capacity-and-performance-management.md](../05-devops/service-level-capacity-and-performance-management.md). No rule defined in this directory was altered by Phase 0.8's work.

## Relationship to Phase 0.9

**Phase 0.9 — Design System, UX, Accessibility, and Cross-Platform Experience Architecture is now complete** — see [../06-design/README.md](../06-design/README.md). It consumed this directory's classification and publication-state model to define privacy-aware and state-visibility interface rules in [../06-design/privacy-security-and-sensitive-data-experience.md](../06-design/privacy-security-and-sensitive-data-experience.md) and [../06-design/dashboard-table-chart-and-data-visualization-standards.md, Section 4](../06-design/dashboard-table-chart-and-data-visualization-standards.md#4-data-freshness-version-and-state-indication). No rule defined in this directory was altered by Phase 0.9's work.

## Relationship to Phase 0.10

**Phase 0.10 — AI-Assisted Platform Architecture is now complete** — see [../07-ai/README.md](../07-ai/README.md), superseding this section's earlier expectation that Phase 0.10 would begin implementation directly. It consumed this directory's classification model and identity-resolution/duplicate-management design to define AI data-access rules (intersection-not-union, per [../07-ai/ai-identity-authorization-scope-and-audit.md, Section 3](../07-ai/ai-identity-authorization-scope-and-audit.md#3-ai-data-access)) and the duplicate-athlete-detection candidate capability (per [../07-ai/duplicate-athlete-detection-architecture.md, Section 4](../07-ai/duplicate-athlete-detection-architecture.md#4-reviewer-decision-merge-and-unmerge)), which restates this directory's alias/never-delete merge model unchanged. No rule defined in this directory was altered by Phase 0.10's work.

## Relationship to Phase 0.11

**Phase 0.11 — Event-Driven Workflows, Notifications, Messaging, and Responsible Automation Architecture is now complete** — see [../08-workflows/README.md](../08-workflows/README.md), superseding this section's earlier expectation that Phase 0.11 would begin implementation directly. It consumed this directory's [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md) and [transaction-concurrency-and-locking.md](transaction-concurrency-and-locking.md) to define workflow-state versioning, correction/supersession discipline, and concurrency handling for automation conflicts — see [../08-workflows/workflow-versioning-migration-and-active-instance-compatibility.md](../08-workflows/workflow-versioning-migration-and-active-instance-compatibility.md) and [../08-workflows/responsible-automation-and-authority-boundaries.md, Section 9](../08-workflows/responsible-automation-and-authority-boundaries.md#9-automation-conflict-handling-and-escalation). No rule defined in this directory was altered by Phase 0.11's work — MySQL remains the sole authoritative store for workflow and business state.

## Relationship to Phase 0.12 (Complete)

**Phase 0.12 — Performance, Scalability, Multi-Tenancy, Disaster Recovery, and Enterprise Readiness Architecture is now complete** — see [../09-enterprise/README.md](../09-enterprise/README.md), superseding this section's earlier expectation below. It consumed this directory's [backup-restore-and-data-recovery.md](backup-restore-and-data-recovery.md), [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md), and [data-open-decisions.md, PD-01](data-open-decisions.md#pd-01--tenant-column-timing) to define backup-tier consolidation, partitioning/sharding readiness, and the recommended tenant-isolation data model — extending, never redefining, this directory's existing backup-tier priorities and capacity-planning dimensions. No rule defined in this directory was altered by Phase 0.12's work.

## Relationship to Phase 0.13

**Phase 0.13 — Architecture Validation, Gap Analysis, Technical Debt, and Final Architecture Review is now complete** — see [../10-review/README.md](../10-review/README.md), superseding this section's earlier expectation that Phase 0.13 would begin implementation directly. Its single most significant finding concerns this directory directly: **no physical database schema was ever produced** across Phase 0.5 through 0.12 — see [../10-review/architecture-completeness-assessment.md, Section 3](../10-review/architecture-completeness-assessment.md#3-the-physical-database-schema-gap-critical-finding) and [../10-review/architecture-gap-register.md, GAP-01](../10-review/architecture-gap-register.md#gap-01--no-physical-database-schema-exists). This is a genuine, self-acknowledged coordination gap across the phase sequence (first flagged in this directory's own "Preparation Requirements for Phase 0.6" note), not a defect in this directory's logical architecture — the review recommends physical schema design become Phase 0.14's explicit first work package. No rule defined in this directory was altered by Phase 0.13's work.

## Relationship to Phase 0.14

The next phase, **Phase 0.14**, is expected to consume this directory's naming standards, identifier strategy, persistence-ownership map, and high-integrity data model — together with every subsequent phase's foundation through Phase 0.13's gap analysis — to generate the actual Laravel migrations, React/Flutter implementation, and their corresponding tests, beginning with the physical schema design Phase 0.13 identified as still outstanding. See [../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md, Section 47](../10-review/phase-0.13-architecture-validation-gap-analysis-final-review.md#47-next-phase).

## Update Rules

1. Changes to persistence ownership should be reflected first in [persistence-ownership-map.md](persistence-ownership-map.md), then propagated to [conceptual-schema-catalog.md](conceptual-schema-catalog.md) and [phase-0.5-data-database-persistence-architecture.md](phase-0.5-data-database-persistence-architecture.md) as needed.
2. Changes to naming or identifier conventions should be reflected first in [database-naming-and-design-standards.md](database-naming-and-design-standards.md) / [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md), then propagated to every document that references a naming/identifier example.
3. Changes to classification or retention should be reflected first in [information-classification-and-privacy.md](information-classification-and-privacy.md) / [retention-archival-and-disposal.md](retention-archival-and-disposal.md), then propagated to [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md) and [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md) as needed.
4. Resolving an item in [data-open-decisions.md](data-open-decisions.md) should update its `Status` field and, where it changes a rule, be reflected back into the relevant document.
5. Keep `.ai/project-context.md`, `.ai/architecture.md`, `.ai/database-rules.md`, `.ai/data-classification-rules.md`, and `.ai/persistence-rules.md` (see [../../.ai/](../../.ai/)) in sync with material changes to this directory, since those files are the primary context AI-assisted development tools use for this project.
