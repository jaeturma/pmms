# ADR-0005: Data, Database, and Information Lifecycle Architecture

## Status

Accepted (as a Phase 0.5 data-architecture decision; pending formal data, security, privacy, domain, and engineering sign-off — see [../current-phase.md](../current-phase.md))

## Context

ADR-0002 decomposed PMMS into 34 bounded contexts with one authoritative data owner each. ADR-0003 built a hybrid authorization model on top of those contexts. ADR-0004 gave both a concrete Laravel module structure and runtime execution model. None of the three specified what the database actually looks like: how records are identified, how tables are named, how corrections to a certified result are recorded without destroying history, which store (MySQL, Redis, MinIO) is authoritative for what, or how long data must be kept.

Left unspecified, this gap risks the same failure mode Phase 0.2 and Phase 0.4 were built to prevent, now expressed at the schema level: 34 modules independently generating migrations without a shared naming convention, identifier strategy, or versioning discipline would produce a database that passes tests on day one while quietly violating the ownership and integrity guarantees established in ADR-0002/0003/0004 — a `status` column meaning different things in different tables, one module soft-deleting an eligibility decision while another never soft-deletes anything, Redis quietly accumulating data nothing else backs up. These are expensive, potentially data-loss-risking problems to discover after real meets depend on the schema, and cheap to prevent by deciding them once, consistently, now.

## Decision

PMMS will use **MySQL as the sole authoritative relational store, Redis as a strictly transient support layer, MinIO for object content only with MySQL always authoritative for its metadata, and a persistence model whose ownership boundaries, identifier strategy, and integrity patterns mirror the bounded-context, authorization, and runtime architecture already established.**

Specifically:

1. **Source of truth is unambiguous.** MySQL holds every authoritative business record. Redis holds nothing durable — cache, queue transport, session, distributed locks, and temporary idempotency keys only. MinIO stores object bytes; ownership/access metadata always lives in MySQL. Public projections, reporting read models, and search indexes are all downstream, non-authoritative, and rebuildable (see [../../docs/02-data/logical-data-architecture.md](../../docs/02-data/logical-data-architecture.md)).
2. **Persistence ownership mirrors bounded-context ownership exactly.** Every one of the 34 contexts from ADR-0002 has one, and only one, authoritative persistence owner for its data — no ownership assignment changed in this phase (see [../../docs/02-data/persistence-ownership-map.md](../../docs/02-data/persistence-ownership-map.md)).
3. **Identifiers are dual by design.** Unsigned BIGINT auto-increment internal primary keys for relational efficiency, paired with ULID-based public identifiers for externally exposed, synchronization-sensitive, or offline-originated records — offline-originated records (Scoring, Access Validation) use a client-generated ULID as their permanent identity from the moment of capture (see [../../docs/02-data/identifier-and-reference-strategy.md](../../docs/02-data/identifier-and-reference-strategy.md)).
4. **The 11 high-integrity domains (Participant Identity, Athlete Registration, Eligibility, Scoring, Official Results, Protest and Appeals, Medal Tally, Accreditation, Medical Operations, Finance, Audit) never permit silent mutation.** Corrections supersede via new versions with actor/reason/time/approval tracking; soft deletion is prohibited for these domains; audit events are absolutely append-only with no correction authority at all (see [../../docs/02-data/high-integrity-data-model.md](../../docs/02-data/high-integrity-data-model.md), [../../docs/02-data/temporal-history-and-versioning-model.md](../../docs/02-data/temporal-history-and-versioning-model.md)).
5. **Data classification (the five Phase 0.3 tiers, unchanged) drives persistence-layer storage, encryption, logging, export, and offline-replication rules** — no cryptographic algorithm is selected in this phase; every control is an evaluated candidate (see [../../docs/02-data/information-classification-and-privacy.md](../../docs/02-data/information-classification-and-privacy.md), [../../docs/02-data/audit-and-security-data-architecture.md](../../docs/02-data/audit-and-security-data-architecture.md)).
6. **Retention is never invented.** Every retention category is a documented placeholder pending DepEd records-management and legal input; several are explicitly marked blocking (see [../../docs/02-data/retention-archival-and-disposal.md](../../docs/02-data/retention-archival-and-disposal.md)).
7. **Concurrency and integrity are enforced at the database layer, not merely by convention.** Optimistic locking (version columns) for high-integrity aggregates; Redis distributed locks coordinate but never substitute for authoritative database validation; transactions stay within one bounded context; Medal Tally reads exclusively from certified Official Results (see [../../docs/02-data/transaction-concurrency-and-locking.md](../../docs/02-data/transaction-concurrency-and-locking.md)).
8. **An outbox pattern is evaluated, not implemented.** The recommended direction starts with Laravel's `after_commit` dispatch semantics; a formal outbox is adopted only if measured event-loss incidents demonstrate the need.
9. **Multi-organization/multi-meet readiness is logical, not physical.** Every meet/organization-scoped table conceptually carries an organization-ownership path; no physical tenant-isolation mechanism is selected in this phase.

