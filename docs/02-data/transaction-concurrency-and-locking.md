# PMMS Transaction Boundaries, Concurrency, and Locking

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [../01-architecture/laravel-architecture.md, Section 4](../01-architecture/laravel-architecture.md#4-transaction-boundaries) · [high-integrity-data-model.md](high-integrity-data-model.md) · [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)

This document extends the Phase 0.4 transaction-boundary principles with concurrency control, locking strategy, and referential-integrity rules specific to the persistence layer. **No lock implementation or transaction code is written here.**

---

## 1. Transaction Boundaries

Conceptual transaction boundaries for: meet lifecycle changes, registration submission, eligibility decision, entry locking, tournament progression, score submission, score correction, result certification, result supersession, protest decision, medal tally certification, credential issuance, credential revocation, assignment activation, financial approval, file metadata finalization.

### Rules (Restated and Extended from Phase 0.4)

- **A transaction normally remains within one bounded context** — restated from [../01-architecture/laravel-architecture.md, Section 4](../01-architecture/laravel-architecture.md#4-transaction-boundaries).
- **Remote calls never occur inside a long database transaction** — an eligibility approval's database transaction commits before any subsequent notification/integration call begins; the call is triggered *after* commit, via the standard event/queue path (per [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md)).
- **External side effects happen after safe persistence.**
- **Cross-context workflows use staged states, events, or orchestration** — never a distributed transaction spanning two contexts' tables.
- **Distributed transactions are avoided entirely** — PMMS does not adopt two-phase commit or any XA-style distributed-transaction protocol across bounded contexts.
- **Failed transitions leave unambiguous state** — a failed `official-result.certify` attempt either fully commits or fully rolls back; there is no partially-certified result, ever, restated as the persistence-layer guarantee behind [high-integrity-data-model.md](high-integrity-data-model.md).

## 2. Concurrency Control

### Concurrency-Sensitive Operations
Registration editing, eligibility review, entry locking, tournament advancement, score entry, score correction, result certification, protest holds, medal recalculation, accreditation issuance, credential replacement, venue rescheduling, assignment changes, finance approvals, document version replacement.

### Candidate Controls

| Control | When Used |
|---|---|
| **Optimistic locking (version columns)** | Default for most high-integrity aggregates — a `version` column incremented on every update; a concurrent update against a stale version is rejected, forcing the second writer to reload and retry rather than silently overwriting the first writer's change |
| **Unique constraints** | Enforce stable uniqueness (e.g., one active `EligibilityCase` per registration at a time) |
| **Database transactions** | Every multi-step write within one context's boundary |
| **Pessimistic locks for short critical sections** | Reserved for genuinely short, high-contention operations (e.g., the exact moment of medal-tally recalculation, where a concurrent second recalculation attempt should wait rather than race) — never held across a remote call or a long-running operation |
| **Distributed locks (Redis)** | For cross-request coordination where a database-level lock is insufficient (e.g., preventing two simultaneous draw-generation attempts for the same tournament from different requests) — **always a coordination aid, never the authoritative correctness mechanism** (Section 4) |
| **Idempotency keys** | Per [offline-sync-and-conflict-data-model.md, Section 4](offline-sync-and-conflict-data-model.md#4-idempotency-data) |
| **State-condition updates** | An `UPDATE ... WHERE status = 'expected_prior_status'` pattern — the update only succeeds if the record was in the expected state, providing a lightweight optimistic check without a separate version column for simpler cases |
| **Conflict errors** | Surfaced clearly to the caller (per [../01-architecture/observability-and-error-handling.md, Section 1](../01-architecture/observability-and-error-handling.md#1-error-handling-architecture)) rather than silently retried in a way that could mask a real conflict |
| **Manual resolution** | For high-integrity conflicts that cannot be mechanically resolved (per [offline-sync-and-conflict-data-model.md, Section 3](offline-sync-and-conflict-data-model.md#3-conflict-resolution-data)) |

### Rule: Redis Locks Never Replace Authoritative Database Validation

A distributed lock (Redis) may *coordinate* which request gets to attempt an operation first, but the actual correctness guarantee (e.g., "entries are genuinely locked before draw generation") is always enforced by a database-level check within the transaction — never assumed true merely because a Redis lock was successfully acquired. This directly implements working rule 27 ("do not treat Redis as a system of record") in the concurrency-control context specifically.

## 3. Result and Tally Integrity (Persistence Expression)

- Medal Tally's recalculation reads exclusively from Official Results' certified/published rows — enforced by the reference pattern in [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md) (every `MedalAward` references a specific `OfficialResult` version, never a raw score), not merely by application-layer discipline alone.
- A result certification transaction verifies, within the same transaction, that every score record it depends on is in a `Validated` state — this check and the certification write happen atomically, so no other process can validate a score, certify a result based on it, and then have the score revert to unvalidated in between.

## 4. Referential Integrity

- **Same-context foreign keys normally use database constraints** — a `Match`'s reference to its `Tournament` is a real MySQL foreign key, since both are owned by the same context (Tournament Management).
- **Cascade behavior is always explicit** — no foreign key relies on MySQL's default cascade behavior without a deliberate decision; most high-integrity relationships use `RESTRICT` (prevent deletion of a referenced row) rather than `CASCADE` (automatically delete dependents), since silent cascading deletion is precisely the kind of destructive, untraceable operation [high-integrity-data-model.md](high-integrity-data-model.md) exists to prevent.
- **Cross-context deletion cascades are avoided entirely** — restated from [identifier-and-reference-strategy.md, Section 4](identifier-and-reference-strategy.md#4-cross-context-reference-strategy), Rule 8.
- **Historical records use restrict, nullification, snapshot, or archival based on context** — e.g., if an `Organization` is ever deactivated, `Delegation` rows referencing it are not deleted; they retain the reference (restrict-style) or, where the relationship is meant to survive even a hypothetical organization removal, a snapshot of the relevant organization fields at the time is retained instead.
- **Unique constraints enforce stable uniqueness** — e.g., a `public_id` column is uniquely constrained across its owning table.
- **Partial uniqueness may require application rules or generated strategies** — e.g., "at most one *active* `EligibilityCase` per registration" is a uniqueness rule scoped to a specific status value, which MySQL can express via a generated/virtual column or partial-index-equivalent technique, or may be enforced at the application layer if the database-native mechanism proves awkward — a Phase 0.6 implementation decision.
- **Orphan prevention is mandatory** for file metadata (per [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md)), assignments, and high-integrity records — every such record's owning-aggregate reference is validated at write time, even where a native foreign key isn't used (Section 5 of [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)).
- **Imported external references require source-system qualifiers** — an imported record's `external_source_id` is always paired with which source system it came from, since the same raw ID value could plausibly collide across different legacy sources.

## 5. Outbox and Event Persistence Evaluation

Per [../01-architecture/event-and-queue-architecture.md, Section 5](../01-architecture/event-and-queue-architecture.md#5-reliable-delivery-outbox-consideration) and [../01-architecture/runtime-open-decisions.md, RD-01](../01-architecture/runtime-open-decisions.md#rd-01--reliable-event-delivery-mechanism), an outbox pattern is **evaluated, not committed**, for:

Certified result events, medal tally recalculation, public projection publication, credential revocation, security alerts, notification dispatch, external integrations (none currently approved), reporting updates.

| Evaluation Dimension | Assessment |
|---|---|
| Reliability need | High for certified-result and credential-revocation events specifically; lower for routine notification dispatch |
| Failure risk | A crash between "commit state change" and "dispatch event" without an outbox means the event could be silently lost |
| Transaction relationship | An outbox entry is written in the *same* transaction as the state change it announces, guaranteeing both succeed or both roll back together |
| Replay | An outbox-based dispatcher can replay undelivered events after a crash/restart |
| Deduplication | Requires an idempotency key on the outbox entry (per [offline-sync-and-conflict-data-model.md, Section 4](offline-sync-and-conflict-data-model.md#4-idempotency-data)) so replay never double-processes |
| Event status | Pending / Dispatched / Failed / Dead-lettered |
| Attempt count | Tracked per outbox entry |
| Processing time | Tracked for observability |
| Error tracking | Structured error category per failed attempt |
| Retention | Short — an outbox entry is cleaned up once successfully dispatched and confirmed, retained slightly longer for failed/dead-lettered entries pending investigation |
| Operational ownership | ICT Committee / software architect |

**No outbox table is implemented in this phase.** The recommended direction (per [../01-architecture/runtime-open-decisions.md, RD-01](../01-architecture/runtime-open-decisions.md#rd-01--reliable-event-delivery-mechanism)) is to start with Laravel's `after_commit` queue-dispatch semantics (already visible as a configuration option in the repository's default `config/queue.php`) for MVP scope, and revisit a formal outbox table only if measured event-loss incidents demonstrate the need.

## 6. Open Questions

- Whether optimistic-locking version columns are added to every high-integrity aggregate uniformly, or only where concurrent-write contention is realistically expected.
- Formal outbox adoption trigger (Section 5) — deferred pending operational evidence.

Tracked in [data-open-decisions.md](data-open-decisions.md).
