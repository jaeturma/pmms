# PMMS Schedule Conflict and Tournament Recommendation Architecture (UC-03, UC-04)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 2 (Moderate-Risk Recommendation). Covers UC-03 (Schedule Conflict Detection) and UC-04 (Tournament Scheduling Recommendations) — both combine deterministic rules with AI explanation, never relying on AI for the underlying correctness guarantee.

---

## Schedule Conflict Detection (UC-03)

### Potential Assistance

Detect venue conflicts · detect participant schedule overlaps · detect official-assignment conflicts · detect delegation travel conflicts · detect insufficient transition time · detect equipment or facility conflicts · detect operational committee conflicts · detect dependencies between competition rounds.

### Deterministic-First Design

**Conflict detection should preferably combine deterministic rules with AI explanation** — restated absolutely per this phase's own governing instruction. Deterministic rules are preferred for: overlapping venue · overlapping athlete · overlapping official · overlapping tournament unit · insufficient setup time · impossible advancement timing — these are structural, calculable facts (two events cannot occupy the same venue at the same time), never left to AI inference where a deterministic check suffices.

**AI is used for:** explanation (why this conflict matters, in plain language) · prioritization (which conflicts are most urgent to resolve) · tradeoff analysis (what resolving one conflict costs elsewhere) · alternative suggestions (candidate resolutions for a tournament manager to evaluate).

**AI must not silently modify official schedules** — restated absolutely.

## Tournament Scheduling Recommendations (UC-04)

### Potential Assistance

Recommend event ordering · suggest venue allocation · suggest time-slot allocation · reduce participant conflicts · reduce official-assignment conflicts · account for venue capacity · account for estimated event duration · account for travel or transition time · suggest contingency options · explain tradeoffs.

**AI recommendations require review and approval by authorized tournament personnel** — restated absolutely; a generated schedule is a draft proposal, never an auto-applied change to the official schedule.

### Inputs

Sports · events · tournament format · estimated durations · venues · venue capabilities · participant availability · officials · transition time · rest requirements from approved rules (never invented, per working rule 34 — a rest-period requirement is cited from a verified sports-rule source or explicitly marked "requires validation") · operating hours · dependencies · weather inputs only if approved later (no weather-data integration exists or is assumed in this phase).

### Outputs

Candidate schedules · conflicts · assumptions · tradeoffs · risk indicators · alternative versions — every recommendation is presented as one of potentially several candidate options, never a single "the AI decided this" schedule.

## Authority Table (Both Use Cases)

| Element | Value |
|---|---|
| Requesting user | Tournament manager |
| Required role/permission | Existing tournament-management permissions, per [../01-architecture/permission-catalog.md](../01-architecture/permission-catalog.md) |
| Scope | The specific meet/sport under scheduling |
| Data classification | Internal |
| Permitted input | Schedule, venue, official-assignment, and entry data already visible to the Tournament manager role |
| Prohibited input | Medical, financial, or eligibility-evidence detail unrelated to scheduling |
| Allowed output | Conflict findings and candidate schedule recommendations |
| Prohibited action | Any direct write to the official schedule — restated absolutely |
| Required reviewer | The requesting Tournament manager, or a more senior scheduling authority for meet-wide changes |
| Audit level | Standard |
| Feature-flag state | Off by default, pending pilot approval |

## Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably whether weather-data integration is ever approved (currently entirely out of scope) and the specific deterministic-conflict-rule engine's relationship to the existing [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) command catalog.