**Explicitly not decided by this ADR:** physical table/column definitions, migrations, indexes, and constraints (Phase 0.6); which encryption algorithm or key-management product is used; numeric retention periods, RPO/RTO targets, or partitioning trigger thresholds; whether historical spreadsheet data is migratable at all.

## Rationale

- **Preserves Phase 0.2/0.3/0.4 boundaries at the schema level, not just in documentation.** A bounded context's ownership and a high-integrity domain's correction discipline are only real once the database itself makes violating them structurally awkward; this ADR is where that translation happens.
- **Prevents 34 independently-evolving migration sets from silently diverging.** Naming, identifier, and versioning standards are decided once, centrally, before any module generates its first migration — cheaper now than after real data depends on an inconsistent schema.
- **Matches the confirmed technology stack's actual guarantees.** MySQL's relational integrity and transactional guarantees are used for exactly what they're good at; Redis's speed and Laravel-native queue/cache/session support are used for exactly what they're good at; MinIO's object-storage economics are used for content, never for metadata that needs relational integrity.
- **Protects institutional trust at the exact layer where it is most permanently at risk.** A silently-overwritten official result or a soft-deleted eligibility decision is not a bug that a later patch can fix — the data is gone. This ADR's append-only and versioning rules exist specifically to make that class of failure structurally difficult, not merely policy-discouraged.
- **Avoids both premature physical commitment and premature vagueness.** No migration is generated and no encryption algorithm is chosen without evidence; but the logical shape, ownership, and integrity discipline every future migration must respect is fully decided, so Phase 0.6 begins from a specified foundation rather than a blank slate.

## Approved Data Architecture Direction

> MySQL as the sole authoritative relational store; Redis strictly transient; MinIO for object content only with MySQL-authoritative metadata; persistence ownership mirroring bounded-context ownership; dual BIGINT/ULID identifiers; append-only and versioned treatment for all 11 high-integrity domains; classification-driven (not yet algorithm-selected) protection; and retention left as documented placeholders pending policy input.

## Persistence Ownership Rule (Carried Forward from ADR-0002/0004)

Every major data concept's authoritative bounded context is the only owner of its underlying storage. Other contexts hold references, application-validated pointers, immutable snapshots, or downstream projections — never a second authoritative copy and never a direct write into another context's tables.

## High-Integrity Rule (Persistence Expression)

Eligibility decisions, scores, official results, protest decisions, medal awards, accreditation credentials, medical encounters, financial records, and audit events are never destructively overwritten or soft-deleted. Every mutation to one of these concepts is either a new version referencing its predecessor, or — for audit and security events specifically — impossible; corrections happen only through the owning context's controlled correction pattern, never a direct production database edit outside a documented emergency-repair procedure.

## Classification Rule (Persistence Expression)

The five Phase 0.3 classification tiers (Public/Internal/Confidential/Restricted/Highly Restricted) determine storage, encryption candidacy, logging restrictions, export restrictions, and offline-replication eligibility for every field — carried unchanged, not redefined, by this phase.

## Offline and Sync Rule (Persistence Expression)

Only the narrow, explicitly-listed categories may replicate to a device. Idempotency keys prevent duplicate creation on retried sync submissions. High-integrity conflicts always require server authority and human review — never mechanical auto-resolution.

## Consequences

**Positive:**
- Phase 0.6 (physical schema and migration design) inherits a complete naming standard, identifier strategy, persistence-ownership map, and high-integrity data model, and can generate migrations against known, consistent rules rather than inventing conventions per module.
- High-integrity workflows have their data-integrity enforcement points named before any table exists to violate them.
- The three-store architecture (MySQL/Redis/MinIO) has unambiguous, non-overlapping responsibility, reducing the risk of scope creep (e.g., Redis quietly becoming a second, unreliable system of record) as implementation proceeds.

