# PMMS Identity Resolution and Duplicate Management

**Status:** Draft Complete — Pending Data, Security, Privacy, Domain, and Engineering Validation
**Related:** [high-integrity-data-model.md](high-integrity-data-model.md) · [../01-architecture/domain-open-decisions.md, DD-01](../01-architecture/domain-open-decisions.md#dd-01--participant-registry-vs-separate-athletecoachofficial-registries) · [import-export-and-data-exchange.md](import-export-and-data-exchange.md)

This document defines the conceptual data model for identity matching and duplicate management within Participant Registry (BC-07). **No matching algorithm or AI model is selected here.**

---

## 1. Matching Factors (Conceptual)

Official identifier where available, name, birth date, organization, school, sex where lawfully and operationally relevant, guardian reference, historical participation, source system ID, contact details, document evidence.

**No specific weighting or algorithm is defined here** — this is a conceptual factor list a future matching process (rule-based, AI-assisted, or hybrid) would draw from, not an implemented scoring formula.

## 2. Match and Duplicate States

| State | Meaning |
|---|---|
| **Exact match** | All high-confidence factors agree (e.g., official identifier match) |
| **Probable match** | Strong factor agreement (name + birth date + school), short of certainty |
| **Possible duplicate** | Partial factor agreement warranting human review |
| **Confirmed duplicate** | A human reviewer has confirmed two records represent the same person |
| **Merge candidate** | A confirmed duplicate awaiting the merge action |
| **Rejected match** | A human reviewer has confirmed two records are genuinely different people despite factor similarity |
| **Identity conflict** | Records disagree on a factor that should be stable (e.g., two different birth dates for what might be the same person) — requires investigation, not automatic resolution either way |
| **Manual review** | The default outcome for anything short of Exact match — a human decides |
| **Merge** | The action combining two records into one canonical record |
| **Unmerge** | The action reversing a prior merge decision found to be incorrect |
| **Canonical record** | The surviving `Participant` row after a merge |
| **Alias** | A retained reference to a merged-away record's identifiers, so historical references (e.g., old meet participation) still resolve to the canonical record |
| **Source reference** | Which system/process originated a given record or match suggestion |
| **Confidence score** | Where AI-assisted matching is used, a retained score explaining *why* a match was suggested |
| **Audit trail** | Every state transition above is a row in an append-only history table (per [temporal-history-and-versioning-model.md, Section 3](temporal-history-and-versioning-model.md#3-immutable-history-and-append-only-records)) |

## 3. Persistence Model

- A `Participant` row is never deleted as part of a merge — the non-canonical row is marked `merged_into` (referencing the canonical row's public ID) and retained as an **alias**, so any historical foreign key still resolves correctly.
- A merge decision is its own record: which two (or more) rows were merged, who decided, when, why, and what the resulting canonical values were for any conflicting fields.
- An **unmerge** does not delete the merge decision record — it creates a new decision record reversing it, preserving the full history of "we thought these were the same person, then determined they weren't."
- Every match suggestion (whether AI-generated or rule-based) that reaches "possible duplicate" or higher is retained, even if ultimately rejected, so the platform's duplicate-detection accuracy can be reviewed over time.

## 4. AI Boundary (Restated)

**AI may suggest duplicates but must not autonomously merge high-impact identity records.** This is restated unchanged from [../01-architecture/high-integrity-domain-rules.md, "Participant Identity"](../01-architecture/high-integrity-domain-rules.md#participant-identity--bc-07) and [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 34](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#34-ai-service-integration-boundary) — Phase 0.5 does not weaken it. At the persistence layer, this means: an AI-suggested match is written only to a "possible duplicate" / "merge candidate" state with a confidence score, never directly to "confirmed duplicate" or executed as an actual merge without an intervening human decision recorded as its own attributed action.

## 5. Relationship to Import

Bulk imports (per [import-export-and-data-exchange.md](import-export-and-data-exchange.md)) are a primary source of duplicate-candidate generation — an imported batch of athletes is checked against existing `Participant` records using the same matching-factor model before being committed, surfacing possible duplicates for review rather than blindly creating new rows for every imported name.

## 6. Open Questions

- Specific matching algorithm/confidence-scoring approach — implementation-phase (Phase 0.6+) decision, informed by real duplicate-rate data once available.
- Whether "sex" is a matching factor at all, and if so, its lawful/operational basis — requires Data Privacy and Legal Stakeholder input (per [information-classification-and-privacy.md](information-classification-and-privacy.md)).
- Single shared Participant Registry vs. separate per-role registries — this is the same open question as [../01-architecture/domain-open-decisions.md, DD-01](../01-architecture/domain-open-decisions.md#dd-01--participant-registry-vs-separate-athletecoachofficial-registries), directly determining this document's data model; the Phase 0.2 recommended direction (single shared registry) is carried forward here without re-litigation.

Tracked in [data-open-decisions.md](data-open-decisions.md).
