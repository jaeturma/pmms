# PMMS Medal, Performance, and Result Anomaly Analytics (UC-07, UC-08)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [../06-design/sports-tournament-scoring-and-results-components.md](../06-design/sports-tournament-scoring-and-results-components.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 1–2 (analytics) and Tier 3 (anomaly detection). Covers UC-07 (Medal and Performance Analytics) and UC-08 (Result Anomaly Detection) — both operate strictly downstream of, and never in place of, PMMS's official scoring/results/medal-tally chain.

---

## Medal and Performance Analytics (UC-07)

### Potential Assistance

Analyze medal distribution · compare delegation performance · identify performance trends · analyze participation by sport · compare current and historical meets · generate sport and delegation summaries · identify emerging strengths or weaknesses · support post-meet planning.

### Data-State Discipline

Analytics must distinguish: raw scores · validated results · certified results · published results · provisional medal tally · certified medal tally — restated absolutely, directly extending [../06-design/dashboard-table-chart-and-data-visualization-standards.md, Section 4](../06-design/dashboard-table-chart-and-data-visualization-standards.md#4-data-freshness-version-and-state-indication)'s certified-versus-published discipline into analytics specifically. An analytic never blends raw scores with certified results as if interchangeable.

## Result Anomaly Detection (UC-08)

### Potential Signals

Unusual score delta · impossible sequence · duplicate submission · unexpected rank change · inconsistent units · invalid precision · repeated corrections · mismatch between detail and total · improbable timing · conflicting devices.

### Absolute Rule

**Anomaly detection creates a review alert only — it never alters scores or official results** — restated absolutely per this phase's own governing instruction, directly reaffirming Phase 0.5's data-integrity model. Every anomaly requires: evidence · a rule or baseline it deviates from · severity · a named reviewer · a disposition · full audit.

### Anomaly Is Not Accusation

**AI must not convert statistical correlation into a factual accusation** — restated absolutely per working rule 32. **Anomaly detection must not label a person as fraudulent or dishonest** — restated absolutely per working rule 33. An anomaly finding describes a data pattern requiring review, never a conclusion about a person's intent or honesty — the language used throughout this capability's outputs is deliberately neutral ("unusual score delta detected, requires review"), never accusatory.

## Authority Table (Both Use Cases)

| Element | Value |
|---|---|
| Requesting user | Tally team / Executive (UC-07); Result validator (UC-08) |
| Scope | Certified results and medal tally within the requester's authorized meet/sport scope |
| Data classification | Internal–Public (analytics); Internal (anomaly detection, since it touches pre-certification data) |
| Permitted input | Certified results and tally data for UC-07; scoring data already visible to the Result validator role for UC-08 |
| Prohibited input | Provisional data presented as if certified; any data outside the requester's own scope |
| Allowed output | Trend summaries, comparisons (UC-07); anomaly alerts with evidence (UC-08) |
| Prohibited action | Any modification to scores, results, or medal tally — restated absolutely for both |
| Required reviewer | Tally certifier (UC-07 summaries feeding public output); Result validator/certifier (UC-08 anomalies) |
| Audit level | Standard (UC-07); Full (UC-08, given its Tier 3 status) |
| Feature-flag state | Off by default, pending pilot approval |

## Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably the specific anomaly-detection baseline/threshold methodology (deliberately not defined here) and whether athlete-level performance trend analytics require additional privacy review beyond delegation-level aggregation.
