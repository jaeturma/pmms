# PMMS Temporal, History, and Versioning Model

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [database-naming-and-design-standards.md](database-naming-and-design-standards.md) · [high-integrity-data-model.md](high-integrity-data-model.md) · [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md)

This document defines PMMS's approach to state persistence, soft deletion, immutable/append-only history, versioning/supersession, and effective-dated (temporal) data. **No physical table is created here.**

---

## 1. State and Status Persistence

See [database-naming-and-design-standards.md, Section 3](database-naming-and-design-standards.md#3-status-and-state-transition-persistence) for the full status-vocabulary rules. The key architectural commitment: **every context owns its own status vocabulary**, and **high-integrity state transitions require an explicit history record**, not merely a mutated current-status column. A `case_status` column tells you *what* an `EligibilityCase` currently is; a companion history table tells you *how it got there* — who changed it, when, and why, for every transition, not just the most recent one.

## 2. Soft Deletion

### Appropriate For (Low-Stakes, Recoverable Records)
Draft operational records, recoverable reference records, user-created content still in a mutable/pre-final state, non-final configuration records — e.g., a `SupportTicket` (BC-27), a `BilletingAssignment` (BC-22) before check-in, a draft `Announcement` (BC-28) before publication.

Laravel's `deleted_at` soft-delete convention (per [database-naming-and-design-standards.md, Section 2](database-naming-and-design-standards.md#2-column-naming)) is appropriate here — the record is genuinely "gone" from an operational standpoint but recoverable if the deletion was a mistake.

### Insufficient or Prohibited (High-Integrity Records)
Official scores, certified results, eligibility decisions, protest decisions, medal awards, access scans, medical encounters, financial records, audit events, assignment history.

**Soft deletion is never used for these**, because `deleted_at IS NOT NULL` collapses every reason a record might no longer be "current" (superseded, revoked, cancelled, corrected) into one undifferentiated flag — exactly the ambiguity [high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) exists to prevent. Instead, these records use one or more of:

- **Cancellation** — an explicit status value, with reason, never a deletion.
- **Revocation** — for credentials/assignments: an explicit status value, with reason and revoking actor.
- **Reversal** — for a decision found to be wrong: a new record/state explicitly reversing the prior one, both retained.
- **Supersession** — for versioned records (Section 4): a new version replaces the prior one as "current," but the prior version row is never deleted.
- **Inactivation** — for reference data (e.g., a deprecated `SportDefinition` version): marked inactive, never removed.
- **Archival** — moved to a historical/read-only state (per [retention-archival-and-disposal.md](retention-archival-and-disposal.md)), not deleted.
- **Correction version** — a new, attributed version corrects a prior one (Section 4).

## 3. Immutable History and Append-Only Records

### Append-Only Candidates
Audit events, security events, access scans, score submissions, score corrections, result certifications, result supersessions, protest decisions, medal tally certifications, eligibility decision history, accreditation issuance/revocation, medical encounter history, financial approval history, assignment lifecycle history, device credential history, data import history, consequential AI-assisted recommendation records, public publication history.

### Principles

- **Append-only intent** — a new fact is always a new row; an existing row's meaning, once written, is never altered (audit events, per [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md), take this furthest: literally never modified, ever).
- **Controlled correction references** — where a correction is needed, the new row explicitly references the row it corrects/supersedes (a `corrects_id` / `supersedes_id`-style relationship, per Section 4), rather than the two being connected only implicitly by matching business keys.
- **No silent mutation** — restated as the persistence-layer expression of the same-named principle in [high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md).
- **Integrity checks** — for the highest-stakes append-only tables (audit events especially), a tamper-evidence mechanism (e.g., a hash chain linking each row to the previous) is a candidate control, evaluated in [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md) rather than assumed here.
- **Export restrictions** — append-only history for Restricted/Highly Restricted domains (medical, eligibility evidence, finance) is exported only through the controlled export path in [import-export-and-data-exchange.md](import-export-and-data-exchange.md), never a raw table dump.
- **Retention requirements** — append-only tables typically carry the platform's longest retention periods (per [retention-archival-and-disposal.md](retention-archival-and-disposal.md)), since their entire value is the permanent record they provide.
- **Administrative access restrictions** — even a Platform Administrator does not have blanket UPDATE/DELETE privilege on an append-only table in the target operational model; the database user the application connects as should have no `UPDATE`/`DELETE` grant on tables like `audit_events` at all, only `INSERT`/`SELECT` (a Phase 0.6 physical-implementation recommendation, not implemented now).

## 4. Versioning and Supersession

| Versioned Concept | Version Identity | Current/Previous Tracking | Effective Period | Actor/Reason Required | Approval | Publication Status | Rollback |
|---|---|---|---|---|---|---|---|
| Eligibility case decisions | Decision sequence per case | Latest decision is current; prior decisions retained | N/A (point decisions) | Yes | Per [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) | N/A | Reopen, not rollback |
| Documents | Version number per document | Current version flagged; priors retained | N/A | Yes (uploader) | Context-dependent | N/A | New version supersedes; no true rollback |
| Sports rule references | Version number per `SportDefinition`/`EventDefinition` | Current version flagged; priors retained, never deleted | Yes (effective from/until) | Yes | Sports Specialist sign-off | Published/Draft | Deprecate a version, never delete |
| Tournament configurations | Regeneration count pre-publication | Only the published draw is "current"; pre-publication regenerations are not separately versioned | N/A | Yes, for post-publication changes | Tournament Manager | Published/Draft | Regeneration only pre-publication |
| Schedules | Revision number | Current revision flagged; priors retained | Yes | Yes | Organizing Committee | Published/Draft | New revision supersedes |
| Scores | Correction sequence per score record | Latest correction is current; originals retained | N/A | Yes | Validator (SOD-02) | N/A | Correction only, never rollback to a "wrong" value silently |
| Official results | Explicit version number, supersession chain | Current version flagged; full chain retained | N/A | Yes | Per [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain) | Certified/Published distinct states | Supersession via new version, triggered by protest resolution |
| Public result projections | Mirrors source Official Result version | Current published version only shown publicly | N/A | N/A (derived) | N/A (derived) | Published | Rebuilt from source, never independently rolled back |
| Medal tallies | Snapshot version per recalculation | Current snapshot flagged; all snapshots retained | N/A | Yes | Per [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules) | Published/Preliminary | Recalculation produces new snapshot |
| Accreditation templates | Version number | Current flagged | Yes | Yes | Accreditation Officer | N/A | New template version, priors retained for reference |
| Report templates | Version number | Current flagged | Yes | Yes | Reporting owner | N/A | New version |
| Publication items | Mirrors source | Current only | N/A | N/A | N/A | Published | Rebuilt |
| API contracts | Semantic version, per [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md) | Multiple concurrently supported versions possible | Yes (deprecation window) | Yes | Software architect | N/A | Deprecation, not rollback |
| Import templates | Version number | Current flagged | Yes | Yes | Import owner | N/A | New version |

### Common Versioning Fields (Conceptual)

Version identity, current version flag, previous version reference, "superseded by" reference, effective period (from/until), actor, reason, approval reference, publication status, rollback limitations — applied selectively per concept above, never uniformly forced onto a concept that doesn't need full versioning (e.g., a `BilletingAssignment` does not need a supersession chain; a simple reassignment is sufficient).

## 5. Temporal and Effective-Dated Data

Concepts requiring effective dating (a value that is true for a specific period, potentially with a known future or past validity window):

Organization hierarchy, school names/identifiers, role assignments, meet assignments, committee membership, delegation membership, technical-official assignments, credential validity, sports rule references, eligibility requirement sets, venue availability, schedule changes, public publication windows, user-access restrictions, device assignments, feature configuration.

### Rules

- **Effective from / Effective until** — every effective-dated concept carries both, with `Effective until` nullable to represent "still current."
- **Recorded at** — when the effective-dated fact was entered into the system, distinct from when it becomes/became effective (a change can be recorded today but effective next week, or recorded today but effective retroactively from last month, if the business process allows).
- **Superseded at** — when a later effective-dated record replaced this one as current.
- **Source** — what triggered the effective-dated change (e.g., a committee resolution, an assignment renewal).
- **Reason** — required for anything touching a high-integrity or access-relevant concept (role/meet/committee assignments, credential validity).
- **Overlap rules** — two effective-dated records for the same subject/concept must not have overlapping validity windows unless the business concept genuinely allows concurrent validity (e.g., a user holding two simultaneous assignments in different scopes is fine; the same assignment scope having two conflicting "current" values is not, per [../01-architecture/assignment-model.md, Section 9](../01-architecture/assignment-model.md#9-assignment-conflicts)).
- **Historical query expectations** — "what was this person's role assignment on July 15, 2026" must be answerable from the effective-dated history, not just "what is it now" — this is the persistence-layer requirement underlying [../01-architecture/access-review-and-revocation.md](../01-architecture/access-review-and-revocation.md)'s audit/review capabilities.

## 6. Relationship to High-Integrity Domains

Every high-integrity domain named in [high-integrity-data-model.md](high-integrity-data-model.md) uses some combination of append-only history (Section 3), explicit versioning (Section 4), and/or effective dating (Section 5) — never destructive overwrite. This document defines the *general mechanisms*; [high-integrity-data-model.md](high-integrity-data-model.md) applies them to each specific domain's actual workflow.
