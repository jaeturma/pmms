# PMMS Import, Export, and Data Exchange

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md) · [information-classification-and-privacy.md](information-classification-and-privacy.md) · [identity-resolution-and-duplicate-management.md](identity-resolution-and-duplicate-management.md)

This document defines import, export, and future migration architecture. **No import/export code is written here.**

---

## 1. Import Architecture

### Import Categories
Organization directory, school directory, participants, athletes, coaches, technical officials, delegations, sports catalog, event catalog, schedules, historical results, accreditation lists, reference data.

### Import Lifecycle

```text
Upload → Validate file → Create import batch → Parse → Normalize → Stage →
Validate → Detect duplicates → Preview → Approve → Commit → Reconcile → Report → Archive source
```

Each stage produces an auditable artifact (a validation report, a duplicate-candidate list, a commit summary) — the pipeline never jumps directly from "Upload" to "Commit."

### Rules

- **Imports never write directly without validation** — every row passes through Parse/Normalize/Stage/Validate before any authoritative table is touched; the staging area is a distinct, non-authoritative holding location.
- **Imports require idempotency** — an import batch carries an idempotency key (per [identifier-and-reference-strategy.md, Section 2](identifier-and-reference-strategy.md#2-identifier-categories)); re-running the same batch file does not duplicate records.
- **Row-level error reporting** — a batch with 500 rows and 3 invalid ones reports exactly which 3 failed and why, rather than failing (or silently accepting) the entire batch.
- **Source references are preserved** — every imported record retains its `source_system_id`/`external_source_id` (per [identifier-and-reference-strategy.md, Section 2](identifier-and-reference-strategy.md#2-identifier-categories)), enabling future reconciliation against the original source.
- **Reversibility/compensation** — where feasible, a committed import batch can be reversed (e.g., a batch of Draft-state records not yet touched by downstream workflows) or compensated (a correcting batch), never a raw `DELETE WHERE batch_id = ...` against records that may already be referenced elsewhere.
- **Approved records use normal domain rules** — once committed, an imported `Participant` or `Organization` record is subject to exactly the same domain validation, duplicate-detection, and correction rules as a manually entered one; import is a bulk *entry point*, not a bypass of business rules.
- **Sensitive imports have restricted access** — an import touching Confidential/Restricted data (e.g., a participant import carrying contact details) requires the same role/scope authorization as manually entering that data would.
- **Large imports are chunked** — per [../01-architecture/event-and-queue-architecture.md, Section 6](../01-architecture/event-and-queue-architecture.md#6-chunking-and-bulk-work).
- **Import templates are versioned** — a template change (e.g., adding a required column) does not retroactively reinterpret a prior import batch's already-committed data.

## 2. Export Architecture

### Export Categories
Operational reports, committee reports, official result documents, medal tally, athlete rosters, accreditation lists, audit exports, finance exports, medical exports, public datasets, administrative backups, data-portability exports.

### Rules

- **Exports respect authorization and classification** — an export never contains a field the requester is not independently authorized to view; export is not a side channel around normal access control.
- **Sensitive exports require reason capture** where the underlying data warrants it (Restricted/Highly Restricted categories) — restated from [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md)'s audit-level conventions.
- **Large exports are generated asynchronously** — per the `exports` queue category in [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md), never blocking an HTTP request.
- **Generated export files have an expiry** — a short-lived retention window (Section 1 of [retention-archival-and-disposal.md](retention-archival-and-disposal.md)), after which the file is deleted regardless of whether it was downloaded.
- **Export activity is audited** — who requested it, what data classification it touched, when.
- **Public exports use public projections only** — a "public dataset" export draws from BC-29's approved projections, never a direct dump of operational tables.
- **CSV formula-injection risk is addressed at implementation time** — a known risk class for spreadsheet-format exports (a cell value starting with `=`, `+`, `-`, or `@` being interpreted as a formula by the opening application); flagged here as a requirement for Phase 0.6+ implementation, not solved in this document.
- **Spreadsheet, PDF, and machine-readable (JSON/CSV) formats are treated as distinct concerns** — each has different injection, formatting, and fidelity considerations; a single generic "export" code path is not assumed to serve all three safely.
- **Export version and source time are included** — every export states what data-version/timestamp it reflects, so a recipient knows exactly how current the export is.
- **Exported data identifies freshness and filtering** — an export's header/metadata states any filters applied (e.g., "Meet 2027, Delegation A–D only") so the recipient cannot mistake a filtered subset for the complete record.

## 3. Data Migration Readiness (Future Historical Data)

### Migration Categories
Legacy spreadsheets, previous meet databases, existing athlete records, school directories, organization references, historical results, accreditation lists, financial summaries, document archives.

### Migration Phases

```text
Discovery → Profiling → Mapping → Cleansing → Transformation → Staging →
Validation → Trial migration → Reconciliation → Approval → Production migration →
Post-migration verification → Archive
```

### Requirements

- **Source inventory** — a documented list of what legacy data exists and where, established before any migration work begins.
- **Data owner** — each source dataset has a named accountable owner (a DepEd/Secretariat role, to be identified).
- **Mapping rules** — explicit source-field-to-PMMS-field mappings, reviewed before transformation.
- **Exception handling** — a documented process for records that don't cleanly map (e.g., a legacy spreadsheet with inconsistent name formats).
- **Rollback or correction plan** — a migration that introduces bad data must be correctable without a full re-migration.
- **Reconciliation report** — post-migration, a report confirming record counts and spot-checked accuracy against the source.
- **Sign-off** — a named approval step before the migration is considered final.
- **Security review** — any migration touching Restricted/Highly Restricted legacy data (e.g., historical medical or financial records) requires a security/privacy review before execution.

**No migration is performed in this phase** — this section establishes the readiness framework a future migration effort would follow, per [Phase 0.1 product-scope.md, Section 14](../00-product/product-scope.md#14-data-migration-scope) (data migration scope requires confirmation of whether historical records exist in a usable format).

## 4. Open Questions

- Whether historical spreadsheet-based meet records actually exist in a migratable format — per [Phase 0.1 product-scope.md, Section 14](../00-product/product-scope.md#14-data-migration-scope), unconfirmed.
- CSV formula-injection mitigation library/approach — implementation-phase detail.
- Export file expiry duration — implementation-phase tuning.

Tracked in [data-open-decisions.md](data-open-decisions.md).
