# PMMS Database Migration and Release Safety

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md) · [ci-cd-and-release-pipeline-architecture.md, Section 6](ci-cd-and-release-pipeline-architecture.md#6-database-migration-gates) · [deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md)

This document defines database-migration gates and the phased database-release-safety pattern. **No migration is created, reviewed against real schema, or executed by this document** — no physical schema exists yet.

---

## 1. Database Migration Gates

A migration requires:

Reviewed migration · forward compatibility (new code works against the new schema) · backward compatibility where needed (old, still-deploying code doesn't break against the new schema mid-rollout) · data-volume assessment (how many rows does this affect, per [../02-data/indexing-performance-and-capacity.md, Section 2](../02-data/indexing-performance-and-capacity.md#2-capacity-and-growth-categories)) · locking assessment (does this migration take a table lock, and for how long) · index-impact assessment · backup readiness (confirmed recent backup exists before a Production migration runs) · rollback-or-forward-fix plan · data-migration verification (if the migration also transforms data, not just structure) · a production-execution-time estimate based on Staging evidence (never a guess) · an application-compatibility window (how long old and new code must coexist) · post-deployment verification · documentation (the migration's purpose and impact recorded, extending [../02-data/](../02-data/)'s documentation discipline to the eventual physical schema).

**Do not assume every migration can be rolled back safely** — restated absolutely per working rule 43; a migration that drops a column or table is not trivially reversible once data has been written against the new schema.

## 2. Database Release Safety — Phased Changes

For any schema change that isn't trivially additive, a phased approach is used:

1. **Add backward-compatible schema** — a new column/table is added without removing or renaming anything the currently-deployed code depends on.
2. **Deploy code that supports old and new forms** — the application is deployed to read/write both the old and new shape, tolerating either during the transition.
3. **Backfill or migrate data** — existing rows are updated to populate the new shape, as a controlled, monitored background process for large tables.
4. **Validate** — the backfilled data is confirmed correct before proceeding.
5. **Switch reads and writes** — the application is deployed to depend on the new shape exclusively.
6. **Remove old structures in a later release** — the old column/table is dropped only once no code path references it and sufficient time has passed to be confident nothing does.

This pattern directly protects the high-integrity, append-only/versioned tables named in [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) — a schema change to `ScoreRecord` or `OfficialResult`, for example, cannot risk losing history mid-migration, and the phased approach ensures it never has to.

## 3. Specific Migration-Safety Concerns

| Concern | Direction |
|---|---|
| Long-running migrations | Estimated from Staging-environment timing against representative data volume before running in Production; a migration expected to take minutes on a large table is scheduled deliberately, never run blind |
| Large index creation | Evaluated for online/non-blocking index-creation support (MySQL's `ALGORITHM=INPLACE` or equivalent), avoiding a full-table lock where avoidable |
| Table locks | Explicitly assessed per migration — restated from Section 1 |
| Online schema changes | A candidate tooling evaluation (e.g., `gh-ost`/`pt-online-schema-change`-style tools) for the highest-volume tables, once real volume justifies the added tooling complexity |
| Data backfills | Run in controlled, resumable chunks — never a single unbounded `UPDATE` against a live, high-volume table |
| Migration resumption | A backfill interrupted partway through can resume without data loss or duplication |
| Idempotency | A migration re-run (e.g., after a partial failure) doesn't corrupt state — restated from the platform-wide idempotency principle |
| Verification | Post-migration, a specific check confirms the migration achieved its intended effect, not merely that it "ran without error" |
| Rollback limitations | Documented explicitly per migration where a clean rollback isn't possible — the forward-fix strategy (Section, [deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md)) is the named alternative |

## 4. Relationship to Deployment Strategy

This document's gates feed directly into [ci-cd-and-release-pipeline-architecture.md, Section 6](ci-cd-and-release-pipeline-architecture.md#6-database-migration-gates) and [deployment-strategies-rollbacks-and-maintenance.md](deployment-strategies-rollbacks-and-maintenance.md)'s rollback-architecture discussion — a migration's safety assessment directly determines whether the surrounding application deployment can use a zero-downtime strategy or requires a maintenance window.

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably whether an online-schema-change tool is adopted before Phase 0.9's physical schema implementation begins, and the specific production-migration approval workflow.
