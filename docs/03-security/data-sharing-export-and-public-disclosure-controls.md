# PMMS Data Sharing, Export, and Public Disclosure Controls

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../02-data/public-reporting-and-projection-data.md](../02-data/public-reporting-and-projection-data.md) · [../02-data/import-export-and-data-exchange.md, Section 2](../02-data/import-export-and-data-exchange.md#2-export-architecture) · [privacy-by-design-architecture.md](privacy-by-design-architecture.md)

This document defines public-disclosure workflow, data-sharing categories, export controls, masking/redaction/de-identification, data-subject-rights readiness, and consent/authority-record candidates. **No publication workflow implementation, export tooling, or consent-form content is created here.**

---

## Public Disclosure Controls

| Requirement | Direction |
|---|---|
| Approved publishable projection | Every public-facing item (schedule, result, medal tally, athlete profile, delegation profile, announcement, photo, media asset, venue information, public report) is served from an approved projection, never a direct operational-table read, per [../02-data/public-reporting-and-projection-data.md, Section 1](../02-data/public-reporting-and-projection-data.md#1-public-projections) |
| Privacy filter | Every projection build applies the classification-driven privacy filter before data reaches the public store — filtering happens at build time, never at read time |
| Publication status | Every publishable item has an explicit status (draft, approved, published, unpublished, corrected) — nothing is public merely by existing |
| Source version | A published item references the specific source-data version it was built from, per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession), supporting historical reproducibility |
| Approval | Publication requires the specific Publication-tier authority named in [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 19](../01-architecture/phase-0.3-access-and-assignment-architecture.md#19-approval-authority-levels) — never an automatic publish-on-save |
| Expiry where applicable | Time-bound announcements/content have a defined expiry, after which they are no longer surfaced |
| Unpublish or correction flow | A published item can be withdrawn or corrected; a correction creates a new version rather than silently editing the live public record, consistent with [../02-data/temporal-history-and-versioning-model.md](../02-data/temporal-history-and-versioning-model.md) |
| Cache invalidation | Unpublishing/correcting an item invalidates any cached copy, per [../01-architecture/caching-and-session-architecture.md](../01-architecture/caching-and-session-architecture.md) |
| Search-index update | If a search index exists (staged, per [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes)), it reflects unpublication/correction promptly |
| Audit | Every publish/unpublish/correction action is audit-relevant |

## Data Sharing and External Disclosure

| Category | Direction |
|---|---|
| Internal operational sharing | Cross-context sharing within PMMS follows the approved patterns in [../01-architecture/internal-integration-architecture.md](../01-architecture/internal-integration-architecture.md) — never an ad hoc direct table read |
| Inter-committee sharing | Governed by each committee's actual need-to-know, per [../01-architecture/scope-model.md](../01-architecture/scope-model.md) — committee membership alone grants nothing |
| Inter-organization sharing | Not currently applicable at single-organization scope; a future consideration if multi-organization support (per [../00-product/open-decisions.md, OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)) is adopted |
| Parent or guardian access | Per [minor-athlete-and-guardian-data-governance.md, Section 2](minor-athlete-and-guardian-data-governance.md#2-guardian-data) |
| Media sharing | Any data shared with media/press for coverage purposes follows the same approval and classification discipline as public disclosure — never a casual "send the spreadsheet" practice |
| Public disclosure | Per the section above |
| Vendor processing | Any future vendor that processes PMMS data (e.g., a notification-delivery provider) is a candidate for [vendor-and-third-party-risk.md](vendor-and-third-party-risk.md) assessment before approval — none currently approved |
| Government integration | Any future integration with a DepEd or government system is a candidate for [policy-source-registry.md](policy-source-registry.md) tracking and the same anti-corruption-layer discipline as any external integration — none currently approved |
| Research or analytics | External research use of PMMS data (if ever requested) requires de-identification and a specific, documented approval — never assumed as a standing practice |
| Emergency sharing | Sharing data with emergency responders (e.g., medical emergency) follows the emergency-access governance in [medical-eligibility-finance-and-sensitive-data-controls.md, "Medical Data Governance"](medical-eligibility-finance-and-sensitive-data-controls.md#medical-data-governance), not an ad hoc exception |

Every sharing instance requires: **purpose**, **authority** (who approved it), **minimum data** (never more than necessary), **classification** (of what's being shared), **recipient** (named/identified), **transfer method** (secure), **retention** (at the recipient, where controllable), **security** (protecting data in transit and at the recipient), **audit** (the sharing event itself), **revocation** (where feasible), and an **agreement or policy reference** where a formal arrangement is required.

## Export Controls

| Control | Direction |
|---|---|
| Role and scope | Every export respects the exporting user's role, scope, and assignment — an export is never a bypass around normal query-time authorization |
| Classification | Export content respects the classification of every included field — a report combining Public and Restricted data is itself Restricted |
| Reason capture | Sensitive exports (Restricted tier and above) require a captured reason |
| Approval | The highest-sensitivity export categories (medical, audit, eligibility evidence) require an approval step beyond ordinary role-based authorization |
| Watermarking readiness | A candidate control for sensitive document exports (identifying the exporting user/session) — evaluated, not committed |
| Encryption readiness | Sensitive exports are encrypted at rest and, where transmitted, in transit, per [cryptography-key-and-secret-management.md, Section 1](cryptography-key-and-secret-management.md#1-cryptographic-architecture) |
| Expiry | Generated export files have a bounded availability window before automatic cleanup, per [../02-data/retention-archival-and-disposal.md, Section 1](../02-data/retention-archival-and-disposal.md#1-retention-categories) ("export files" category) |
| Download audit | Every export generation and every subsequent download is audit-relevant |
| Public versus restricted export | A public-data export (e.g., a published results CSV) follows lighter controls than a Restricted/Highly Restricted export — the distinction is explicit, never assumed |
| CSV security | CSV exports are a candidate formula-injection vector (a cell value beginning with `=`, `+`, `-`, `@` interpreted as a formula by spreadsheet software when opened) — mitigation (e.g., prefixing/escaping such values) is an implementation-phase concern flagged here, per [../02-data/import-export-and-data-exchange.md, Section 2](../02-data/import-export-and-data-exchange.md#2-export-architecture) and [../02-data/data-open-decisions.md, PD-14](../02-data/data-open-decisions.md#pd-14--csv-formula-injection-mitigation-approach) |
| PDF classification | Generated PDF reports carry the classification of their most sensitive included content |
| Spreadsheet protection | Where feasible, sensitive spreadsheet exports use password protection or equivalent access control beyond the download-authorization step itself |
| Large export alerts | An unusually large export (volume or scope) is a candidate security-event trigger, per [audit-and-security-event-architecture.md, Section 8](audit-and-security-event-architecture.md#8-security-events) |
| Bulk personal-data restrictions | A bulk export of personal data across many individuals (vs. a single-record export) requires elevated authorization, reflecting its higher exposure impact if mishandled |
| Medical and audit export restrictions | The two highest-sensitivity export categories require the most restrictive authorization and mandatory reason capture, with no exception |

## Masking, Redaction, and De-Identification

Extends [../02-data/audit-and-security-data-architecture.md, Section 4](../02-data/audit-and-security-data-architecture.md#4-data-masking-and-redaction):

| Technique | Application |
|---|---|
| Partial identifiers | Displaying a partial identifier (e.g., last 4 digits) where full disclosure isn't needed for the specific view |
| Contact masking | Phone/email masked in list views, fully shown only where the specific workflow requires it |
| Birth-date masking | Age-band shown instead of exact birthdate where operationally sufficient |
| Medical summary | A status-only view (cleared/not cleared/conditional) substitutes for full medical detail wherever the consuming context doesn't need the detail |
| Eligibility-status-only views | Mirrors medical summary — status without full evidentiary detail |
| Guardian masking | Guardian contact masked outside the specific registration/emergency workflow |
| Export redaction | Sensitive fields excluded or masked in export output unless the export's specific approved purpose requires them |
| Log redaction | Restated from [audit-and-security-event-architecture.md, Section 9](audit-and-security-event-architecture.md#9-logging-and-monitoring-boundaries) |
| Support-view redaction | A support role's view of a user's data is masked/redacted beyond what the specific support task requires |
| Staging-data masking | Any (exceptional, approved) production-derived lower-environment data is masked/de-identified before use, per [../02-data/test-seed-and-reference-data-strategy.md, Section 3](../02-data/test-seed-and-reference-data-strategy.md#3-test-data-strategy) |
| Analytical de-identification | Analytics/reporting favors de-identified or aggregated data wherever individual-level detail isn't the specific report's purpose |
| Re-identification risk | A de-identified or aggregated dataset is reviewed for re-identification risk (e.g., a small delegation with only one athlete in a category effectively re-identifies that athlete) before public or broad disclosure |
| Small-group disclosure risk | Public aggregate statistics for very small groups (e.g., a single-athlete category) are a candidate for suppression or generalization, mirroring standard small-cell statistical-disclosure practice |
| Public leaderboard privacy | Public leaderboards/rankings show only the approved public-profile subset (Section, [minor-athlete-and-guardian-data-governance.md](minor-athlete-and-guardian-data-governance.md)), never full underlying personal data |

## Data-Subject Rights Readiness

Without asserting any specific legal obligation, PMMS's architecture is designed to support the following request types once a formal process is established:

| Readiness Area | Direction |
|---|---|
| Access request | A data subject (or verified guardian) can request to see what personal data PMMS holds about them — supported by the classification and ownership model already in place; process/SLA not yet defined |
| Correction request | Supported by the correction architecture in [../02-data/high-integrity-data-model.md, "Data Correction Architecture"](../02-data/high-integrity-data-model.md#data-correction-architecture) — a correction creates a new version, never silently overwrites |
| Objection or restriction request where applicable | A candidate future capability; process not yet defined |
| Deletion request evaluation | Any deletion request is evaluated against retention obligations and high-integrity-record preservation needs (per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md)) — not automatically honored where a legitimate retention basis exists |
| Portability request evaluation | A candidate future capability (exporting a data subject's own data in a usable format); not yet defined |
| Consent withdrawal where applicable | Where a specific consent record exists (Section, "Consent and Authority Records" below), withdrawal is a candidate supported action; scope not yet defined |
| Guardian request | A verified guardian's request on behalf of a minor follows the same readiness areas above, subject to relationship verification |
| Identity verification | Any data-subject-rights request requires verifying the requester's identity before acting, to prevent impersonation-based data exposure |
| Case tracking | Requests are tracked from receipt through resolution, with a named handler |
| Data-owner review | The relevant bounded-context data owner (per [../02-data/persistence-ownership-map.md](../02-data/persistence-ownership-map.md)) reviews requests touching their owned data |
| Legal or policy exceptions | Some requests may be legitimately declined or limited based on legal/policy exceptions — none are asserted or invented here; every exception requires named-authority validation |
| Audit | Every data-subject-rights request and its resolution is audit-relevant |
| Response evidence | The resolution and its basis are documented, supporting future accountability review |

**No specific response-time SLA is defined** — this is a policy/legal decision, not an architecture decision.

## Consent and Authority Records

Candidate record types — **not asserted as legally required**, each marked for legal and policy validation before being treated as such:

| Candidate Record | Purpose |
|---|---|
| Guardian authorization | Authorization for a minor's participation, captured at registration |
| Photo or media authorization | Per [minor-athlete-and-guardian-data-governance.md, Section 3](minor-athlete-and-guardian-data-governance.md#3-photo-and-media-publication-governance) |
| Medical handling authorization | Authorization for medical-team handling of a participant's medical data/emergency care |
| Participation agreement | Any general terms of participation a delegation/athlete is asked to acknowledge |
| Data-sharing authorization | Where data is shared beyond ordinary operational use (e.g., with an external research request) |
| Terms acceptance | Standard platform terms-of-use acceptance, if adopted |
| Privacy notice acknowledgment | Acknowledgment that a privacy notice was made available, if adopted |
| Emergency contact authorization | Authorization to use emergency-contact information for its stated purpose |

**Do not assume every record above is legally required** — each requires Data Privacy and Legal Stakeholder validation, per working rule 52's instruction, before being implemented as a formal requirement rather than a design candidate.

## Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably which consent/authority records are actually legally or institutionally required, the data-subject-rights request process/SLA, and CSV formula-injection mitigation adoption timing (mirrors [../02-data/data-open-decisions.md, PD-14](../02-data/data-open-decisions.md#pd-14--csv-formula-injection-mitigation-approach)).
