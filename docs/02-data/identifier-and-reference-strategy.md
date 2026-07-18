# PMMS Identifier and Reference Strategy

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [logical-data-architecture.md](logical-data-architecture.md) · [conceptual-schema-catalog.md](conceptual-schema-catalog.md) · [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md)

This document defines PMMS's identifier strategy and cross-context reference rules. **No physical column type is finalized as binding implementation** — this is a recommended direction for Phase 0.6 (physical schema design) to apply consistently.

---

## 1. Internal Primary Keys — Recommended Direction

> **Use unsigned BIGINT auto-incrementing internal primary keys for high-volume relational efficiency where centrally created, while using ULID-based public identifiers for externally exposed, synchronization-sensitive, or offline-originated records.**

### Rationale

| Consideration | Assessment |
|---|---|
| Laravel compatibility | Unsigned BIGINT auto-increment is Laravel's native default (`id()` migration helper); adopting it minimizes friction and matches the existing starter-kit scaffolding's `users` table pattern |
| MySQL index efficiency | Auto-increment BIGINT produces sequential, compact, cache-friendly clustered-index inserts — materially more efficient than random UUID/ULID primary keys for high-write tables (e.g., `ScoreRecord`, `AccessScan`, `AuditEvent`) |
| Offline record creation | A device capturing a score or scan offline cannot obtain a server-issued auto-increment ID before sync — it needs a collision-safe, client-generatable identifier, which a sequential integer cannot provide |
| Public identifier safety | An auto-increment integer exposed in a URL or QR code leaks record volume/creation order and invites enumeration — unacceptable for anything public-facing or security-sensitive (accreditation credentials, public result IDs) |
| Multi-organization merging | If a second organization's data is ever merged in (per [logical-data-architecture.md, Section 4](logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles)), two independently auto-incremented ID sequences would collide; a globally unique identifier does not |
| Import and synchronization | Bulk imports and offline sync both need an identifier that can be assigned before the record reaches the authoritative database |
| Storage size | BIGINT (8 bytes) is smaller than a UUID/ULID (16 bytes) — relevant for the highest-volume tables (`ScoreRecord`, `AccessScan`, `AuditEvent`, `Notification`) |
| Sortability | ULIDs are lexicographically sortable by creation time (unlike UUIDv4); this is a deciding factor in choosing ULID over plain UUID for the public-identifier role |
| Operational debugging | A small sequential internal ID is easier to reference in logs/support conversations than a long public identifier |

### Resulting Pattern

- **Internal key (`id`):** unsigned BIGINT, auto-increment, used for same-context foreign keys and internal joins. Never exposed in a URL, API response, QR code, or export intended for external consumption.
- **Public ID (`public_id` or context-specific equivalent, e.g., `credential_number`):** a ULID (26-character, Crockford Base32, time-sortable, 128-bit collision-resistant), assigned at record-creation time — server-side for centrally-created records, **client-side for offline/device-originated records** (Scoring, Access Validation), which resolves the offline-ID-generation problem directly: a device generates its own ULID at capture time, and that same ULID becomes the record's permanent public identity once the server accepts it, with no renumbering.

This pattern is **not imposed over any conflicting decision already made in Phase 0.1–0.4** — no such conflict was found; Phase 0.4's offline-sync architecture ([offline-sync-runtime-architecture.md, Section 3](../01-architecture/offline-sync-runtime-architecture.md#3-runtime-rules), "client-generated IDs require a collision-safe strategy") already anticipates exactly this need.

## 2. Identifier Categories

