# PMMS High-Integrity Domain Rules

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [domain-events-catalog.md](domain-events-catalog.md) · [workflow-and-command-catalog.md](workflow-and-command-catalog.md) · [../00-product/phase-0.1-product-foundation.md, Section 8](../00-product/phase-0.1-product-foundation.md#8-product-principles)

This document defines architectural safeguards for PMMS's high-integrity domains. **It defines safeguard *patterns* — no actual sports rules, eligibility criteria, scoring formulas, protest procedures, or medal rules are defined or invented here** (per working rule 11). Those must come from an authorized DepEd/sports source (see the corresponding blocking items in [../00-product/open-decisions.md](../00-product/open-decisions.md) and [domain-open-decisions.md](domain-open-decisions.md)).

## Common Safeguard Principles

These principles apply to every high-integrity domain listed below, drawn directly from the Phase 0.1 product principles:

1. **No silent mutation** — a change to a high-integrity record is never invisible. Every change is a new, attributed fact, not an in-place edit that erases the prior state.
2. **Explicit state transitions** — high-integrity records move through a defined, finite set of states (e.g., Submitted → Under Review → Approved); transitions outside that defined set are structurally prevented, not just discouraged by convention.
3. **Actor identification** — every consequential action records *who* performed it, resolved to a real accountable identity, not a shared or system-generic account.
4. **Reason capture** — corrections, rejections, holds, and revocations record *why*, not just *what changed*.
5. **Timestamping** — every state transition is timestamped with a trustworthy clock source.
6. **Versioning** — high-integrity records that can be corrected (results, scores) are versioned; a "current" view always exists, but prior versions remain retrievable.
7. **Separation of duties** — the actor who performs an action is not, by design, the same actor authorized to approve/validate that same action (e.g., the scorer who enters a score is not the validator who confirms it — see WF-11 in [workflow-and-command-catalog.md](workflow-and-command-catalog.md)).
8. **Evidence retention** — decisions in high-integrity domains retain their supporting evidence (documents, prior scores, protest submissions) for the retention period established by DepEd policy (see [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)).
9. **Correction instead of destructive overwrite** — corrections supersede rather than erase; the erroneous prior state remains part of the historical record.
10. **Controlled publication** — a high-integrity outcome becoming visible to delegations/public is a distinct, deliberate step from the outcome being *decided* (see [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md) and the Public Data Boundary section of [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md#16-public-data-boundary)).
11. **Human approval** — no high-integrity outcome is finalized without a human decision from an authorized role, regardless of how strong an automated (including AI) recommendation might be.
12. **Restricted access** — high-integrity domains apply the narrowest access consistent with the role's actual need (least privilege), independent of the general role/permission architecture defined in Phase 0.3.
13. **Testable invariants** — each safeguard below should be expressible as a testable business invariant once implementation begins (e.g., "a `ScoreValidated` event's validator must not equal its `ScoreRecorded` event's actor").
14. **Immutable history where appropriate** — for the most sensitive record types (audit events, certified results, medal awards), the historical record itself is append-only and never deleted, even when superseded.
15. **AI advisory-only behavior** — AI may assist, detect, summarize, or recommend in every domain below, but never independently decides the outcome (see Section "AI Limitations" per domain and [../00-product/phase-0.1-product-foundation.md, Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction)).

---

## Participant Identity — [BC-07](bounded-context-catalog.md#bc-07--participant-registry)

- **Why integrity is critical:** Every downstream decision (eligibility, entries, accreditation, results attribution) depends on correct identity resolution. Misidentification enables both innocent duplication and deliberate ineligible substitution.
- **Required separation of duties:** The person proposing a match/merge (which may include AI-assisted suggestion) is not the sole authority finalizing it.
- **Required evidence:** Basis for a match/merge decision (matching criteria used, confidence signal) is retained.
- **Required auditability:** Every identity creation and correction is logged with actor and reason.
- **Allowed correction pattern:** Identity corrections are versioned; the registry never silently merges two records without a retained record of the merge decision.
- **Prohibited silent changes:** No automatic, unreviewed merge of two participant records, even when AI-suggested confidence is high.
- **Publication restrictions:** Full identity data is never directly public; only approved, minimal display fields flow to Public Information via the relevant downstream context.
- **Retention expectations:** Retained per DepEd records policy (see [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)); historical participation identity is expected to persist across meet cycles.
- **AI limitations:** AI may suggest duplicate matches (advisory); it must never auto-merge or auto-create a corrected identity without human confirmation.

## Athlete Registration — [BC-08](bounded-context-catalog.md#bc-08--athlete-registration)

- **Why integrity is critical:** Registration is the gateway to eligibility, entries, and accreditation; errors here propagate downstream.
- **Required separation of duties:** Submission (coach/delegation) is distinct from Secretariat review.
- **Required evidence:** Submitted registration data and, where applicable, guardian consent record.
- **Required auditability:** Submission, review, and withdrawal events are logged.
- **Allowed correction pattern:** Amendments before eligibility review begins are unrestricted; amendments after review begins are logged exceptions.
- **Prohibited silent changes:** No retroactive registration edits after eligibility approval without a visible amendment trail.
- **Publication restrictions:** Registration status is not directly public.
- **Retention expectations:** Meet-scoped, retained per DepEd policy.
- **AI limitations:** AI may flag missing requirements (advisory); it must not auto-approve or auto-complete a registration.

## Eligibility — [BC-09](bounded-context-catalog.md#bc-09--eligibility-and-clearance-high-integrity)

- **Why integrity is critical:** Directly determines who may compete; incorrect decisions cause disqualification, reputational harm, and possible legal exposure (RSK-01 in [Phase 0.1](../00-product/assumptions-constraints-risks.md)).
- **Required separation of duties:** The reviewer approving/rejecting a case is authorized and distinct from the submitter; the specific authorized role is **blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)**.
- **Required evidence:** All submitted documentation and the reviewer's stated reason are retained.
- **Required auditability:** Every decision (approve/reject/appeal outcome) is logged with actor, timestamp, and reason — **critical** level.
- **Allowed correction pattern:** An approved case may only be reversed through a documented, audited reversal process — never a silent status flip.
- **Prohibited silent changes:** No automatic approval/rejection; no unattributed status changes.
- **Publication restrictions:** Eligibility case detail is never public; only a downstream cleared/not-cleared gate is exposed to dependent contexts (BC-11, BC-19).
- **Retention expectations:** High — this is a legally and institutionally significant record; retention duration requires DepEd confirmation.
- **AI limitations:** AI may assist with document-completeness checks and flag missing requirements (advisory only); **AI must never independently approve or reject eligibility**, per [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction).

## Competition Entries — [BC-11](bounded-context-catalog.md#bc-11--competition-entries)

- **Why integrity is critical:** Entries determine who competes in what, feeding directly into tournament structure and eligibility-dependent gating.
- **Required separation of duties:** Submission (coach) is distinct from validation/locking (Tournament Manager).
- **Required evidence:** Entry submission history, including substitutions/withdrawals.
- **Required auditability:** Standard, elevated at lock.
- **Allowed correction pattern:** Pre-lock changes unrestricted; post-lock changes require an explicit, audited exception process.
- **Prohibited silent changes:** No entry modification after lock without a logged exception.
- **Publication restrictions:** Only aggregate entry counts are public before competition; individual entries are not broadly published ahead of competition unless approved.
- **Retention expectations:** Meet-scoped, standard retention.
- **AI limitations:** AI may detect entry conflicts or limit violations (advisory); it must not auto-reject or auto-confirm an entry.

## Tournament Progression — [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression)

- **Why integrity is critical:** The draw/bracket/seeding structure determines the competitive path for every entry; manipulation or error here is a fairness violation.
- **Required separation of duties:** Draw generation and draw publication may be the same role, but any post-publication regeneration requires a distinct authorization and logged justification.
- **Required evidence:** Seeding basis and draw generation parameters are retained.
- **Required auditability:** High — draws are a competition-integrity artifact.
- **Allowed correction pattern:** Pre-publication regeneration is unrestricted; post-publication changes are logged exceptions (e.g., a withdrawal forcing a bracket adjustment).
- **Prohibited silent changes:** No undisclosed re-draw after publication.
- **Publication restrictions:** Draws are published deliberately, not automatically the instant they are generated (to allow a verification step).
- **Retention expectations:** Retained as part of the competition record.
- **AI limitations:** AI may propose a seeding/draw arrangement per defined rules (advisory); a human confirms before publication.

## Scoring — [BC-15](bounded-context-catalog.md#bc-15--scoring-high-integrity)

- **Why integrity is critical:** Scores are the raw evidentiary basis for every downstream official outcome; RSK-03 (unauthorized score changes) is a named Phase 0.1 risk.
- **Required separation of duties:** The scorer who enters a score is not the validator who confirms it (WF-11).
- **Required evidence:** Source device/actor identity captured with every score record.
- **Required auditability:** **Critical** — full scoring audit history retained.
- **Allowed correction pattern:** Corrections are versioned revisions with a retained prior value and stated reason — never a destructive overwrite.
- **Prohibited silent changes:** No score alteration without a logged `ScoreCorrected` event.
- **Publication restrictions:** Raw scores are not directly public; only certified Official Results are.
- **Retention expectations:** High — evidentiary record for potential protests.
- **AI limitations:** AI must not independently alter a score under any circumstance, per [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction).

## Official Results — [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity)

- **Why integrity is critical:** This is the single most trust-sensitive output of the platform; RSK-04 (incorrect medal tally) traces directly back to result integrity.
- **Required separation of duties:** Certification authority is distinct from scoring/validation authority (specific role **blocked pending [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain)**).
- **Required evidence:** Traceability to the exact source score-record versions used for certification.
- **Required auditability:** **Critical**.
- **Allowed correction pattern:** Certified results may only be corrected through versioned supersession triggered by a resolved protest or an equivalent documented exception (see [high-integrity-domain-rules.md](#protests-and-appeals) below) — never a direct edit.
- **Prohibited silent changes:** No certification or correction without an attributed, reasoned event.
- **Publication restrictions:** Certification and publication are distinct steps; a certified-but-unpublished result is not visible to the public (see [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary)).
- **Retention expectations:** Critical — permanent institutional record.
- **AI limitations:** AI must not independently certify a result, per [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction).

## Protests and Appeals — [BC-17](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity)

- **Why integrity is critical:** This is the platform's designated correction mechanism for high-integrity results; if it is not trustworthy, the entire results chain loses institutional credibility.
- **Required separation of duties:** The adjudicator is distinct from any party involved in producing the original result.
- **Required evidence:** Full evidentiary trail (filing, evidence submitted, decision rationale) retained.
- **Required auditability:** **Critical**.
- **Allowed correction pattern:** A resolved protest triggers a versioned correction in Official Results (BC-16) and, if medal-relevant, a recalculation in Medal Tally (BC-18) — never a direct edit to either.
- **Prohibited silent changes:** No protest outcome without a logged, reasoned decision.
- **Publication restrictions:** Protest outcome (not necessarily full case evidence) may be published; case evidence detail remains restricted.
- **Retention expectations:** Critical — evidentiary record.
- **AI limitations:** AI must not independently resolve a protest, per [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction).

## Medal Tally — [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived)

- **Why integrity is critical:** This is the most public-facing high-integrity output; RSK-04 names incorrect medal tally as a direct institutional-trust risk.
- **Required separation of duties:** The tally computation reads only from certified/published results (BC-16); it structurally cannot accept a manually entered medal outcome that bypasses result certification.
- **Required evidence:** Every tally snapshot traces to the specific certified result version(s) it derives from.
- **Required auditability:** **Critical**.
- **Allowed correction pattern:** Recalculation produces a new versioned snapshot; prior public snapshots are retained, not deleted, for historical/audit reference.
- **Prohibited silent changes:** No manual tally adjustment outside the derivation process.
- **Publication restrictions:** Tally publication follows the same controlled publication discipline as results.
- **Retention expectations:** Critical — permanent institutional record.
- **AI limitations:** AI must not independently award a medal, per [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction).

## Accreditation — [BC-19](bounded-context-catalog.md#bc-19--accreditation-high-integrity)

- **Why integrity is critical:** Accreditation is the security/authorization gate for physical access to the event; misissuance compromises safety and access control.
- **Required separation of duties:** Issuance authority is distinct from the participant's own submission; revocation authority may be broader (e.g., Security Committee escalation).
- **Required evidence:** Basis for issuance (eligibility clearance reference, role determination) retained.
- **Required auditability:** High.
- **Allowed correction pattern:** Reissue/reprint for lost/damaged credentials follows a logged replacement workflow, not a silent duplicate.
- **Prohibited silent changes:** No credential status change without a logged, reasoned event.
- **Publication restrictions:** Credential data is never public.
- **Retention expectations:** High.
- **AI limitations:** AI must not independently issue or revoke a credential, per [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction).

## Medical Information — [BC-21](bounded-context-catalog.md#bc-21--medical-operations-restricted)

- **Why integrity is critical:** This is the platform's most sensitive personal data category, compounded by the likelihood that most athletes are minors ([Phase 0.1 CON-09](../00-product/assumptions-constraints-risks.md#2-constraints)).
- **Required separation of duties:** Only Medical Team roles access raw medical records; all other contexts (notably Eligibility) receive only a minimal status flag via Anti-Corruption Layer (see [context-map.md](context-map.md)).
- **Required evidence:** Incident records retain full clinical detail within the Medical Operations context only.
- **Required auditability:** **Critical**, with access itself being audited (who viewed a medical record, not just who changed it).
- **Allowed correction pattern:** Amendments are logged with a visible correction trail; records are never deleted.
- **Prohibited silent changes:** No cross-context copying of raw medical data under any circumstance.
- **Publication restrictions:** Never public, never shared outside Medical Operations except via the defined ACL status flag.
- **Retention expectations:** Governed by applicable data-privacy law and DepEd policy for minors — **blocked pending [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling)**.
- **AI limitations:** AI must not make or suggest medical/clinical decisions; any AI assistance here is restricted to non-clinical operational summarization only, and only if approved data-access boundaries permit it (see [Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions)).

## Financial Records — [BC-26](bounded-context-catalog.md#bc-26--finance-operations)

- **Why integrity is critical:** Financial monitoring supports institutional accountability, even though PMMS is explicitly not a full accounting system.
- **Required separation of duties:** Budget allocation and expense recording should not be controlled by the same unchecked actor without committee-level review.
- **Required evidence:** Supporting documents for allocations/expenses retained (via BC-30).
- **Required auditability:** High.
- **Allowed correction pattern:** Corrections are logged adjustments, not deletions.
- **Prohibited silent changes:** No unattributed financial record edits.
- **Publication restrictions:** Never public.
- **Retention expectations:** High, per DepEd financial record-keeping norms (not defined here — requires confirmation).
- **AI limitations:** AI may assist with anomaly detection (advisory) only.

## Audit History — [BC-32](bounded-context-catalog.md#bc-32--audit-and-compliance-elevated-integrity)

- **Why integrity is critical:** This is the safeguard mechanism for every other high-integrity domain; if audit history itself can be altered, no other guarantee in this document is trustworthy.
- **Required separation of duties:** No context, including administrative roles, has the ability to delete or edit an existing audit event through normal operation.
- **Required evidence:** The audit event *is* the evidence.
- **Required auditability:** N/A — self-referential; instead, audit-log integrity verification (e.g., tamper-evidence) is the safeguard.
- **Allowed correction pattern:** Corrections to a business fact produce a *new* audit event referencing the old one; the old audit event is never modified or removed.
- **Prohibited silent changes:** Absolute — this is the one domain where "no silent mutation" has no exceptions.
- **Publication restrictions:** Not public; available to Auditors and DepEd Leadership per defined access.
- **Retention expectations:** Critical — likely the longest retention requirement of any data category in the platform.
- **AI limitations:** AI may assist in summarizing or searching audit history (advisory) but must never generate, alter, or suppress an audit event.
