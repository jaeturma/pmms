# PMMS Narrative Summary and Natural-Language Reporting (UC-05, UC-11)

**Status:** Draft Complete — Pending AI, Security, Privacy, Domain, Sports, Quality, and Engineering Validation
**Related:** [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) · [ai-use-case-and-risk-classification.md](ai-use-case-and-risk-classification.md)

Tier 1–2 (varies public vs. internal). Covers UC-05 (Automatic Narrative Event Summaries) and UC-11 (Natural-Language Report Generation).

---

## Automatic Narrative Event Summaries (UC-05)

### Potential Assistance

Generate daily event summaries · generate sport-level summaries · generate committee reports · generate public-result narratives · summarize notable performances · summarize schedule changes · summarize medal movement · generate executive briefs · draft post-event reports.

### Requirements

Generated narratives: use approved source data (never an ungrounded claim) · **distinguish certified from provisional data** — restated absolutely per working rule 24, directly extending [../06-design/dashboard-table-chart-and-data-visualization-standards.md, "Certified Versus Published States"](../06-design/dashboard-table-chart-and-data-visualization-standards.md#certified-versus-published-states) · cite source records · avoid unsupported conclusions · avoid exposing restricted data · **are reviewed before publication** — restated absolutely, no narrative auto-publishes.

### Summary Categories

| Category | Classification | Data Restriction |
|---|---|---|
| Public daily summary | Public | Certified/published data only, per [public-portal-kiosk-scoreboard-and-display-experience.md](../06-design/public-portal-kiosk-scoreboard-and-display-experience.md) |
| Internal operational summary | Internal | May include provisional data, clearly labeled as such |
| Committee summary | Internal | Committee-scoped |
| Sport summary | Internal–Public split | Public version certified-only; internal version may include in-progress detail |
| Executive summary | Internal | Meet-wide operational status |
| Post-event report | Internal–Public split | Historical, reproducible per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession) |

Every summary carries: source version · public or internal classification · certified-data restrictions (for public summaries specifically) · a named reviewer · tone guidance (per [../06-design/content-design-terminology-help-and-onboarding.md](../06-design/content-design-terminology-help-and-onboarding.md)) · prohibited sensitive details (no medical/eligibility/finance/security content in any narrative, ever) · a generated-content label (per [ai-explainability-confidence-and-user-review.md, Section 4](ai-explainability-confidence-and-user-review.md#4-ai-generated-content-labeling)) · the standard publication workflow (per [../06-design/high-integrity-approval-certification-and-publication-ux.md, Section 5](../06-design/high-integrity-approval-certification-and-publication-ux.md#5-publication-ux), unchanged).

## Natural-Language Report Generation (UC-11)

### Lifecycle

```text
Natural-Language Request
→ Authorization and Scope Resolution
→ Approved Report Intent
→ Query Plan or Read-Model Selection
→ Data Retrieval
→ Privacy Filter
→ Summary or Visualization Recommendation
→ User Review
→ Export Through Normal Workflow
```

**AI must not generate arbitrary SQL or access unapproved tables** — restated absolutely; a natural-language report request resolves to one of a bounded set of pre-approved report intents/read models (per [../02-data/public-reporting-and-projection-data.md, Section 2](../02-data/public-reporting-and-projection-data.md#2-read-models-and-analytics)), never a freely-generated database query. Export itself always passes through [../06-design/search-filter-import-export-and-file-experience.md, Section 4](../06-design/search-filter-import-export-and-file-experience.md#4-export-experience)'s existing, unmodified export-authorization workflow.

## Authority Table (Both Use Cases)

| Element | Value |
|---|---|
| Requesting user | Media/Public Information staff (UC-05); any authorized reporting user (UC-11) |
| Scope | Meet/organization-scoped; the requesting user's own authorized report scope for UC-11 |
| Data classification | Varies — public summaries draw Public-tier data only; internal summaries may draw Internal-tier data within the requester's authorization |
| Permitted input | Certified/published data for public output; the requester's own authorized data scope for internal output |
| Prohibited input | Any data the requesting user could not otherwise access directly — restated absolutely per working rule 22 |
| Allowed output | Draft narrative/report content, always requiring review |
| Prohibited action | Direct publication without human review; SQL generation; unapproved table access |
| Required reviewer | The Media reviewer (UC-05) or the report requester (UC-11), per the domain's ordinary review chain |
| Audit level | Standard |
| Feature-flag state | Off by default, pending pilot approval |

## Open Questions

See [ai-open-decisions.md](ai-open-decisions.md) — notably whether UC-05's public and internal narrative variants are tracked as one use case or split, and the bounded report-intent catalog for UC-11.
