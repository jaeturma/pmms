# PMMS Logical Data Architecture

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [phase-0.5-data-database-persistence-architecture.md](phase-0.5-data-database-persistence-architecture.md) · [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) · [../01-architecture/data-ownership-map.md](../01-architecture/data-ownership-map.md)

This document establishes PMMS's foundational data-architecture principles and the source-of-truth model every other Phase 0.5 document builds on. **No physical schema, migration, or SQL is defined here** — this is logical architecture only, distinguished explicitly from future physical schema design per working rule 36.

---

## 1. Data Architecture Principles

1. **One authoritative owner per major data concept** — inherited directly from [data-ownership-map.md](../01-architecture/data-ownership-map.md) (Phase 0.2); Phase 0.5 gives that principle a persistence-layer expression, it does not re-derive it.
2. **MySQL is authoritative for transactional business data.** Every high-integrity or business-authoritative record lives in MySQL.
3. **Redis is transient.** It never holds the only copy of anything that matters if lost.
4. **MinIO stores object content, not business ownership.** Database metadata controls object access, classification, and lifecycle — the object store itself has no concept of PMMS's authorization model.
5. **Other contexts use references, snapshots, projections, or approved replicated data** — never a second authoritative copy of a concept they don't own (restated from [laravel-architecture.md, Section 8](../01-architecture/laravel-architecture.md#8-modular-monolith-rules-summary)).
6. **Public and reporting models are downstream.** BC-29 Public Information and BC-33 Reporting and Analytics own no authoritative data at the persistence layer, exactly as they own none at the domain layer.
7. **High-integrity records are corrected through controlled history** — versioned supersession, reversal, or effective-dated change — never a destructive `UPDATE` that erases the prior value.
8. **Destructive overwrite is prohibited where traceability is required.** This is the persistence-layer expression of "no silent mutation" from [high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md).
9. **Business states must be explicit.** A record's state is a first-class, named value — never inferred from the presence/absence of a timestamp or the coincidence of two other column values.
10. **Data classifications influence access and storage** — the five-tier classification model from [phase-0.3-access-and-assignment-architecture.md, Section 21](../01-architecture/phase-0.3-access-and-assignment-architecture.md#21-data-classification-model) is carried into persistence decisions (encryption, logging exclusion, export restriction), not re-invented here.
11. **Sensitive data is minimized.** A table stores what its owning workflow needs, not everything that could conceivably be useful someday.
12. **External identifiers must not expose internal sequential IDs** — see [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md).
13. **Cross-context references must not create shared ownership.** Referencing another context's identifier is not the same as co-owning its data.
14. **Database constraints enforce stable integrity rules** (foreign keys within an ownership boundary, unique constraints, not-null) — constraints are for rules that essentially never change; rules that vary by sport, meet, or policy belong in the Application/Domain layer, not a `CHECK` constraint.
15. **Application logic owns complex domain rules.** The database is not where eligibility criteria, scoring formulas, or medal-tally logic live.
16. **Database triggers do not hide business workflows** — restated as an explicit anti-pattern (per [phase-0.4-application-integration-runtime-architecture.md, Section 49](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#49-architectural-risks-and-anti-patterns)); triggers, if used at all, are reserved for narrow technical bookkeeping (e.g., maintaining a search-friendly denormalized column), never for enforcing an eligibility or certification rule invisibly.
17. **Schema evolution is forward-compatible** — new columns are nullable or defaulted; breaking changes use expand-migrate-contract, not a single destructive migration.
18. **Migration rollback limitations are documented** — not every migration can be safely reversed (e.g., one that drops a column with data); where a migration is not safely reversible, that fact is recorded at migration-authoring time (an implementation-phase practice this document establishes as a requirement, not itself performs).
19. **Retention and deletion respect policy and audit requirements** — see [retention-archival-and-disposal.md](retention-archival-and-disposal.md); no table implements ad hoc deletion logic disconnected from the platform's retention categories.
20. **Test data never contains real protected data** — see [test-seed-and-reference-data-strategy.md](test-seed-and-reference-data-strategy.md).

## 2. Source-of-Truth Model

| Store | Role | Authoritative For | Never Authoritative For |
|---|---|---|---|
| **MySQL** | Authoritative relational system of record | Organizations, meets, people/participants, user-account relationships, roles/permissions/scopes/assignments, registrations, eligibility decisions, competition entries, tournament structures, scores, official results, protests, medal awards/certified tally, accreditation metadata, access scans, medical operational records, logistics records, finance records, document metadata, audit events, retained notification intent/delivery status, synchronization state, integration state | Object binary content (MinIO's job) |
| **Redis** | Transient runtime support | Nothing durable — queues, cache, sessions (where approved), rate limiting, distributed locks, idempotency keys, short-lived sync coordination, Reverb runtime state, temporary progress indicators | Any of the MySQL list above, ever |
| **MinIO** | Object content storage | Uploaded evidence, profile photos, accreditation assets, medical/finance attachments, official documents, generated reports, media files, import/export files, archives — the *bytes* | Ownership, access authorization, classification, retention decision (all MySQL) |
| **Mobile Local Store** | Temporary offline operational data | Nothing permanently — a device-local cache and pending-action queue, per [offline-sync-and-conflict-data-model.md](offline-sync-and-conflict-data-model.md) | Anything once synced and server-accepted |
| **Public Projections (BC-29)** | Approved public read models | Nothing — a downstream, rebuildable projection of MySQL data that has been explicitly approved for publication | Any business decision |
| **Reporting/Analytics Stores (BC-33)** | Downstream read models | Nothing — derived, rebuildable | Any business decision |
| **Search Indexes** | Derived, rebuildable indexes | Nothing — rebuildable from MySQL at any time | Any business decision |

This table is the single reference point every other Phase 0.5 document defers to when a "where does this live" question arises.

## 3. Why This Architecture

- **Why PMMS requires explicit data architecture before migration generation:** Phases 0.2–0.4 established *what* owns *what* conceptually and *how* it executes at runtime; without this phase, a developer generating the first migration would have to simultaneously invent naming conventions, identifier strategy, versioning approach, and classification handling — each inconsistently, across 34 modules, with no way to catch drift until data already depends on the wrong shape.
- **Why data ownership must follow bounded contexts:** A migration authored without reference to [persistence-ownership-map.md](persistence-ownership-map.md) risks recreating the exact cross-context table-sharing anti-pattern Phase 0.2 exists to prevent — the domain boundary is only real if the schema respects it.
- **Why athlete, eligibility, scoring, results, medal tally, medical, accreditation, finance, and audit data require stronger controls:** These are the 11 high-integrity domains named consistently since Phase 0.2; [high-integrity-data-model.md](high-integrity-data-model.md) gives each one a dedicated persistence treatment because a generic "CRUD table" pattern cannot express "corrections supersede, never overwrite."
- **Why MySQL is the authoritative transactional store:** It is the approved technology direction (confirmed across Phases 0.1–0.4) and the only store in the stack with the relational integrity, transactional guarantees, and Laravel-native tooling PMMS's high-integrity workflows require.
- **Why Redis, MinIO, public projections, search indexes, and offline mobile stores have different responsibilities:** Each is good at something MySQL is not (Redis: speed and transience; MinIO: bulk binary storage; projections: public-scale read traffic; search: query flexibility; mobile store: offline availability) — collapsing these into "just use MySQL for everything" or "just use Redis for everything" would sacrifice exactly the property each was chosen for.
- **Why historical meet data must remain reproducible and traceable:** Per [Phase 0.1's institutional-knowledge goal](../00-product/phase-0.1-product-foundation.md#5-product-mission) and the meet-closure preservation requirement in [retention-archival-and-disposal.md](retention-archival-and-disposal.md) — a meet's official record must be answerable years later exactly as it stood at closure.
- **Why a commercial-quality platform needs defined retention, versioning, correction, import, export, and recovery rules:** These are the operational disciplines that separate a durable institutional platform from a prototype; Phase 0.1's commercial-quality direction ([phase-0.1-product-foundation.md, Section 18](../00-product/phase-0.1-product-foundation.md#18-commercial-quality-product-direction)) is not satisfied by a working schema alone.

## 4. Multi-Meet and Multi-Organization Readiness (Logical Principles)

- **Reusable master data** (Organizations, Schools, People, User Accounts, Officials, Sports Catalog, Venue Catalog, reference values) is platform-level and persists across meet cycles.
- **Meet-specific data** (meet configuration, committees, delegations, registrations, eligibility cases, entries, tournament structures, assignments, scores, results, protests, medal tally, accreditation, logistics, publications) is always meet-owned — every meet-specific record carries an explicit meet reference, never an implicit "current meet" assumption.
- **Cross-meet copying is explicit** — a new meet cycle never silently inherits a prior meet's eligibility, assignments, or entries; each is freshly established, even when the underlying Person/Organization records are reused.
- **Tenant/multi-organization readiness is a logical property, not a launch requirement.** Every meet-scoped and organization-scoped table conceptually carries an organization-ownership path (even though only one organization — DepEd — exists today, per [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)), so that a future second organization does not require retrofitting every table with a tenant column after the fact. This mirrors the identical reasoning already applied to authorization scope in [domain-open-decisions.md, DD-21](../01-architecture/domain-open-decisions.md#dd-21--tenant-boundaries).

**No physical tenant-isolation mechanism (separate schema, row-level security, separate database) is selected in this phase** — see [data-open-decisions.md](data-open-decisions.md).

## 5. Open Questions

- Whether tenant readiness should be expressed as a nullable `organization_id` on every relevant table from day one, or added when a second organization is actually onboarded — see [data-open-decisions.md](data-open-decisions.md).
- Physical tenant-isolation mechanism, deferred entirely.

Tracked in [data-open-decisions.md](data-open-decisions.md).
