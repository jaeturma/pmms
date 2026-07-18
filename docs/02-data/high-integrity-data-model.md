# PMMS High-Integrity Data Model

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [temporal-history-and-versioning-model.md](temporal-history-and-versioning-model.md) · [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) · [../01-architecture/high-integrity-access-controls.md](../01-architecture/high-integrity-access-controls.md)

This document gives persistence-layer treatment to the 11 high-integrity domains established consistently since Phase 0.2, plus the general data-quality and correction-architecture principles that apply across them. **No physical table or migration is created here.**

---

## Participant Identity (BC-07)

- **Persistence model:** One `Participant` row per canonical identity, plus an append-only identity-correction history table.
- **Duplicate prevention:** No database-level uniqueness constraint can fully prevent duplicate identities (two genuinely different rows describing the same real person) — this requires the AI-advisory duplicate-detection process from [identity-resolution-and-duplicate-management.md](identity-resolution-and-duplicate-management.md); the database instead ensures every *merge decision* is itself recorded, attributed, and reversible-by-record (never a silent row deletion).
- **Historical identity:** A `Participant` row persists across meet cycles (per [logical-data-architecture.md, Section 4](logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles)) — never re-created per meet.
- **Correction:** Biographical corrections are versioned entries in the correction-history table, never an in-place `UPDATE` with no record of the prior value.
- **Merge and unmerge:** A merge decision creates an explicit `IdentityMergeDecision`-style record referencing both source rows and the resulting canonical row; an "unmerge" is a new correction decision, never a literal database rollback.
- **Source tracking:** Every `Participant` row and every correction records its originating source (self-registered, Secretariat-entered, imported) for audit purposes.
- **Minor-athlete protection:** See [information-classification-and-privacy.md](information-classification-and-privacy.md) for classification and access-restriction detail on this table.

## Athlete Registration (BC-08)

- **Persistence model:** One `AthleteRegistration` row per meet-scoped registration, referencing `Participant` and `Delegation`.
- **Submission versions:** Amendments before eligibility review begins are unrestricted edits to the draft; once review begins, further changes are logged as explicit amendment events, not silent edits.
- **Withdrawal:** An explicit `withdrawn` status with reason and actor — never a deletion.
- **Locking:** `athlete-registration.lock` (per [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md)) sets an explicit lock state after which further changes require an audited exception process, not an ordinary edit path.
- **Delegation ownership:** Enforced at the application layer (a delegation may only modify its own registrations) — see [../01-architecture/scope-model.md](../01-architecture/scope-model.md).
- **Eligibility relationship:** A registration references, but never contains, its eligibility case — the two remain separately owned tables even though tightly coupled in workflow.

## Eligibility (BC-09) — **Critical**

