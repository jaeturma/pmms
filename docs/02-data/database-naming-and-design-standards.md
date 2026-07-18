# PMMS Database Naming and Design Standards

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md) · [conceptual-schema-catalog.md](conceptual-schema-catalog.md)

This document defines naming and value-representation conventions for future physical schema design. **No physical table or column is created here.**

---

## 1. Table Naming

- Plural, snake_case: `athlete_registrations`, `official_results`, `medal_awards`.
- Pivot/join tables: alphabetical or domain-meaningful compound, snake_case, singular concept names joined: `committee_memberships` (preferred, domain-meaningful) over a raw `committees_users` join-table name where a real business concept exists.
- Avoid ambiguous abbreviations (`elig_cases` — no; `eligibility_cases` — yes).
- Avoid MySQL reserved words as table or column names (`order`, `group`, `condition`, etc.).
- Table names never encode a bounded-context ID (no `bc09_eligibility_cases`) — the module/namespace boundary (per [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md)) is where context ownership lives, not the table name.

## 2. Column Naming

| Column Purpose | Convention | Example |
|---|---|---|
| Primary key | `id` | `id` |
| Public identifier | `public_id` (or a context-meaningful equivalent) | `public_id`, `credential_number` |
| Foreign key (same-context or cross-context reference) | Singular snake_case + `_id` | `participant_id`, `meet_id` |
| Boolean | `is_` / `has_` prefix | `is_active`, `has_conflict_declared` |
| Timestamp (point in time) | `_at` suffix | `submitted_at`, `certified_at` |
| Date (calendar date only, no time) | `_on` or `_date` suffix | `effective_date`, `birth_date` |
| Status/state | `_status` suffix, always an explicit enumerated value, never inferred | `case_status`, `credential_status` |
| Type/category (where multiple distinct meanings could exist) | `_type` suffix, scoped explicitly to avoid ambiguity | `document_type`, `incident_type` |
| Version | `_version` suffix or a dedicated `version` column on a versioned table | `result_version`, `version` |
| Sequence/order | `_sequence` or `_order` suffix | `round_sequence` |
| Quantity | `_count` suffix | `entry_count` |
| Monetary amount | `_amount` suffix, always paired with a currency indicator (Section 5) | `budget_amount`, `expense_amount` |
| Soft-delete marker | `deleted_at` (Laravel convention) | `deleted_at` |
| Audit/tracking | `created_at`, `updated_at`, `created_by`, `updated_by` | Standard Laravel + explicit actor columns for anything beyond Low sensitivity |

### Explicitly Avoided

- Bare `data`, `info`, `details`, or `value` column names without qualification (`registration_data` — no; a specific, named column — yes; a well-justified, narrowly-scoped JSON column with a qualified name — see Section 6).
- A single global `status` enumeration shared across unrelated contexts (per Section 3 below) — every context owns its own status vocabulary.
- A generic `type` column when the table could reasonably have more than one kind of "type" (sport type vs. document type vs. incident type must never collapse into one ambiguous `type` column on a shared table).

## 3. Status and State-Transition Persistence

