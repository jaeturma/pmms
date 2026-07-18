# PMMS Data, Database, Migration, and Quality Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md) · [../02-data/import-export-and-data-exchange.md](../02-data/import-export-and-data-exchange.md) · [../02-data/high-integrity-data-model.md, "Data Quality Controls"](../02-data/high-integrity-data-model.md#data-quality-controls)

This document defines MySQL, reporting, export, import, data-migration, and data-quality testing requirements. **No migration, seeder, or test code is created here.**

---

## 1. MySQL Testing

| Target | What to Verify |
|---|---|
| Constraints | Database constraints (once physical schema exists) correctly reject invalid data |
| Unique rules | Unique constraints correctly prevent duplicate values where required |
| Foreign keys | Referential integrity is enforced per [../02-data/transaction-concurrency-and-locking.md, Section 4](../02-data/transaction-concurrency-and-locking.md#4-referential-integrity) |
| Transactions | Multi-step operations correctly commit atomically or roll back entirely on failure |
| Rollback | A failed transaction leaves no partial state |
| Deadlocks | A deadlock is correctly detected and one transaction is safely retried/failed, without corrupting data |
| Lock waits | Lock-wait behavior doesn't cause an unacceptably long user-facing delay for ordinary operations |
| Optimistic versioning | A version-column conflict is correctly detected and rejected, per [../02-data/transaction-concurrency-and-locking.md, Section 2](../02-data/transaction-concurrency-and-locking.md#2-concurrency-control) |
| Pessimistic locking | Narrowly-scoped pessimistic locks correctly serialize the specific operations they protect |
| State-condition updates | An update conditioned on current state (e.g., "only if still `pending`") correctly no-ops or errors if the state has changed |
| Query plans | Representative queries are checked against expected index usage once physical schema exists (a Phase 0.8+ activity) |
| Migration safety later | Migration reversibility and zero-downtime compatibility are a Phase 0.8+ concern, anticipated here |
| Backup and restore | Per [resilience-backup-recovery-and-continuity-testing.md](resilience-backup-recovery-and-continuity-testing.md) |
| Data consistency | Cross-table consistency invariants (e.g., a medal award always references a certified result) hold |
| Historical records | Append-only/versioned tables never lose a prior version through any code path |
| Cross-context ownership rules | No write path exists from outside a table's owning bounded context, per [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md) |

## 2. Reporting Testing

| Target | What to Verify |
|---|---|
| Source correctness | A report's figures match its authoritative source data at the version it was generated from |
| Projection freshness | A read model's staleness stays within its documented freshness expectation, per [../02-data/public-reporting-and-projection-data.md, Section 2](../02-data/public-reporting-and-projection-data.md#2-read-models-and-analytics) |
| Filtering | Report filters correctly constrain results |
| Sorting | Sort order is correct and stable |
| Pagination | Large reports paginate correctly |
| Totals | Aggregate totals correctly reflect the underlying detail |
| Historical snapshots | A historical report reproduces the same figures it originally produced, even after later corrections elsewhere |
| Correction propagation | A source correction correctly propagates to dependent reports/projections within their expected rebuild trigger |
| Superseded results | A superseded result never appears as if current in any report |
| Held results | A protest-held result is correctly excluded from or flagged in reports depending on the report's purpose |
| Privacy filtering | Every report respects classification-driven field visibility, per [../03-security/privacy-by-design-architecture.md](../03-security/privacy-by-design-architecture.md) |
| Public and internal differences | A public-facing report and an internal-facing report on the same underlying data correctly differ in exposed fields |
| Export consistency | An exported report matches its on-screen equivalent |
| Rebuildability | A read model can be fully rebuilt from authoritative source data and produces the same result |

## 3. Export Testing

| Target | What to Verify |
|---|---|
| Authorization | Export requests are authorized per the exporting user's role/scope/classification access |
| Scope | Exported data is correctly scoped (e.g., only the requested meet) |
| Classification | Export content correctly reflects the classification of every included field |
| Reason capture | Sensitive exports correctly require and record a reason |
| Privacy filtering | Restated from Section 2 |
| Redaction | Fields requiring redaction in export context are correctly redacted |
| Watermarking readiness | If watermarking is adopted, exported documents correctly carry it |
| Expiry | Generated export files become inaccessible after their retention window |
| Download audit | Every export generation and download is audit-relevant |
| Large export | A very large export completes successfully (asynchronously where designed) without timing out or corrupting output |
| Queue failure | A failed export-generation job is surfaced for retry, not silently lost |
| CSV injection defense | A cell value beginning with `=`, `+`, `-`, or `@` is correctly neutralized before being written to a CSV export, per [../03-security/data-sharing-export-and-public-disclosure-controls.md, "Export Controls"](../03-security/data-sharing-export-and-public-disclosure-controls.md#export-controls) |
| Time zone | Exported timestamps correctly reflect the intended time zone convention, per [../02-data/database-naming-and-design-standards.md, Section 4](../02-data/database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards) |
| Data freshness | The export correctly reflects data as of a specific, identifiable point in time |
| Source version | The export references the specific source-data version it was built from |
| Empty export | An export with zero matching records produces a well-formed, non-broken output rather than an error |
| Cross-tenant leakage | No export ever includes data from outside its authorized scope |

## 4. Import Testing

| Target | What to Verify |
|---|---|
| Valid file | A correctly-formatted file imports successfully |
| Invalid file | A malformed file is rejected with a clear, row-aware error |
| Wrong template version | An outdated import template is detected and rejected or handled per its versioning policy |
| Missing columns | Missing required columns are detected before any row processing begins |
| Duplicate rows | Duplicate rows within a single import file are detected |
| Cross-row conflicts | Rows that conflict with each other within the same import are detected |
| Existing-record conflicts | A row conflicting with an already-existing record is detected and handled per the import's conflict policy |
| Staging | Imported data correctly lands in a staging state before commit |
| Preview | A staged import can be previewed before commit |
| Approval | Commit requires the appropriate authorization |
| Commit | A committed import correctly creates/updates the target records |
| Partial failure | A partially-failing import correctly reports which rows succeeded and which failed, without silently dropping failures |
| Row-level errors | Each failed row's specific error is reported, not just an aggregate failure count |
| Chunking | Large imports correctly process in chunks without timing out or exhausting memory |
| Idempotency | A re-run of the same import file doesn't duplicate already-imported rows |
| Re-import | A corrected and re-submitted import file correctly supersedes/completes the prior attempt |
| Compensation | A committed import that needs reversal has a defined, tested compensation path |
| Audit | Every import batch is audit-relevant |
| Source preservation | The original import file/source reference is preserved for traceability |

## 5. Data-Migration Testing

Applicable only if/when historical data migration is confirmed in scope (per [../00-product/product-scope.md, Section 14](../00-product/product-scope.md#14-data-migration-scope), still unconfirmed as of this phase):

| Activity | What to Verify |
|---|---|
| Source profiling | The source data's actual shape/quality is understood before mapping begins |
| Mapping validation | Field-mapping rules correctly transform source data into PMMS's target shape |
| Transformation validation | Transformation logic (e.g., normalizing legacy sport names) produces correct results |
| Duplicate detection | Migrated records are checked against [../02-data/identity-resolution-and-duplicate-management.md](../02-data/identity-resolution-and-duplicate-management.md) |
| Trial migration | A trial run against a copy of source data surfaces issues before the real migration |
| Reconciliation | Post-migration record counts and key figures are reconciled against the source |
| Exception reporting | Records that couldn't be migrated cleanly are reported, not silently dropped |
| Count comparison | Source and target record counts match, accounting for any intentional exclusions |
| Financial reconciliation where applicable | Migrated financial figures are reconciled to the source with the same rigor as [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md, "Finance Data Governance"](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md#finance-data-governance) requires generally |
| Historical reproducibility | A migrated historical report reproduces its original source figures |
| Rollback or correction | A migration found to be incorrect has a defined recovery path |
| Post-migration verification | A final verification pass confirms the migrated system behaves correctly against migrated data |
| Sign-off | Migration completion requires explicit sign-off from the relevant data owner |

## 6. Data-Quality Testing

Ten quality dimensions, restated from [../02-data/high-integrity-data-model.md, "Data Quality Controls"](../02-data/high-integrity-data-model.md#data-quality-controls) and connected to testable checks:

| Dimension | Test Focus |
|---|---|
| Completeness | Required fields are never silently missing on a persisted record |
| Accuracy | Persisted values match their validated source/input |
| Consistency | The same fact is never represented contradictorily across related records |
| Validity | Persisted values conform to their defined format/range |
| Uniqueness | Records that should be unique (e.g., one canonical athlete identity) are not silently duplicated |
| Timeliness | Time-sensitive records (e.g., eligibility decisions before entry deadlines) are processed within operationally necessary windows |
| Traceability | Every high-integrity record maintains its full version/audit chain |
| Referential integrity | Cross-context references never point to nonexistent or incorrect records |
| Reproducibility | The same input, reprocessed, produces the same output |
| Conformance | Data conforms to its owning context's documented schema/business rules |

## 7. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably whether data-migration testing is scoped at all, pending confirmation of historical-data-migration necessity, and the specific MySQL query-plan validation tooling once physical schema exists.
