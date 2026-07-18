# PMMS AI Explainability, Confidence, and User Review

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../06-design/ai-assisted-experience-architecture.md](../06-design/ai-assisted-experience-architecture.md) · [human-in-the-loop-and-authority-model.md](human-in-the-loop-and-authority-model.md)

This document defines explainability, confidence expression, review UX requirements, and content-labeling standards — extending [../06-design/ai-assisted-experience-architecture.md](../06-design/ai-assisted-experience-architecture.md) with AI-architecture-side detail. **No UI component is created here.**

---

## 1. Explainability

Every AI output explains: what was detected or recommended · which evidence was used · which rules or sources were referenced · which assumptions were made · what uncertainty exists · what data was unavailable · why human review is required.

**Do not display invented mathematical confidence when the model does not provide a validated probability** — restated absolutely; a confidence figure is only shown if it reflects something genuinely measured, never a plausible-sounding number added for the appearance of rigor.

## 2. Confidence and Uncertainty

### Categories

Strong evidence · moderate evidence · limited evidence · insufficient evidence · conflicting evidence.

**These categories require evaluation and must not imply statistical certainty unless validated** — restated absolutely per this phase's own governing instruction. A category label is a qualitative signal to the human reviewer, calibrated against real evaluation data (per [ai-evaluation-testing-and-quality-assurance.md](ai-evaluation-testing-and-quality-assurance.md)), never an unvalidated marketing-style confidence claim.

### Declining When Evidence Is Insufficient

**AI must be able to decline when evidence is insufficient** — restated absolutely per working rule 27. A capability that cannot ground a finding in adequate evidence returns "Insufficient evidence" rather than a low-confidence guess dressed up as a recommendation.

## 3. AI User Experience (Cross-Reference)

Every AI result supports: review · source inspection · accept as draft · edit · reject · report issue · request alternative · escalate to human review — restated from [../06-design/ai-assisted-experience-architecture.md, Section 2](../06-design/ai-assisted-experience-architecture.md#2-required-elements-of-every-ai-output). **Acceptance of an AI suggestion never bypasses the normal domain workflow** — restated absolutely, directly reinforcing [human-in-the-loop-and-authority-model.md, Section 1](human-in-the-loop-and-authority-model.md#1-human-in-the-loop-lifecycle)'s "Authorized Application Action" stage.

## 4. AI-Generated Content Labeling

### Label As

AI-generated draft · AI-assisted classification · AI-suggested match · AI-detected anomaly · AI-generated summary · AI recommendation.

### Never Label As

Official decision · certified result · approved finding · confirmed violation.

**AI involvement is always visible** — restated absolutely from [ai-vision-principles-and-governance.md, Section 2](ai-vision-principles-and-governance.md#2-principles), Principle 12; a user should never need to guess whether content came from a person or a model.

## 5. Correction and Feedback

Every reviewer disposition (accept/edit/reject/escalate, per [human-in-the-loop-and-authority-model.md, Section 4](human-in-the-loop-and-authority-model.md#4-acceptance-editing-rejection-and-escalation)) is captured as feedback data, feeding [ai-evaluation-testing-and-quality-assurance.md, "Human Acceptance"](ai-evaluation-testing-and-quality-assurance.md#1-evaluation-dimensions) evaluation metrics. A high correction/rejection rate for a specific capability is itself a signal warranting review, per [ai-observability-cost-quotas-and-operations.md](ai-observability-cost-quotas-and-operations.md).

## 6. Recommendation Presentation

A recommendation (Tier 2/3 use cases specifically) is presented with its full evidentiary context visible before any accept/reject decision — never a bare conclusion the reviewer must take on faith. Where multiple recommendation options exist (e.g., tournament-scheduling alternatives), tradeoffs between them are shown explicitly, per [ai-use-case-and-risk-classification.md, "UC-04"](ai-use-case-and-risk-classification.md#1-use-case-classification).

## 7. Relationship to Phase 0.9 Design Architecture

This document does not redefine [../06-design/ai-assisted-experience-architecture.md](../06-design/ai-assisted-experience-architecture.md)'s interface patterns — it provides the AI-architecture-side detail (confidence-category evaluation requirements, evidence-assembly mechanics) that document's interface requirements depend on.

## 8. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably the specific evidence-presentation format per use case and whether a standardized "confidence category" visual treatment is adopted across all capabilities.