- **Each bounded context owns its own state vocabulary.** `EligibilityCase.case_status` (`submitted`, `under_review`, `approved`, `rejected`, `reopened`) is a different, independently defined enumeration from `AccreditationCredential.credential_status` (`requested`, `issued`, `active`, `revoked`, `expired`) — never a shared "status" lookup table serving both.
- **High-integrity transitions require explicit state history**, not just a current-status column — see [temporal-history-and-versioning-model.md, Section 1](temporal-history-and-versioning-model.md#1-state-and-status-persistence).
- **Avoid generic status tables shared by unrelated contexts** — restated from working rule 27's "avoid generic key-value tables for important business rules"; a `statuses` table with a `context` discriminator column is exactly the anti-pattern this rule prohibits.
- **State transitions are validated by domain logic**, not inferred from timestamps — a record is not "approved" merely because `approved_at IS NOT NULL`; it has an explicit `case_status = 'approved'` value that domain logic set deliberately, with `approved_at` as corroborating metadata, not the source of truth for state.
- **Distinct concepts are never collapsed:** cancellation, rejection, revocation, withdrawal, expiration, supersession, and deletion are different business events with different meanings and different downstream consequences — each context's status vocabulary names them distinctly, never folding several into one generic "inactive" value.
- **Provisional, validated, certified, published, held, and superseded results remain distinguishable** — Official Results' status vocabulary has a distinct named value for each (per [high-integrity-data-model.md](high-integrity-data-model.md)).
- **Assignment status and credential status are distinct** — an assignment's lifecycle state (per [../01-architecture/assignment-model.md, Section 4](../01-architecture/assignment-model.md#4-assignment-lifecycle)) is never conflated with an accreditation credential's separate lifecycle state, even though both may apply to the same person at the same time.
- **Historical state changes retain actor, time, reason, and source** — every state transition on a high-integrity record is itself a row in a history table (Section 1 of [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md)), not merely an overwritten column.

## 4. Timestamp and Time-Zone Standards

- **Store all timestamps in UTC.** No exceptions — this is the only way to compare/order timestamps correctly across venues, devices, and future time zones.
- **Display in the meet's or user's local time zone** at the presentation layer — a purely UI/Application-layer concern, never a storage-layer one.
- **Preserve the original source time zone where required** for a record whose *meaning* depends on local time (e.g., a schedule slot's local start time), stored alongside the UTC value, not instead of it.
- **Date-only values are distinct from date-time values** — a `birth_date` is a calendar date with no time-zone ambiguity; an `approved_at` is a precise instant and always carries UTC semantics.
- **Scheduled time is distinct from actual/occurred time** — `scheduled_start_at` (planned) and `actual_start_at` (what really happened) are two different columns, never conflated, since a schedule change and a late start are different facts.
- **System-recorded time is distinct from user-declared occurrence time** — `recorded_at` (when the system captured the row) versus `occurred_at` (when the user says the event actually happened) matter distinctly for offline-captured records, where the two can differ by hours.
- **Device-recorded time and server-received time are both preserved for offline records** — per [../01-architecture/offline-authorization-model.md, Section 12](../01-architecture/offline-authorization-model.md#12-time-drift): `device_occurred_at` and `server_received_at` are both retained, never one overwriting the other, since device clocks may drift during extended disconnection.
- **Synchronization time is tracked separately** — `synchronized_at` records when a record was actually reconciled with the server, distinct from when it was created on-device.
- **Client clocks are never trusted as the sole ordering authority** for anything requiring strict sequencing (e.g., which of two conflicting score corrections happened "first") — server-received order and/or explicit sequence metadata supplement device timestamps.
- **A meet's time zone is explicit**, stored on the `Meet` aggregate, not inferred from server location or assumed to be a single national time zone indefinitely (future regional/national meets, per [Phase 0.1](../00-product/phase-0.1-product-foundation.md#16-organizational-model), could plausibly span time zones).
- **Historical records preserve their effective time-zone context** — a report generated from a 2026 meet's data displays using that meet's recorded time zone, not the time zone active on the server when the report is later generated.

### Conceptual Timestamp Fields (Not Physical Columns)

Created at, updated at, occurred at, recorded at, submitted at, approved at, certified at, published at, revoked at, effective from, effective until, device occurred at, server received at, synchronized at — each used only where the underlying business concept actually needs it, never added reflexively to every table.

## 5. Monetary Value Standards

- **Fixed-precision decimal storage only** — MySQL `DECIMAL(precision, scale)`, never `FLOAT`/`DOUBLE`, for any monetary amount, to avoid floating-point rounding error in financial calculations.
- **Currency is stored explicitly** wherever multi-currency is even plausible — even though PMMS's initial scope is domestic (PHP-denominated), the column exists rather than being assumed, consistent with the commercial-quality/future-readiness direction from [Phase 0.1](../00-product/phase-0.1-product-foundation.md#18-commercial-quality-product-direction).
- **Original amount and approved amount are stored separately** where a financial workflow can adjust a requested figure (e.g., a budget request vs. its approved allocation) — never overwriting the original with the approved value.
- **Distinct financial concepts are never collapsed:** budget, obligation, disbursement, cash advance, liquidation, reimbursement, and adjustment are different business facts (per [../01-architecture/permission-catalog.md, "Finance"](../01-architecture/permission-catalog.md#finance-bc-26) category) — each gets its own explicit representation, not a single generic "amount" with a `type` flag standing in for fundamentally different accounting semantics.
- **Financial correction and approval history is preserved** — per [high-integrity-data-model.md](high-integrity-data-model.md), a financial record correction is a new, attributed entry, never a silent balance overwrite.
- **Formatted currency strings are never stored** — `"₱1,234.56"` is a display-layer concern; the stored value is a plain decimal plus a currency code.
- **Rounding is defined only through approved business rules** — no rounding behavior is invented in this document; it is deferred to whatever DepEd/finance-committee policy governs financial reconciliation (see [data-open-decisions.md](data-open-decisions.md)).
- **No government accounting rule is invented** — PMMS's Finance Operations context is explicitly not a full accounting system (per [../01-architecture/bounded-context-catalog.md, BC-26](../01-architecture/bounded-context-catalog.md#bc-26--finance-operations)); this document defines storage hygiene, not accounting policy.

## 6. Score, Measurement, and Timing Standards

- **Store normalized, machine-comparable values where possible** — e.g., a time result stored as a precise duration value (fixed-precision, not floating-point) that can be sorted/compared programmatically, alongside a display-formatted string for presentation.
- **Preserve the display value and unit** where a sport's convention requires a specific human-readable format (e.g., `10.87s`, `1.95m`) distinct from the normalized comparable value.
- **Preserve the originally entered value** even after any correction — corrections are versioned (per [high-integrity-data-model.md](high-integrity-data-model.md)), never destructive.
- **Preserve the device source** that captured the value, per [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)'s device-generated-ID pattern.
- **Preserve precision** — a measurement's recorded precision (e.g., hundredths of a second vs. whole seconds) is retained as recorded, never silently rounded at storage time.
- **Preserve the rule-set version** the score was recorded and validated against, per [conceptual-schema-catalog.md](conceptual-schema-catalog.md)'s `SportDefinition`/`EventDefinition` versioning requirement — so a later rule change never retroactively reinterprets a historical score.
- **Avoid one universal "score" column for all sports.** A time-based sport, a points-based sport, and a judged sport have fundamentally different value shapes; `ScoreRecord`'s conceptual model (per [conceptual-schema-catalog.md](conceptual-schema-catalog.md)) accommodates sport-specific value representations rather than forcing every sport through a single generic numeric column — the specific per-sport-format design is deferred to Phase 0.6 physical schema design, informed by the sport-format decisions still pending under [../01-architecture/domain-open-decisions.md, DD-13](../01-architecture/domain-open-decisions.md#dd-13--sport-specific-plugin-strategy-and-tournament-format-configurability).
- **Avoid floating-point storage for precision-sensitive measurements** — same reasoning as monetary values (Section 5); fixed-precision decimal or integer (with an explicit unit/scale) is used instead.
- **No actual scoring formula is invented** — this document defines storage hygiene only; the specific formula for any sport is sourced from an approved authority, per [../01-architecture/domain-open-decisions.md, DD-13](../01-architecture/domain-open-decisions.md#dd-13--sport-specific-plugin-strategy-and-tournament-format-configurability) and [../00-product/open-decisions.md, OD-10](../00-product/open-decisions.md#od-10--sports-rule-source).

## 7. JSON Columns — Narrow, Justified Use Only

Per working rules 28–29, a JSON column is **not** a substitute for proper domain modeling. It is used only where:

- Content is genuinely variable/unstructured and does not warrant a normalized table (e.g., a raw external-integration payload retained for audit purposes).
- It represents a versioned snapshot whose internal shape is expected to vary over time and whose primary purpose is faithful reproduction, not queryability (e.g., a `SportDefinition` version's full rule-reference payload).
- It stores external payloads (webhook bodies, third-party API responses) whose shape PMMS does not control.

A JSON column is **never** used for:

- Important business rules that should be relationally modeled and queryable (e.g., eligibility criteria, scoring rules) — these belong in proper normalized tables or the Application/Domain layer, not a JSON blob.
- A substitute for adding a real column when the data is structured, always-present, and queried directly.
- Something with unclear ownership — every JSON column has a named owning context and a documented reason it isn't normalized.

## 8. Index, Constraint, and Naming Suffixes (Summary)

Full indexing strategy: [indexing-performance-and-capacity.md](indexing-performance-and-capacity.md). Naming convention: `idx_<table>_<columns>` for indexes, `uq_<table>_<columns>` for unique constraints, `fk_<table>_<referenced_table>` for foreign keys — Laravel's default constraint-naming convention is acceptable and consistent with this pattern; no custom naming scheme is imposed where the framework default already satisfies clarity and consistency.