- **Persistence model:** One `EligibilityCase` row per case, with an append-only decision-history table recording every review finding and decision.
- **Requirement-set version:** Every case references the specific version of the eligibility requirement set it was evaluated against (per [temporal-history-and-versioning-model.md, Section 5](temporal-history-and-versioning-model.md#5-temporal-and-effective-dated-data)), so a later policy change never retroactively reinterprets a historical decision.
- **Evidence references:** Links to Document and Records (BC-30) metadata — the case table itself never stores document content.
- **Findings, approval, rejection, reopen, override:** Each is a distinct, attributed row in the decision-history table — never a single mutable "decision" column.
- **Decision history:** Immutable once written (Section, [temporal-history-and-versioning-model.md, Section 3](temporal-history-and-versioning-model.md#3-immutable-history-and-append-only-records)).
- **Restricted access:** Enforced at the application/authorization layer per [../01-architecture/high-integrity-access-controls.md, "Eligibility"](../01-architecture/high-integrity-access-controls.md#eligibility); at the persistence layer, this means the case table and its evidence links are never exposed through a general-purpose query path accessible outside the owning context.
- **Blocking dependency:** The actual eligibility criteria and approval authority are **blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)** — this document defines the storage shape, not the criteria.

## Scoring (BC-15) — **Critical**

- **Persistence model:** One `ScoreRecord` row per captured value, with every correction as a new, linked row — never an `UPDATE` to a prior score value.
- **Source, encoder, device:** Every row records the capturing official, the device (per [../01-architecture/device-and-service-identity-model.md](../01-architecture/device-and-service-identity-model.md)), and — for offline captures — both device-occurred and server-received timestamps (per [database-naming-and-design-standards.md, Section 4](database-naming-and-design-standards.md#4-timestamp-and-time-zone-standards)).
- **Submission version:** Each correction increments a version counter scoped to the specific score record; the current version is flagged, all prior versions retained.
- **Validation:** A separate `validated_by`/`validated_at` pair, structurally distinct from the entering official's fields, enforcing SOD-02 (per [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md)) at the data-shape level — the schema itself should make it awkward, not merely discouraged, to populate both fields with the same actor.
- **Locking:** Once validated and consumed by result certification, further correction requires the formal correction path in Section "Data Correction Architecture" below, not an ordinary edit.

## Official Results (BC-16) — **Critical**

- **Persistence model:** One row per result *version*, with an explicit version number and a `supersedes`/`superseded_by` self-reference chain — never a single mutable "the result" row.
- **Certification:** `certified_by`/`certified_at`, distinct from any validating-official fields on the source score records (SOD-02/SOD-03).
- **Hold:** An explicit hold flag/state, set by Protest and Appeals (BC-17) through the documented cross-context interaction (per [../01-architecture/context-map.md](../01-architecture/context-map.md)), blocking publication while active.
- **Supersession:** A protest-triggered correction creates a new version row referencing the prior one — the prior version remains queryable forever, never deleted.
- **Publication:** A distinct `published_by`/`published_at`, separate from certification, per [../01-architecture/domain-open-decisions.md, DD-17](../01-architecture/domain-open-decisions.md#dd-17--public-publication-approval-chain).
- **Source score references:** Every result version records exactly which score-record versions it was assembled from (an explicit reference set, not an implicit "whatever the scores currently say"), enabling full reproducibility of how a historical result was reached.
- **Blocking dependency:** Approval-chain authority is **blocked pending [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain)**.

## Protest and Appeals (BC-17) — **Critical**

- **Persistence model:** One `ProtestCase` row per protest, with an append-only decision/evidence history.
- **Filing:** Records the filer, grounds, and the exact Official Result *version* being protested (never a loose reference to "the result," which could otherwise later mean a different version).
- **Evidence:** Referenced via Document and Records metadata, same discipline as Eligibility.
- **Decision:** Attributed, timestamped, reasoned — an immutable history entry.
- **Result hold:** The cross-reference to Official Results' hold mechanism (Section above).
- **Resolution history:** Every step (filed → under review → decided → appealed → resolved) is a distinct row, not a mutated status.
- **Blocking dependency:** **Blocked pending [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority)**.

## Medal Tally (BC-18) — **Critical, derived**

- **Persistence model:** Versioned snapshot rows — every recalculation produces a new, fully retained snapshot, never an in-place update to "the current tally."
- **Derivation constraint:** The application/domain layer (not a database constraint alone) enforces that a tally snapshot's inputs are exclusively certified/published Official Result versions — the persistence design supports this by requiring every medal-award row to reference a specific Official Result version, never a raw score.
- **Recalculation, certification, publication:** Each a distinct, attributed step; a recalculated-but-not-yet-certified snapshot is queryable internally (`view-provisional`, per [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md)) but never surfaced publicly until published.
- **Correction:** A new snapshot version; prior public snapshots remain retained for historical/audit reference, per [../01-architecture/domain-open-decisions.md, DD-16](../01-architecture/domain-open-decisions.md#dd-16--medal-tally-correction-authority).
- **Blocking dependency:** **Blocked pending [Phase 0.1 OD-12/OD-13](../00-product/open-decisions.md#od-12--medal-tally-rules)**.

## Accreditation (BC-19) — **Critical**

- **Persistence model:** One `AccreditationCredential` row per credential, with an explicit status lifecycle (requested → issued → active → revoked/expired) and a replacement chain (a reissued credential references the one it replaces).
- **Credential identity:** The public ID (per [identifier-and-reference-strategy.md](identifier-and-reference-strategy.md)) is structurally distinct from the QR token value — two different columns, never one field serving both purposes.
- **Issuance, replacement, revocation:** Each an attributed, timestamped event, retained even after the credential is superseded.
- **Validation history:** Access Validation (BC-20) records its own scan history separately — Accreditation retains only its own issuance-side lifecycle, per the cross-context boundary in [../01-architecture/context-map.md](../01-architecture/context-map.md).

## Medical Operations (BC-21) — **Critical, Highly Restricted**

- **Persistence model:** One `MedicalEncounter` row per encounter, with amendment history retained (never overwritten).
- **Segregated access:** At the persistence layer, this means the medical schema group is physically/logically separable enough that a database-level access grant could, in a future implementation, restrict it to Medical-context application credentials only — evaluated further in [information-classification-and-privacy.md](information-classification-and-privacy.md).
- **Summary vs. detailed:** The status flag exposed to Eligibility via the documented Anti-Corruption Layer (per [../01-architecture/context-map.md, "Anti-Corruption Layers"](../01-architecture/context-map.md#anti-corruption-layers--explicit-justification)) is a **separate, minimal, derived value** — never a view or join directly onto the encounter table itself, so that no query path from outside Medical Operations can ever reach the detailed record even by accident.
- **Attachments:** Via Document and Records, classified Highly Restricted.
- **Emergency access audit:** Any access to a medical record outside the normal owning-committee path (e.g., an emergency-context lookup) is itself an audited event, per [../01-architecture/high-integrity-access-controls.md, "Medical Records"](../01-architecture/high-integrity-access-controls.md#medical-records).
- **Retention validation:** **Blocked pending [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling)**.

## Finance (BC-26)

- **Persistence model:** Distinct tables/rows for budget allocation, expense, and approval — never a single generic "financial transaction" table conflating fundamentally different accounting concepts (per [database-naming-and-design-standards.md, Section 5](database-naming-and-design-standards.md#5-monetary-value-standards)).
- **Amount precision:** Fixed-precision decimal, explicit currency.
- **Supporting documents:** Via Document and Records, Restricted classification.
- **Approval history:** Append-only, enforcing SOD-06 (encoder ≠ approver) at the data-shape level, same pattern as Scoring's SOD-02.
- **Reversal:** A correcting entry, never a balance overwrite.
- **Reconciliation:** A later-phase operational process this document does not design, but whose data (matched/unmatched transaction references) is anticipated as append-only history.

## Audit (BC-32) — **Critical, self-referential**

Full treatment in [audit-and-security-data-architecture.md](audit-and-security-data-architecture.md) — the single domain where "Correction Authority" is genuinely "none": a mistaken audit entry is corrected only by a new entry referencing the old one, never an edit or deletion.

---

## Data Quality Controls

| Quality Dimension | Applied To | Control |
|---|---|---|
| Completeness | Registration, eligibility evidence, competition entries | Required-field validation at the Application layer before a submission transitions out of Draft state |
| Validity | Scores, results, monetary amounts | Type/range/format validation before persistence; domain-specific rule validation (sport-specific score ranges, etc.) deferred to approved rule sources |
| Consistency | Cross-context references | Application-validated references (per [identifier-and-reference-strategy.md, Section 5](identifier-and-reference-strategy.md#5-foreign-keys-vs-application-validated-references)) checked at write time |
| Uniqueness | Participant identity, organization records | Duplicate-detection process (per [identity-resolution-and-duplicate-management.md](identity-resolution-and-duplicate-management.md)), plus database unique constraints for genuinely stable natural keys |
| Timeliness | Public projections, read models | Freshness timestamps exposed per [public-reporting-and-projection-data.md](public-reporting-and-projection-data.md) |
| Accuracy | Scores, results, medal tally | Independent validation step (SOD-02/03/04) before certification |
| Traceability | All high-integrity domains | Version chains and append-only history throughout this document |
| Referential integrity | Same-context relationships | Database foreign keys per [identifier-and-reference-strategy.md, Section 5](identifier-and-reference-strategy.md#5-foreign-keys-vs-application-validated-references) |
| Conformance | Sports rule application | Every score/result references the specific rule-set version it conforms to |
| Reproducibility | Historical meet records | Meet-closure preservation per [retention-archival-and-disposal.md](retention-archival-and-disposal.md) |

## Data Correction Architecture

### Correction Mechanisms

Draft edit (pre-submission, unrestricted), return for correction (reviewer sends back), controlled amendment (post-submission, logged), reversal (a wrong decision explicitly undone), supersession (a new version replaces the current one), reopen (a closed case is reactivated for re-decision), override (an exceptional, elevated-authority correction), merge/unmerge (identity), reconciliation (finance), administrative correction (a narrowly-scoped, heavily audited path for clerical errors), data repair under support control (the last resort — Section below).

### Required Fields for Any Correction

Actor, reason, time, approval where needed, previous-value reference, new-value reference, affected-projections list (which downstream projections/reports need to be informed), recalculation trigger (if the correction feeds a derived value like Medal Tally), notification trigger, audit record.

### Prohibition on Direct Production Database Edits

**Direct production database edits are prohibited except through a documented emergency-repair procedure.** This procedure itself does not yet exist as an implemented process (a Phase 0.6+ operational deliverable), but its requirement is established now: any direct data repair outside the normal application-mediated correction paths above must be logged with the same rigor as any other high-integrity correction (actor, reason, time, before/after values), reviewable by an independent party (per the Audit Viewer role, [../01-architecture/role-catalog.md, ROLE-05](../01-architecture/role-catalog.md#role-05--audit-viewer)), and treated as an exceptional event, never a routine troubleshooting shortcut.
