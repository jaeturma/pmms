# PMMS Helpdesk and Committee Knowledge Assistants (UC-09, UC-10, UC-12)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md) · [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 1 (Low-Risk Assistance). Covers UC-09 (Helpdesk Assistant), UC-10 (Committee Knowledge Assistant), and UC-12 (Policy and Rulebook Search) — PMMS's three knowledge-assistance capabilities, all built on the RAG lifecycle in [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md).

---

## Helpdesk Assistant (UC-09)

### Potential Assistance

Answer common support questions · explain system workflows · guide users through role-appropriate tasks · suggest troubleshooting steps · link to help documents · identify when escalation is required · draft support-ticket summaries.

### The Assistant Must Not

Expose another user's data · reset credentials without approved workflow · grant permissions · perform support impersonation · provide hidden administrative instructions to unauthorized users.

## Committee Knowledge Assistant (UC-10)

### Potential Assistance

Answer committee-specific operational questions · search committee manuals · explain approved workflows · summarize committee responsibilities · retrieve templates · identify required reports · identify handoff dependencies · recommend relevant runbooks.

Covers all twelve committees per [../06-design/committee-logistics-medical-finance-and-support-experience.md, Section 1](../06-design/committee-logistics-medical-finance-and-support-experience.md#1-committee-experience-architecture): Secretariat · tournament management · technical officials · tally · medical · food · transport · billeting · finance · security · ICT · media.

**Answers cite approved knowledge sources** — restated absolutely per [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md). **Retrieval remains committee- and role-aware** — a Food committee member's question never surfaces Finance-committee-restricted procedure detail, restated per working rule 21.

## Policy and Rulebook Search (UC-12)

### Potential Assistance

Search verified DepEd documents · search official sports rulebooks · search approved meet guidelines · search committee procedures · retrieve relevant passages · explain policy version and effectivity · identify superseded references · compare rule versions · answer questions with citations.

**The system never treats an unverified document or generated content as official policy** — restated absolutely, per [policy-rulebook-and-source-governance.md, Section 4](policy-rulebook-and-source-governance.md#4-unverified-source-exclusion).

### Output Requirements

Exact citation · source version · effectivity · verified status · superseded notice · related documents · direct-extract limit · answer summary · unresolved-ambiguity disclosure · link or source identifier · **no fabricated citation** — restated absolutely.

## Authority Table (All Three Use Cases)

| Element | Value |
|---|---|
| Requesting user | Any authenticated user (UC-09, role-aware); committee staff (UC-10, committee-scoped); any authorized user (UC-12) |
| Scope | Role-appropriate help content (UC-09); the requester's own committee (UC-10); the requester's own authorized document access (UC-12) |
| Data classification | Internal (help/committee content); Public–Internal (policy/rulebook content, depending on source) |
| Permitted input | The user's question plus retrieval against approved, role-appropriate knowledge sources |
| Prohibited input | Another user's account data, credentials, or permission state |
| Allowed output | Grounded, cited answers; help-document links; support-ticket draft summaries |
| Prohibited action | Credential reset, permission grant, impersonation, or any account-modifying action |
| Required reviewer | None required for routine Tier 1 knowledge queries; Support Tier 2 for escalated helpdesk cases |
| Audit level | Standard |
| Feature-flag state | Off by default, pending pilot approval — Tier 1 use cases are natural candidates for the earliest pilot rollout given their lower risk |

## Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably which of these three Tier 1 use cases is prioritized for the first implementation, given their shared low-risk profile and RAG-infrastructure dependency.
