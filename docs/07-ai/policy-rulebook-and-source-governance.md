# PMMS Policy, Rulebook, and Source Governance

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md) · [retrieval-knowledge-and-semantic-search-architecture.md](retrieval-knowledge-and-semantic-search-architecture.md)

This document governs which sources PMMS's AI capabilities may draw on, and how citations are required to work. **No official policy, sports rule, or rulebook content is invented here** — restated absolutely per working rule 14, directly extending [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md)'s identical discipline into AI-specific governance.

---

## 1. Verified Sources

### Candidate Knowledge Source Categories

Verified DepEd issuances · approved sports rulebooks · meet memoranda · committee manuals · standard operating procedures · help documentation · system user guides · approved templates · incident runbooks · official FAQs · domain glossary (per [../01-architecture/domain-glossary.md](../01-architecture/domain-glossary.md)).

### Every Source Requires

Owner · title · issuing authority · source location · version · effectivity · verification status · classification · supersession status · ingestion status.

**Sources are drawn from, and cross-referenced against, [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md)** — the 13-entry registry (POL-01 through POL-13) already established in Phase 0.6, currently all unverified placeholders. An AI knowledge source is never ingested until its corresponding policy-source-registry entry (or a new one, if the source doesn't map to an existing POL-XX entry) is verified.

## 2. Citation Standards

Every policy/rulebook answer requires: exact citation · source version · effectivity · verified status · superseded notice (where applicable) · related documents · a direct-extract limit (a citation quotes a bounded excerpt, never reproduces an entire source document) · an answer summary · disclosure of unresolved ambiguity · a link or source identifier · **no fabricated citation** — restated absolutely, the single most important rule in this section.

### Conflicting Sources

**When sources conflict, the system surfaces the conflict rather than choosing silently** — restated absolutely per this phase's own governing instruction. If two approved sources disagree (e.g., an older memorandum not yet formally superseded contradicts a newer one), the AI response presents both, flags the apparent conflict, and defers to the named policy owner for resolution — it never silently picks one.

## 3. Knowledge Freshness

| Element | Direction |
|---|---|
| Source review | Periodic, per a not-yet-fixed cadence (see [ai-open-decisions.md](ai-open-decisions.md)) |
| Scheduled revalidation | A knowledge-source owner confirms a source remains current |
| Superseded-source handling | A superseded source is retained for historical citation but flagged, never silently removed |
| Expired-source handling | A source past its effectivity window is excluded from new retrieval by default |
| Index rebuild | Triggered when a source is added, updated, or withdrawn |
| Source withdrawal | A formal, owner-initiated action, distinct from expiry |
| Stale-answer warning | A response grounded in a source nearing its revalidation date carries a visible freshness caveat |
| Last-verified time | Recorded per source, displayed alongside any citation drawn from it |
| Knowledge-owner responsibility | Every source has a named owning role accountable for its currency — restated from the "no ownerless capability" discipline established throughout this project |

## 4. Unverified-Source Exclusion

**The system never treats an unverified document or AI-generated content as official policy** — restated absolutely per this phase's own governing instruction. A document uploaded by any user, however well-intentioned, does not enter the approved-knowledge pipeline without passing through the source-approval step in [retrieval-knowledge-and-semantic-search-architecture.md, Section 1](retrieval-knowledge-and-semantic-search-architecture.md#1-rag-lifecycle).

## 5. Review Process

A new or updated knowledge source is reviewed by its owning role before ingestion — confirming the source's authenticity, current effectivity, and correct classification. This restates and does not weaken [../03-security/policy-source-registry.md, "Rules"](../03-security/policy-source-registry.md#rules)'s existing verification discipline.

## 6. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably the specific source-revalidation cadence and who is designated as knowledge-source owner per category (DepEd issuances, sports rulebooks, committee manuals).
