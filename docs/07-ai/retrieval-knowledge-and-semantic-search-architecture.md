# PMMS Retrieval, Knowledge, and Semantic Search Architecture

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md) · [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes)

This document defines the retrieval-augmented-generation (RAG) lifecycle and knowledge-source architecture. **No embedding is generated and no vector database is created here** — restated absolutely per working rule 10.

---

## 1. RAG Lifecycle

```text
Source Approval
→ Ingestion
→ Classification
→ Parsing
→ Chunking
→ Metadata
→ Indexing
→ Retrieval Authorization
→ Query
→ Reranking
→ Context Assembly
→ Generation
→ Citation
→ Human Review
```

### Stage Detail

| Stage | Requirement |
|---|---|
| Source approval | Only a verified, approved source (per [policy-rulebook-and-source-governance.md, Section 1](policy-rulebook-and-source-governance.md#1-verified-sources)) enters the pipeline — restated absolutely per working rule 14's spirit, no unverified document is ever ingested as if authoritative |
| Ingestion | Document-ingestion mechanics, per Section 2 below |
| Classification | Every ingested document receives a classification tier before indexing, per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md) |
| Parsing | Structural extraction from the source document format |
| Chunking | Per Section 3 below |
| Metadata | Source, version, effectivity, classification, and owner attached to every chunk — never a bare text fragment with no provenance |
| Indexing | The staged, evidence-driven approach already established in [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes) — not committed to a specific vector-database product |
| Retrieval authorization | A query is scoped to what the requesting user is authorized to see **before** retrieval executes, restated per working rule 21 |
| Query | The user's (or system's) actual information need |
| Reranking | A candidate refinement step improving retrieval relevance — evaluated, not committed to a specific mechanism |
| Context assembly | Retrieved, authorized chunks are assembled into the "retrieved evidence" prompt layer, per [prompt-context-and-structured-output-architecture.md, Section 1](prompt-context-and-structured-output-architecture.md#1-prompt-layers) |
| Generation | The model produces a grounded response using only the assembled context |
| Citation | Every generated claim traces back to a specific source chunk, per Section 4 below |
| Human review | Per [human-in-the-loop-and-authority-model.md](human-in-the-loop-and-authority-model.md) |

## 2. Document Ingestion (Conceptual)

A document enters the RAG pipeline only after: an approved source owner confirms it (per [policy-rulebook-and-source-governance.md](policy-rulebook-and-source-governance.md)) · its classification is assigned · it passes the same malware-scanning discipline as any other PMMS file upload, per [../03-security/file-object-storage-and-malware-security.md, Section 3](../03-security/file-object-storage-and-malware-security.md#3-malware-scanning-architecture). **OCR readiness is acknowledged, not implemented** — a scanned/image-based policy document is a candidate for future OCR-assisted ingestion, evaluated once real document formats are known.

## 3. Chunking and Indexing Concepts

Chunking divides a source document into retrievable units small enough for relevant, precise retrieval but large enough to preserve meaning — the specific chunk-size/overlap strategy is an implementation-phase tuning decision, not fixed here. Indexing follows PMMS's platform-wide staged-search-adoption principle: begin with the simplest approach that satisfies real need (keyword/full-text search against MySQL, consistent with [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes)'s "MySQL-backed first" direction), introduce semantic/vector retrieval only once demonstrated necessary.

## 4. Source Provenance and Citation Requirements

Every retrieved chunk, and every generated claim grounded in it, carries: source document title · issuing authority · version · effectivity · classification · a reference sufficiently specific for a human to locate the original passage. **No fabricated citation** — restated absolutely per [policy-rulebook-and-source-governance.md, Section 2](policy-rulebook-and-source-governance.md#2-citation-standards); if the model cannot ground a claim in retrieved evidence, it declines rather than inventing a plausible-sounding source.

## 5. Semantic Search Versus Keyword Search Versus Hybrid Retrieval

| Approach | When Appropriate |
|---|---|
| Keyword/full-text search | The default starting point — sufficient for exact-term policy lookups and structured queries |
| Semantic (vector) search | A candidate enhancement for natural-language, intent-based queries where keyword matching underperforms — not committed to in this phase |
| Hybrid retrieval | Combining both — a candidate future refinement once real usage data demonstrates keyword search's specific limitations |

## 6. Knowledge Freshness

Source review · scheduled revalidation · superseded-source handling · expired-source handling · index rebuild · source withdrawal · stale-answer warning · last-verified time · knowledge-owner responsibility — every one of these is a required operational process once the RAG pipeline is implemented, restated and cross-referenced from [policy-rulebook-and-source-governance.md, Section 3](policy-rulebook-and-source-governance.md#3-knowledge-freshness).

## 7. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably vector-database product selection (deferred pending demonstrated semantic-search need) and the specific chunking strategy for DepEd issuances versus sports rulebooks, which may have different structural characteristics.