| Identifier Type | Purpose | Example |
|---|---|---|
| **Internal key** | Same-context relational joins, never exposed externally | `scores.id` (BIGINT) |
| **Public ID** | External exposure — URLs, API responses, QR-adjacent references | `scores.public_id` (ULID) |
| **Natural key** | A business-meaningful identifier that already carries uniqueness (e.g., a school's DepEd school ID) | Organization's official school code |
| **External source ID** | The identifier a record held in an external/legacy system before import | `imported_athletes.source_system_id` |
| **Legacy ID** | Retained reference to a pre-migration identifier for reconciliation | Historical meet-record cross-reference |
| **Idempotency key** | A client-supplied token ensuring a retried request is not double-processed | Per [transaction-concurrency-and-locking.md](transaction-concurrency-and-locking.md) |
| **Correlation ID** | Traces one logical operation across requests/jobs/integrations | Per [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) |
| **Device-generated ID** | A ULID created client-side for an offline-originated record before sync | Per Section 1 above |
| **Import batch ID** | Groups all records created/updated by one import run | Per [import-export-and-data-exchange.md](import-export-and-data-exchange.md) |
| **Version ID** | Distinguishes one version of a versioned aggregate from another | Per [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md) |

## 3. Public Identifier Rules

- Internal sequential IDs are never exposed as a security boundary — knowing `id=1042` must never grant or imply access; access is always evaluated through the Phase 0.3 authorization decision sequence regardless of which identifier is used to reference the record.
- Public IDs are non-guessable (ULID's 80 bits of randomness after the timestamp component provides this).
- Public IDs remain stable for the life of the record — a corrected/superseded record's public ID does not change; the correction is expressed as a new version referencing the same enduring identity (per [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md)).
- Public IDs carry no sensitive meaning — no encoding of participant name, delegation, or medical status into the identifier string itself.
- **QR tokens are distinct from record IDs.** An accreditation QR token (per [identity-model.md](../01-architecture/identity-model.md)) is a separate, rotatable credential value — never the same string as the credential's public ID, so that a leaked QR image does not also leak a stable database reference usable elsewhere.
- Accreditation numbers may be human-readable (e.g., a short alphanumeric code printed on a badge) but must never double as an authentication secret — presenting the number alone is never sufficient to prove identity or grant access.
- Athlete numbers may be meet-specific, human-readable display identifiers (e.g., a bib number), distinct from both the internal key and the public ID — meet-scoped, not globally unique, and not treated as a security-relevant identifier.
- External APIs (per [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md)) prefer stable public IDs over internal keys in every response.
- Deleted or archived record IDs are never silently reused for a new, unrelated record — a public ID's meaning is permanent even after the record it names is archived.
- Public identifiers support data portability — a ULID is self-contained and meaningful without a proprietary lookup, supporting future export/import scenarios (per [import-export-and-data-exchange.md](import-export-and-data-exchange.md)).

## 4. Cross-Context Reference Strategy

### Approved Patterns

1. **Store the authoritative external context's identifier** — the default. E.g., `athlete_registrations.participant_id` references Participant Registry's `Participant.id`.
2. **Store a local projection when required for performance** — e.g., Competition Entries may cache a participant's display name locally to avoid a join on every list-page render, explicitly labeled as a projection subject to staleness, never treated as authoritative.
3. **Store an immutable snapshot when historical reproduction requires it** — e.g., Official Results snapshots the specific Sport/Event definition version it was scored against, so a later Sports Catalog rule change never retroactively alters a historical result's meaning.
4. **Avoid cross-context foreign-key coupling where it prevents module independence** — a reference to another bounded context's table is not automatically a database-level `FOREIGN KEY` constraint (Section 5).
5. **Use database foreign keys where the referenced record is in the same ownership boundary**, or where the team intentionally accepts strong relational coupling for a specific, justified reason (e.g., within one aggregate's own child tables).
6. **Use application-validated references where bounded-context separation requires looser coupling** — the Application layer validates the reference exists and is in an appropriate state at write time, rather than relying on a database constraint that would create a physical cross-schema dependency.
7. **Never allow direct ORM mutation across contexts** — restated from [internal-integration-architecture.md](../01-architecture/internal-integration-architecture.md); a reference is read-only from the referencing context's perspective.
8. **Avoid cascading deletes across bounded contexts** — deleting a `Delegation` must never cascade-delete `AthleteRegistration` rows owned by a different context; each context manages its own response to an upstream deactivation (typically: mark dependent records inactive, never silently delete them).
9. **Preserve historical references even if the source record later changes** — a `ProtestCase` referencing an `OfficialResult` version keeps pointing at that exact version even after a later correction produces a new version.

### Worked Examples

| Referencing Context | Referenced Context | Pattern Used | Why |
|---|---|---|---|
| Athlete Registration → Participant Registry | Cross-context | Authoritative external ID (Participant public ID) | Registration never duplicates identity data it doesn't own |
| Eligibility → Registration + Participant | Cross-context | Authoritative external IDs | Eligibility case is meaningless without knowing who/what it evaluates |
| Competition Entry → Athlete Registration | Cross-context | Authoritative external ID, gated by application-validated state check ("is this registration eligible-cleared?") | The gate is a business rule (Phase 0.3 SOD/state check), not a database constraint |
| Scoring → Tournament competition unit | Cross-context | Authoritative external ID (Match/Heat public ID) | A score is meaningless without its match context |
| Official Results → Score Records | Cross-context, via Anti-Corruption Layer | Snapshot of validated score values at certification time, not a live foreign key to mutable score rows | Per [context-map.md, "Anti-Corruption Layers"](../01-architecture/context-map.md#anti-corruption-layers--explicit-justification) — Results must never be affected by a later Scoring-side change to the same rows |
| Medal Tally → Official Results | Cross-context, via Published Language | Reference to a specific certified Official Result *version* | Tally must never silently follow a superseded result |
| Public Results → Official Results | Cross-context, downstream projection | Snapshot/projection of the published result version | Per [phase-0.2-domain-architecture.md, Section 16](../01-architecture/phase-0.2-domain-architecture.md#16-public-data-boundary) — the public projection is never a live foreign key into the authoritative table |
| Access Validation → Accreditation | Cross-context, via cached Published Language | Cached credential-validity set (public ID + validity), refreshed on sync | Enables offline validation without a live database connection |
| Document metadata → owning aggregate | Cross-context (polymorphic-shaped) | Owning-context type + owning-aggregate public ID, application-validated | See Section 5 for why this is not a database-level polymorphic relationship |

## 5. Foreign Keys vs. Application-Validated References

Per working rule 26 (avoid polymorphic relationships where they weaken integrity or ownership), a genuinely polymorphic reference (e.g., Document and Records' "this document belongs to *some* aggregate in *some* context") is **not** implemented as a single MySQL polymorphic foreign-key column pointing at an unconstrained "type + ID" pair with no referential integrity. Instead:

- Where the set of possible owning aggregate types is small, bounded, and known (e.g., Document and Records' owners are limited to a specific list of contexts that produce evidence), the Application layer validates the reference at write time, and the owning-context type is an enumerated, versioned value (per [database-naming-and-design-standards.md](database-naming-and-design-standards.md)), not an open string.
- The database does not attempt to enforce referential integrity across this kind of reference with a native foreign key (since the referenced table varies) — integrity here is an **application-layer responsibility**, explicitly documented as such, with orphan-detection routines (per [object-metadata-and-file-lifecycle.md](object-metadata-and-file-lifecycle.md)) compensating for what the database cannot enforce natively.
- **Same-context, non-polymorphic references always prefer a real database foreign key** — this rule specifically targets the cross-context/polymorphic case, not an excuse to avoid foreign keys generally.

## 6. Open Questions

- Whether ULID or UUIDv7 (a newer, similarly time-sortable standard) is the specific format adopted — both satisfy the requirements above; the final choice is an implementation-phase (Phase 0.6) detail.
- Whether every context adopts the public-ID pattern uniformly from the first migration, or whether it is introduced incrementally starting with the offline-critical and public-facing contexts (Scoring, Access Validation, Official Results, Medal Tally) first.

Tracked in [data-open-decisions.md](data-open-decisions.md).
