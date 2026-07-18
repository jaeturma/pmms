# PMMS Venue and Schedule Risk Prediction (UC-13)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../05-devops/meet-day-venue-and-offline-operations.md](../05-devops/meet-day-venue-and-offline-operations.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 3 (High-Risk Decision Support). Predictive risk assistance for venue and schedule operations — the most speculative capability in this package, given its reliance on historical-pattern inference rather than grounded document retrieval.

---

## 1. Potential Assistance

Identify high-risk schedule congestion · flag insufficient venue turnaround · identify repeated venue incidents · detect transport dependency risks · detect weather-related exposure if an approved external source is integrated later (none is approved or assumed in this phase) · identify device or connectivity risk · identify staffing gaps · recommend contingency planning.

## 2. Candidate Inputs

Historical incidents · venue readiness · device availability · network reliability · staffing · transport dependencies · schedule density · transition time · prior delays · external weather data if approved.

## 3. Output Requirements

**Risk predictions must be explainable and must remain advisory** — restated absolutely per this phase's own governing instruction. Outputs are: risk indicators (not certainties) · contributing factors (what specifically drove this risk assessment) · uncertainty · recommended mitigation · **no automatic cancellation or exclusion** — restated absolutely, a risk prediction never triggers an automatic schedule change, venue closure, or any exclusionary action without a human decision.

## 4. Predictive Risk Is Never a Sole Basis for Exclusion or Discipline

**Predictive risk must not be used as the sole basis for exclusion or disciplinary action** — restated absolutely per working rule 34. A "high staffing gap risk" prediction for a specific venue informs contingency planning; it is never used, for example, as grounds to exclude a delegation, penalize a committee, or take any disciplinary action against an individual.

## 5. Relationship to Meet-Day Operations

This capability's output feeds directly into the readiness checklist already established in [../05-devops/meet-day-venue-and-offline-operations.md, Section 1](../05-devops/meet-day-venue-and-offline-operations.md#1-meet-day-operations) — a risk prediction is one input the meet-day command center considers, never a replacement for the human judgment that checklist already requires.

## 6. Authority Table

| Element | Value |
|---|---|
| Requesting user | Meet administrator / ICT |
| Scope | The specific meet/venue under evaluation |
| Data classification | Internal |
| Permitted input | Operational data (incidents, device status, schedule density) already visible to the Meet administrator role |
| Prohibited input | Individual-level medical, financial, or disciplinary records |
| Allowed output | Risk indicators with contributing factors and mitigation suggestions |
| Prohibited action | Automatic cancellation, exclusion, or disciplinary action of any kind — restated absolutely |
| Required reviewer | Meet administrator |
| Audit level | Full, given Tier 3 status |
| Feature-flag state | Off by default, pending pilot approval — likely among the later capabilities piloted given its speculative, pattern-inference nature |

## 7. Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably whether this capability is included in the initial pilot at all, given its Tier 3 risk profile and the immaturity of historical-incident data available to ground its predictions before at least one real meet cycle has occurred.
