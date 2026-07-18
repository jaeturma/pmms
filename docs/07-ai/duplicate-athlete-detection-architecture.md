# PMMS Duplicate Athlete Detection Architecture (UC-02)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../02-data/identity-resolution-and-duplicate-management.md](../02-data/identity-resolution-and-duplicate-management.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 2 (Moderate-Risk Recommendation). This document extends [../02-data/identity-resolution-and-duplicate-management.md](../02-data/identity-resolution-and-duplicate-management.md)'s Phase 0.5 identity-resolution architecture with AI-specific detection detail. **AI never merges, deletes, or declares two identities identical** — restated absolutely, directly reaffirming Phase 0.5's original AI boundary.

---

## 1. Potential Assistance

Suggest probable duplicate participant records · compare names, birth dates, school, delegation, identifiers, and historical participation · produce similarity indicators · explain matched attributes · identify conflicting attributes · recommend manual identity review.

## 2. Absolute Prohibitions

AI must not: automatically merge records · automatically delete participant records · declare two people identical without human validation · expose protected identity data beyond the reviewer's authority.

## 3. Candidate Generation and Matching

| Element | Direction |
|---|---|
| Candidate generation | AI proposes a bounded, ranked list of probable-duplicate candidates — never an unbounded "everyone who might match" sweep |
| Deterministic matching | Preferred first pass — exact identifier matches (a shared, verified external ID) are deterministic, not AI-inferred |
| Normalized name comparison | Handles common variation (nicknames, transliteration, ordering) — a candidate technique, not a guaranteed-accurate one |
| Birth-date comparison | A strong signal when combined with name similarity |
| School and delegation comparison | Contextual signal — the same name appearing in the same school across meets is a stronger duplicate signal than the same name in different delegations |
| Historical participation | Cross-meet participation patterns inform confidence, per [../02-data/logical-data-architecture.md, Section 4](../02-data/logical-data-architecture.md#4-multi-meet-and-multi-organization-readiness-logical-principles)'s reusable-master-data model |
| External identifiers | Where available and verified, the strongest deterministic signal |
| Similarity evidence | Every candidate match displays exactly which attributes matched and how closely |
| Conflict evidence | Attributes that *don't* match are shown alongside matching ones — a complete picture, not a cherry-picked case for the match |
| Match confidence category | Per [ai-explainability-confidence-and-user-review.md, Section 2](ai-explainability-confidence-and-user-review.md#2-confidence-and-uncertainty) — Strong/Moderate/Limited/Insufficient/Conflicting evidence |

## 4. Reviewer Decision, Merge, and Unmerge

The reviewer's decision (confirm duplicate, reject as distinct individuals, request more evidence) is a human action using the existing identity-resolution workflow — restated absolutely from [../02-data/identity-resolution-and-duplicate-management.md, "Persistence Model"](../02-data/identity-resolution-and-duplicate-management.md#3-persistence-model): a merge never deletes the losing record, it becomes a retained alias/source-reference. **Merge and unmerge occur entirely outside AI's reach** — AI's role ends at producing the ranked candidate list and evidence; the merge/unmerge mechanism itself is unchanged from Phase 0.5's design.

## 5. Bias and Naming-Variation Risks

A named risk: name-matching algorithms can systematically perform worse for names outside the algorithm's training distribution (e.g., certain naming conventions, diacritics, or multi-part surnames common in Philippine names) — a candidate concern for [ai-evaluation-testing-and-quality-assurance.md, "Bias Readiness"](ai-evaluation-testing-and-quality-assurance.md#3-retrieval-hallucination-and-prompt-injection-tests) evaluation, flagged here as a specific, non-generic risk rather than a boilerplate bias disclaimer.

## 6. Authority Table

| Element | Value |
|---|---|
| Requesting user | Secretariat / Participant Registry steward |
| Required role/permission | An identity-resolution-review permission, scoped to the Participant Registry (BC-07) |
| Scope | Organization/meet-scoped candidate generation, never platform-wide without explicit elevated scope |
| Data classification | Confidential–Restricted (identity data) |
| Permitted input | Participant identity fields already visible to the requesting role |
| Prohibited input | Medical, financial, or eligibility-evidence detail not needed for identity comparison |
| Allowed output | Ranked candidate list with similarity/conflict evidence |
| Prohibited action | Merge, unmerge, or delete |
| Required reviewer | Identity-resolution reviewer, distinct from the case's own data-entry originator where SoD applies |
| Audit level | Full |
| Feature-flag state | Off by default, pending pilot approval |

## 7. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably the specific matching-algorithm evaluation methodology for Philippine-naming-convention accuracy.
