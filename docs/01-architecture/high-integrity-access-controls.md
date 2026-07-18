# PMMS High-Integrity Access Controls

**Status:** Draft Complete — Pending Security, Domain, and Stakeholder Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [high-integrity-domain-rules.md](high-integrity-domain-rules.md) · [separation-of-duties-matrix.md](separation-of-duties-matrix.md) · [permission-catalog.md](permission-catalog.md)

This document translates the architectural safeguards in [high-integrity-domain-rules.md](high-integrity-domain-rules.md) (Phase 0.2) into concrete **access control** requirements: which roles, scopes, assignments, and state conditions gate each sensitive operation. It extends Phase 0.2's domain-level safeguards with Phase 0.3's identity/role/scope apparatus.

---

## Athlete Identity

- **Sensitive operations:** Creating/correcting a Participant identity record; merging duplicate records.
- **Required roles:** Secretariat (creation); a designated identity steward (correction/merge — TBD).
- **Required scopes:** Meet or Organization (for cross-meet identity corrections).
- **Required assignments:** Committee assignment (Secretariat) for creation; elevated review authority for merges.
- **State conditions:** A merge candidate must be flagged (possibly AI-advisory) before a human reviewer can act — never auto-merged.
- **Separation-of-duties rules:** The proposer of a merge (if AI-assisted) is not the sole confirming authority.
- **Approval level:** Review (creation), Approval (merge/correction).
- **Audit level:** High for creation, Critical for merge/correction.
- **Correction/override control:** Versioned correction, per [identity-model.md, Section 4](identity-model.md#4-duplicate-identities).
- **Offline limitation:** Identity creation/correction is never finalized offline.
- **AI limitation:** Duplicate-match suggestions only, advisory, never auto-merge.
- **Open questions:** Identity steward role — see [role-catalog.md, ROLE-06](role-catalog.md#role-06--organization-administrator) territory or a new dedicated role; DD-01 in [domain-open-decisions.md](domain-open-decisions.md).

## Registration

- **Sensitive operations:** Submit, review, return, withdraw, lock.
- **Required roles:** ROLE-17 (Delegation Registrar), ROLE-18 (Registration Reviewer), ROLE-13 (Secretariat Head, for lock).
- **Required scopes:** Meet + Delegation.
- **Required assignments:** Committee (Secretariat) or Delegation assignment.
- **State conditions:** Cannot submit an incomplete draft; cannot withdraw a locked registration without an exception process.
- **Separation-of-duties rules:** SOD-01 — submitter/reviewer distinct from the eventual eligibility approver.
- **Approval level:** Operational (submit), Review (review/return), Approval (lock).
- **Audit level:** Standard, Elevated at lock.
- **Correction/override control:** Pre-lock changes unrestricted; post-lock changes require a logged exception.
- **Offline limitation:** Low relevance — pre-meet administrative step, not typically offline.
- **AI limitation:** Missing-requirement detection only, advisory.
- **Open questions:** Guardian consent mechanism — [Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access).

## Eligibility

- **Sensitive operations:** Review, record finding, approve, reject, reopen, view restricted evidence.
- **Required roles:** ROLE-19 (Eligibility Reviewer), ROLE-20 (Eligibility Approver).
- **Required scopes:** Meet + assigned Delegation subset.
- **Required assignments:** Committee assignment (Eligibility), scoped to specific delegations.
- **State conditions:** Approval requires prior review and complete evidence; reopen requires an elevated, reasoned justification.
- **Separation-of-duties rules:** **SOD-01, critical** — reviewer of a specific case can never approve that same case.
- **Approval level:** Review (ROLE-19), Certification (ROLE-20).
- **Audit level:** **Critical** — actor, timestamp, reason mandatory for every decision.
- **Correction/override control:** An approved case may only be reversed through a documented, audited reversal — never a silent status flip.
- **Offline limitation:** **Never finalized offline**, under any circumstance, absent a future specifically-authorized policy exception (none currently exists).
- **AI limitation:** Document-completeness checks only; **AI must never approve or reject eligibility**.
- **Open questions:** **Blocking** — [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).

## Competition Entries

- **Sensitive operations:** Submit, review, substitute, withdraw, lock.
- **Required roles:** ROLE-50 (Coach), ROLE-24 (Tournament Manager, for lock).
- **Required scopes:** Meet + Delegation + Sport.
- **Required assignments:** Delegation assignment (submit), Sport assignment (lock).
- **State conditions:** Submission requires the athlete to be in an `eligibility-case.approve`d state — a structural gate, not a manual check.
- **Separation-of-duties rules:** None distinct beyond the eligibility gate itself.
- **Approval level:** Operational (submit), Approval (lock).
- **Audit level:** Standard, Elevated at lock.
- **Correction/override control:** Pre-lock unrestricted; post-lock requires a logged exception process.
- **Offline limitation:** Low relevance.
- **AI limitation:** Entry-conflict/limit detection only, advisory.
- **Open questions:** Multi-sport participation (DD-11 in [domain-open-decisions.md](domain-open-decisions.md)).

## Tournament Progression

- **Sensitive operations:** Generate draw, approve draw, schedule/reschedule, advance competitor, override progression.
- **Required roles:** ROLE-24 (Tournament Manager), ROLE-26 (Technical Delegate, for override).
- **Required scopes:** Meet + Sport.
- **Required assignments:** Sport assignment.
- **State conditions:** Draw generation requires locked entries; advancement must derive from a certified result, never a manual entry.
- **Separation-of-duties rules:** Progression override (Critical risk) should require Technical Delegate authority distinct from the Tournament Manager who generated the draw, per SOD-03b.
- **Approval level:** Approval (draw, schedule), Critical (override).
- **Audit level:** High, Critical for override.
- **Correction/override control:** Pre-publication regeneration unrestricted; post-publication changes are logged exceptions.
- **Offline limitation:** Draw generation/approval never finalized offline; venue-level read access to the published structure is offline-tolerant.
- **AI limitation:** Seeding/scheduling recommendations only, advisory; human confirms before publication.
- **Open questions:** DD-07, DD-12, DD-13 in [domain-open-decisions.md](domain-open-decisions.md).

## Scoring

- **Sensitive operations:** Submit, correct, validate.
- **Required roles:** ROLE-27 (Technical Official, Scorer/Timer function), ROLE-28 (Result Validator).
- **Required scopes:** Meet + Sport + Event/Match.
- **Required assignments:** Event/Match assignment with function metadata; validator assignment distinct from entering official's assignment.
- **State conditions:** Correction only permitted before validation; validation requires prior capture.
- **Separation-of-duties rules:** **SOD-02, critical** — validator ≠ entering official, enforced at the specific score-record level, not merely by role.
- **Approval level:** Operational (submit/correct), Review (validate).
- **Audit level:** **Critical**.
- **Correction/override control:** Versioned revision with retained prior value and stated reason — never destructive overwrite.
- **Offline limitation:** Capture is offline-tolerant (primary offline use case); **validation is never finalized offline**.
- **AI limitation:** AI must never alter a score under any circumstance.
- **Open questions:** Sport-specific scoring model — [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source).

## Official Results

- **Sensitive operations:** Certify, supersede, publish, unpublish.
- **Required roles:** ROLE-29 (Result Certifier), ROLE-33 (Result Publisher).
- **Required scopes:** Meet + Sport + Event.
- **Required assignments:** Event assignment for certification; a distinct publication authority per [domain-open-decisions.md, DD-17](domain-open-decisions.md#dd-17--public-publication-approval-chain).
- **State conditions:** Certification requires all score inputs validated; publication requires no active protest hold.
- **Separation-of-duties rules:** **SOD-02, SOD-03** — certifier ≠ validator; certifier who certified a result should recuse from resolving a protest against it.
- **Approval level:** Certification (certify), Publication (publish).
- **Audit level:** **Critical**.
- **Correction/override control:** Versioned supersession only, triggered by a resolved protest or a narrowly defined pre-publication clerical-correction path (per [domain-open-decisions.md, DD-15](domain-open-decisions.md#dd-15--result-versioning-authority)) — never a direct edit.
- **Offline limitation:** **Never finalized offline.**
- **AI limitation:** AI must never certify a result.
- **Open questions:** **Blocking** — [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain).

## Protests

- **Sensitive operations:** File, review, place on hold, resolve, release hold, appeal.
- **Required roles:** ROLE-49/50 (filer), ROLE-26 (Technical Delegate, review/resolve).
- **Required scopes:** Meet + Sport.
- **Required assignments:** Sport assignment for the Technical Delegate.
- **State conditions:** Filing must be within a defined deadline (source: DepEd/sport policy, not invented); resolution requires prior review.
- **Separation-of-duties rules:** **SOD-03, critical** — adjudicator must recuse if they certified the disputed result.
- **Approval level:** Review, then Certification (resolution).
- **Audit level:** **Critical**, full evidentiary trail.
- **Correction/override control:** A resolved-and-upheld protest triggers a versioned Official Result correction (WF-12 supersession) — never a direct edit.
- **Offline limitation:** Low relevance; adjudication is never finalized offline.
- **AI limitation:** AI must never resolve a protest.
- **Open questions:** **Blocking** — [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).

## Medal Tally

- **Sensitive operations:** Recalculate, review, certify, publish, correct.
- **Required roles:** ROLE-30/31/32 (Tally Encoder/Reviewer/Certifier), ROLE-33 (Publisher).
- **Required scopes:** Meet.
- **Required assignments:** Committee assignment (Tally).
- **State conditions:** Computation may only read certified/published Official Results, never raw scores or manual entry — a structural gate.
- **Separation-of-duties rules:** **SOD-04, critical** — encoder ≠ certifier for the same tally snapshot.
- **Approval level:** Certification (certify), Publication (publish, with elevated confirmation for post-publication corrections per [domain-open-decisions.md, DD-16](domain-open-decisions.md#dd-16--medal-tally-correction-authority)).
- **Audit level:** **Critical**.
- **Correction/override control:** Recalculation produces a new versioned snapshot; prior public snapshots retained, never deleted.
- **Offline limitation:** **Never finalized offline.**
- **AI limitation:** AI must never award a medal.
- **Open questions:** **Blocking** — [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules), OD-13.

## Accreditation

- **Sensitive operations:** Approve, issue, print, replace, revoke.
- **Required roles:** ROLE-47 (Accreditation Officer).
- **Required scopes:** Meet + Committee (Accreditation).
- **Required assignments:** Committee assignment.
- **State conditions:** Issuance requires resolved identity and, where required, cleared eligibility.
- **Separation-of-duties rules:** **SOD-05** — issuer/revoker ≠ the role authorized to override an access-scan denial.
- **Approval level:** Approval–Certification.
- **Audit level:** High.
- **Correction/override control:** Reissue/reprint follows a logged replacement workflow, never a silent duplicate.
- **Offline limitation:** Issuance is low-offline-relevance; the credential-validity cache it publishes downstream is critical-offline (see [offline-authorization-model.md](offline-authorization-model.md)).
- **AI limitation:** AI must never issue or revoke a credential.
- **Open questions:** [Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage); DD-05 in [domain-open-decisions.md](domain-open-decisions.md).

## Medical Records

- **Sensitive operations:** Record, update, view-sensitive, report incident, issue fitness status, export.
- **Required roles:** ROLE-38 (Medical Officer), ROLE-39 (Medical Staff).
- **Required scopes:** Meet + Venue + Shift; `view-sensitive` further restricted to own committee/need-to-know.
- **Required assignments:** Committee (Medical) + Venue/Shift assignment.
- **State conditions:** Fitness status issuance requires a recorded encounter.
- **Separation-of-duties rules:** **SOD-09, critical** — Medical role must never combine with Public Information Publisher/Media Coordinator.
- **Approval level:** Approval (fitness status, export).
- **Audit level:** **Critical**, including access-to-view audit (who viewed, not just who changed).
- **Correction/override control:** Amendments logged with a visible correction trail; records never deleted.
- **Offline limitation:** High offline relevance for capture (must function in emergencies); export/high-sensitivity views require connectivity for full audit capture where feasible.
- **AI limitation:** AI must not make or suggest clinical decisions; any non-clinical operational summarization requires an approved data-access boundary (DD-26 in [domain-open-decisions.md](domain-open-decisions.md)).
- **Open questions:** **Blocking** — [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).

## Finance

- **Sensitive operations:** Create/approve budget allocation, record/review expense, view restricted supporting documents.
- **Required roles:** ROLE-45 (Finance Coordinator, staff and head positions).
- **Required scopes:** Meet + Committee (Finance).
- **Required assignments:** Committee assignment.
- **State conditions:** Approval requires a prior recorded/created state.
- **Separation-of-duties rules:** **SOD-06** — encoder ≠ approver.
- **Approval level:** Approval.
- **Audit level:** High.
- **Correction/override control:** Corrections are logged adjustments, never deletions.
- **Offline limitation:** Low relevance.
- **AI limitation:** Anomaly-detection assistance only, advisory.
- **Open questions:** None blocking.

## Audit History

- **Sensitive operations:** View, export.
- **Required roles:** ROLE-05 (Audit Viewer), ROLE-15 (Meet Auditor).
- **Required scopes:** Platform or Meet.
- **Required assignments:** Standing platform role or Meet assignment.
- **State conditions:** N/A — read-only domain by design.
- **Separation-of-duties rules:** **SOD-08** — must not be the same individual as any administrator whose actions are being audited, wherever avoidable.
- **Approval level:** Review.
- **Audit level:** N/A self-referentially — instead, tamper-evidence/integrity-verification is the safeguard (per [domain-open-decisions.md, DD-24](domain-open-decisions.md#dd-24--audit-data-ownership-when-a-context-is-itself-under-audit)).
- **Correction/override control:** Absolute — no correction; a mistaken audit entry is superseded by a new entry referencing it, never altered.
- **Offline limitation:** Local buffering with guaranteed eventual sync for offline-capable contexts feeding the audit log; the audit log itself is never the offline-authoritative source.
- **AI limitation:** AI may assist in summarizing/searching audit history (advisory) but must never generate, alter, or suppress an audit event.
- **Open questions:** DD-24.

---

## Cross-Domain Summary

| Domain | Blocking Phase 0.1 Decision | Governing SOD Entry |
|---|---|---|
| Eligibility | [OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) | SOD-01 |
| Scoring | [OD-10](../00-product/open-decisions.md#od-10--sports-rule-source) (rule content) | SOD-02 |
| Official Results | [OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain) | SOD-02, SOD-03 |
| Protests | [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) | SOD-03, SOD-03b |
| Medal Tally | [OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules), OD-13 | SOD-04 |
| Accreditation | [OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage) | SOD-05 |
| Medical | [OD-15](../00-product/open-decisions.md#od-15--medical-data-handling) | SOD-09 |

These blocking dependencies mean Phase 0.4 (Application, Integration, and Runtime Architecture) can design the *mechanism* for each control above, but cannot finalize the *specific authorized role identity* for the blocked domains until DepEd resolves the referenced Phase 0.1 decisions.
