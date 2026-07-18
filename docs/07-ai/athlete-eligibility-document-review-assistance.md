# PMMS Athlete Eligibility Document Review Assistance (UC-01)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../02-data/high-integrity-data-model.md, "Eligibility"](../02-data/high-integrity-data-model.md#eligibility-bc-09--critical) · [../03-security/medical-eligibility-finance-and-sensitive-data-controls.md, "Eligibility Data Governance"](../03-security/medical-eligibility-finance-and-sensitive-data-controls.md#eligibility-data-governance) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 3 (High-Risk Decision Support). This document defines AI-assisted document review for the Eligibility and Clearance domain (BC-09) — the highest-scrutiny AI use case in this package, given eligibility's Critical high-integrity status and its blocking dependency on [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority). **AI never approves or rejects eligibility, under any circumstance.**

---

## 1. Workflow

```text
Authorized Reviewer Selects Case
→ Authorized Documents Retrieved
→ Documents Classified
→ Approved Checklist and Rule Version Loaded
→ AI Extracts and Compares
→ Findings Generated
→ Source and Confidence Displayed
→ Reviewer Accepts, Edits, Rejects, or Requests Clarification
→ Official Decision Uses Normal Eligibility Workflow
```

The final two stages are absolute and non-negotiable: **the official decision always flows through the ordinary, unassisted eligibility-approval workflow already established in [../02-data/high-integrity-data-model.md, "Eligibility"](../02-data/high-integrity-data-model.md#eligibility-bc-09--critical)** — AI assistance ends at "Findings Generated," never extending into the approval action itself.

## 2. Potential Assistance

Identify missing required documents · extract document metadata · detect incomplete submissions · detect inconsistent names, dates, or identifiers · compare submitted evidence against an approved checklist · flag unreadable or potentially outdated documents · draft reviewer observations · suggest cases requiring manual review · link findings to verified policy or eligibility-rule sources (per [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md)).

## 3. Absolute Prohibitions

AI must not: approve eligibility · reject eligibility · override official eligibility rules · interpret missing rules as approval (an absent requirement is flagged as "requires policy clarification," never silently treated as satisfied) · invent requirements (every checklist item traces to a verified source, per working rule 14) · alter submitted documents · conceal uncertainty.

## 4. Input Restrictions

| Element | Direction |
|---|---|
| Supported file categories | Document formats already supported by [../03-security/file-object-storage-and-malware-security.md](../03-security/file-object-storage-and-malware-security.md)'s upload pipeline — no new file-handling path introduced |
| OCR readiness without implementation | A scanned/image-based document is a candidate for future OCR-assisted extraction — not implemented in this phase, restated from [retrieval-knowledge-and-semantic-search-architecture.md, Section 2](retrieval-knowledge-and-semantic-search-architecture.md#2-document-ingestion-conceptual) |
| Extraction confidence | Every extracted field (a name, a date, an ID number) carries its own confidence indicator — a low-confidence extraction is flagged for manual verification, never silently trusted |
| Checklist versioning | The eligibility checklist itself is a versioned source (per [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md)), and every AI comparison cites the specific checklist version used |
| Cross-document consistency | AI compares identifiers/names/dates across multiple documents within the same case, flagging inconsistencies for reviewer attention |
| Manual verification | Every AI finding requires human confirmation before the eligibility decision proceeds — restated absolutely |
| False-positive risk | An AI-flagged "inconsistency" that is actually benign (e.g., a legal name change) is an expected, tolerable outcome — the reviewer's judgment resolves it, not the AI's confidence score |

## 5. Authority Table

| Element | Value |
|---|---|
| Requesting user | Eligibility reviewer (per [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md)) |
| Required role/permission | The existing `eligibility-case.review` permission, per [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) |
| Required assignment | An active Eligibility Reviewer assignment scoped to the specific case's meet/sport |
| Scope | The specific case under review only — never a bulk, unscoped document sweep |
| Data classification | Restricted (eligibility evidence) |
| Permitted input | The specific case's submitted documents and the verified eligibility checklist/rule source |
| Prohibited input | Any other athlete's case, any medical/financial/guardian data not part of this specific case's evidence |
| Allowed output | Structured findings (missing/inconsistent/unreadable flags, extracted metadata) |
| Prohibited action | Approval, rejection, or any write to the case's official eligibility status |
| Required reviewer | The same Eligibility Reviewer who requested assistance, with SOD-01's reviewer/approver separation preserved absolutely — restated from [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) |
| Audit level | Full |
| Retention category | Mirrors the eligibility-case retention category itself, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) — a placeholder, not invented here |
| Feature-flag state | Off by default, pending pilot approval |

## 6. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably OCR-provider evaluation timing and the specific extraction-confidence threshold below which a field is auto-flagged for manual review.