**Negative / trade-offs:**
- The dual internal-key/public-ID pattern adds a second identifier column to nearly every table — a real, ongoing schema-complexity cost, accepted because it is the only approach that simultaneously satisfies offline record creation, public-identifier safety, and relational efficiency.
- Deferring the outbox pattern accepts a small theoretical reliability gap (a crash between commit and event dispatch) in exchange for not building premature infrastructure — revisit if evidence of actual event loss appears.
- A significant number of decisions remain open (27 items in [../../docs/02-data/data-open-decisions.md](../../docs/02-data/data-open-decisions.md)), several blocking Phase 0.6 in specific areas — most notably retention periods (PD-04) and classification-tier validation (PD-08), meaning Phase 0.6 will need to revisit parts of this ADR once those resolve.

## Alternatives Considered

1. **Let each Laravel module design its own migrations independently, guided only by general Laravel conventions.** Rejected — the most direct path to the exact naming/identifier/versioning inconsistency this ADR exists to prevent, discovered only after real data made it expensive to fix.
2. **A single shared, denormalized "god schema" with generic `entities`/`attributes` tables instead of per-context tables.** Rejected — defeats relational integrity, makes ownership unenforceable, and directly contradicts the one-authoritative-owner principle from ADR-0002.
3. **UUID-only identifiers everywhere (no internal BIGINT key).** Rejected as the sole strategy — UUIDs alone sacrifice MySQL index efficiency and natural sortability at high write volume (`ScoreRecord`, `AccessScan`); the dual-identifier approach preserves both efficiency and external-safety/offline-generation needs.
4. **Adopt a full transactional outbox pattern immediately.** Rejected for the initial build — `after_commit` dispatch achieves the great majority of the reliability benefit without the operational complexity of an outbox table and relay process, absent evidence of actual event loss.
5. **Physical multi-tenant schema separation (separate database/schema per organization) from launch.** Rejected for the initial build — logical readiness (an organization-ownership path on every relevant table) is sufficient until a genuine second-organization deployment is confirmed; building physical isolation speculatively would be premature complexity.
6. **Invent plausible retention periods now to unblock Phase 0.6 faster.** Rejected — directly violates the "never invent official/regulatory rules" principle carried through every phase of this project; every retention value remains a documented placeholder until DepEd/legal input is available.

## Validation Requirements

This decision is provisional pending:

- Formal review by a designated data architect, security architect, Data Privacy and Legal Stakeholders, the DepEd records-management authority, and a software architect, per [../../docs/02-data/README.md, "Ownership and Review Expectations"](../../docs/02-data/README.md#ownership-and-review-expectations).
- Resolution of the highest-priority Phase 0.5 open decisions, per [../../docs/02-data/data-open-decisions.md, "Summary of Blocking / High-Priority Data Decisions"](../../docs/02-data/data-open-decisions.md#summary-of-blocking--high-priority-data-decisions) — notably PD-04 (retention periods) and PD-08 (classification-tier validation).
- Continued resolution of the Phase 0.1 policy decisions this ADR's high-integrity persistence rules depend on (eligibility authority, result approval chain, protest authority, medal tally rules, medical-data handling).

## Related Documents

- [../../docs/02-data/phase-0.5-data-database-persistence-architecture.md](../../docs/02-data/phase-0.5-data-database-persistence-architecture.md)
- [../../docs/02-data/logical-data-architecture.md](../../docs/02-data/logical-data-architecture.md)
- [../../docs/02-data/persistence-ownership-map.md](../../docs/02-data/persistence-ownership-map.md)
- [../../docs/02-data/identifier-and-reference-strategy.md](../../docs/02-data/identifier-and-reference-strategy.md)
- [../../docs/02-data/high-integrity-data-model.md](../../docs/02-data/high-integrity-data-model.md)
- [../../docs/02-data/information-classification-and-privacy.md](../../docs/02-data/information-classification-and-privacy.md)
- [../../docs/02-data/data-open-decisions.md](../../docs/02-data/data-open-decisions.md)
- [../architecture.md](../architecture.md)
- [../database-rules.md](../database-rules.md)
- [../data-classification-rules.md](../data-classification-rules.md)
- [../persistence-rules.md](../persistence-rules.md)
- [../current-phase.md](../current-phase.md)
- [ADR-0001-product-foundation.md](ADR-0001-product-foundation.md)
- [ADR-0002-domain-and-bounded-context-architecture.md](ADR-0002-domain-and-bounded-context-architecture.md)
- [ADR-0003-role-permission-scope-and-assignment-architecture.md](ADR-0003-role-permission-scope-and-assignment-architecture.md)
- [ADR-0004-application-integration-and-runtime-architecture.md](ADR-0004-application-integration-and-runtime-architecture.md)
