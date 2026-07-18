# PMMS Domain Classification

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [business-capability-map.md](business-capability-map.md) · [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md)

This document classifies each of the 34 bounded contexts (see [bounded-context-catalog.md](bounded-context-catalog.md)) into **Core**, **Supporting**, or **Generic** domains, using Eric Evans' strategic-classification model. Classification informs investment priority, build-vs-reuse decisions, and where domain-expert involvement matters most — it is not a statement about implementation order (see [../00-product/product-scope.md, Section 16](../00-product/product-scope.md#16-release-sequencing-directional-not-committed) for sequencing).

## Classification Criteria

A domain is classified **Core** if it meets most of the following:
- It is the reason DepEd would choose PMMS over a generic tool or spreadsheet.
- Getting it wrong causes direct institutional, legal, or reputational harm.
- It requires deep, PMMS-specific business logic that cannot be bought or reused off the shelf.
- It is explicitly called out as a high-integrity domain in [Phase 0.1](../00-product/phase-0.1-product-foundation.md#8-product-principles).

A domain is classified **Supporting** if:
- It is necessary for a complete meet but is not what differentiates PMMS.
- It has meaningful PMMS-specific workflow but lower strategic risk than core domains.
- A defect here degrades operations but does not directly compromise result integrity.

A domain is classified **Generic** if:
- The problem is well understood and solved by established patterns or off-the-shelf approaches.
- PMMS does not need to reinvent it — but that does not mean it is unimportant; several generic domains (Identity and Access, Audit and Compliance) carry elevated security/reliability requirements despite being classified generic.

## Core Domains

| Domain Grouping | Bounded Contexts | Why Core | Complexity | Differentiation | Integrity | Expected Change Frequency |
|---|---|---|---|---|---|---|
| Athlete Registration and Identity Resolution | [BC-07 Participant Registry](bounded-context-catalog.md#bc-07--participant-registry), [BC-08 Athlete Registration](bounded-context-catalog.md#bc-08--athlete-registration) | Correct identity resolution underlies every downstream decision (eligibility, entries, accreditation); duplicate/incorrect identity is a named risk (RSK-02) | High (matching, duplicate detection) | High | High | Medium |
| Athlete Eligibility and Clearance | [BC-09](bounded-context-catalog.md#bc-09--eligibility-and-clearance-high-integrity) | Directly determines who may compete; incorrect decisions cause disqualification and reputational harm (RSK-01) | High (multi-step review workflow) | High | **Critical** | Low (workflow), depends on external rule changes |
| Competition and Tournament Management | [BC-10 Sports Catalog](bounded-context-catalog.md#bc-10--sports-catalog), [BC-11 Competition Entries](bounded-context-catalog.md#bc-11--competition-entries), [BC-12 Tournament Management](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) | Must support wide sport-specific variation without hard-coding; core to "meet operations," not just registration (see [Phase 0.1 Section 10](../00-product/phase-0.1-product-foundation.md#10-product-boundaries)) | Very High (sport-specific formats) | High | High | Medium–High (per sport/meet) |
| Scoring and Official Results | [BC-15 Scoring](bounded-context-catalog.md#bc-15--scoring-high-integrity), [BC-16 Official Results](bounded-context-catalog.md#bc-16--official-results-high-integrity), [BC-17 Protest and Appeals](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity) | The single most trust-sensitive part of the platform; RSK-03 (unauthorized score changes) and RSK-04 (incorrect tally depends on this) | High | High | **Critical** | Low (workflow stable; rule content depends on sport) |
| Medal Tally and Team Standings | [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived) | Public-facing, high-trust derived output; explicitly named a high-integrity domain in Phase 0.1 | Medium (derivation logic) | High | **Critical** | Low |
| Accreditation and Access Validation | [BC-19 Accreditation](bounded-context-catalog.md#bc-19--accreditation-high-integrity), [BC-20 Access Validation](bounded-context-catalog.md#bc-20--access-validation-high-integrity-high-volume-offline) | Security/integrity gate for the physical event; must work offline at high volume — a distinctly hard problem | High (offline, high-volume) | High | High | Low |
| Meet Operations and Coordination | [BC-04 Meet Administration](bounded-context-catalog.md#bc-04--meet-administration) | The backbone lifecycle owner that every meet-scoped context conforms to; without it there is no "meet" to scope anything else against | Medium | Medium–High | High | Low |

**Note on scope:** The prompt's original "Meet Operations and Coordination" core-domain candidate could have swept in Committee Operations and Venue/Schedule. Per working rule 18 (avoid one oversized Meet Management context) and rule 17 (classify by responsibility/ownership/lifecycle), only **Meet Administration** (meet identity and lifecycle) is retained as Core; **Committee Operations** and **Venue and Schedule** are classified Supporting because, while operationally necessary, they do not carry the same institutional-trust weight as meet lifecycle integrity itself.

## Supporting Domains

| Bounded Context | Why Supporting | Complexity | Integrity | Expected Change Frequency |
|---|---|---|---|---|
| [BC-03 Organization Directory](bounded-context-catalog.md#bc-03--organization-directory) | Necessary master data; not a differentiator | Low–Medium | Standard–High | Low |
| [BC-05 Committee Operations](bounded-context-catalog.md#bc-05--committee-operations) | Administrative shell around committees; important for coordination, not itself a trust-critical output | Medium | Standard | Medium |
| [BC-06 Delegation Management](bounded-context-catalog.md#bc-06--delegation-management) | Necessary structural data; complexity depends on unresolved hierarchy question | Medium | Standard–High | Medium |
| [BC-13 Technical Officials](bounded-context-catalog.md#bc-13--technical-officials) | Assignment logistics; identity itself lives in a core context (Participant Registry) | Medium | High | Medium |
| [BC-14 Venue and Schedule](bounded-context-catalog.md#bc-14--venue-and-schedule) | Logistics-heavy; high public visibility but not itself a trust-critical decision | High (conflict resolution) | High | High (frequent revisions) |
| [BC-21 Medical Operations](bounded-context-catalog.md#bc-21--medical-operations-restricted) | Operationally necessary and highly sensitive, but not a competitive differentiator of the platform; classified Supporting with an elevated **security/privacy** flag, not a Core integrity flag, since its integrity concern is confidentiality rather than result-correctness | Medium | Critical (sensitivity) | Low |
| [BC-22 Billeting](bounded-context-catalog.md#bc-22--billeting) | Standard logistics | Low–Medium | Standard | Low |
| [BC-23 Food Services](bounded-context-catalog.md#bc-23--food-services) | Standard logistics | Low–Medium | Standard | Low |
| [BC-24 Transportation](bounded-context-catalog.md#bc-24--transportation) | Standard logistics | Low–Medium | Standard | Low |
| [BC-25 Security Operations](bounded-context-catalog.md#bc-25--security-operations) | Operationally critical for safety, not a platform differentiator | Medium | High (safety) | Medium |
| [BC-26 Finance Operations](bounded-context-catalog.md#bc-26--finance-operations) | Necessary monitoring, explicitly not a full accounting system | Medium | High | Low |
| [BC-27 ICT Service Operations](bounded-context-catalog.md#bc-27--ict-service-operations) | Operational support function | Low–Medium | Standard | Low |
| [BC-28 Media and Communications](bounded-context-catalog.md#bc-28--media-and-communications) | Important for public trust in communications, not itself a transactional integrity domain | Low–Medium | Standard–High | Medium |
| [BC-29 Public Information](bounded-context-catalog.md#bc-29--public-information-non-authoritative) | Consumer-facing, high visibility, but explicitly non-authoritative | Medium (caching, scale) | Standard | High (frequent refresh) |
| [BC-30 Document and Records](bounded-context-catalog.md#bc-30--document-and-records) | Institutional record-keeping, necessary but not a differentiator | Medium | High | Low |
| [BC-33 Reporting and Analytics](bounded-context-catalog.md#bc-33--reporting-and-analytics-consumes-only) | Consumes and presents; does not decide business outcomes | Medium–High | Standard | Medium |

## Generic Domains

| Bounded Context | Why Generic | Elevated Requirement Despite Being Generic |
|---|---|---|
| [BC-01 Platform Administration](bounded-context-catalog.md#bc-01--platform-administration) | Configuration/settings management is a solved pattern | None beyond standard admin-surface security |
| [BC-02 Identity and Access](bounded-context-catalog.md#bc-02--identity-and-access) | Authentication is a solved problem (and the repository already has a starter-kit implementation with 2FA/passkeys) | **High** — every other context's audit trail and separation-of-duties guarantees depend on this being correct |
| [BC-31 Notifications](bounded-context-catalog.md#bc-31--notifications) | Delivery mechanics are a solved pattern | Reliability for time-sensitive advisories (e.g., schedule changes) |
| [BC-32 Audit and Compliance](bounded-context-catalog.md#bc-32--audit-and-compliance-elevated-integrity) | The mechanism (append-only event log) is a solved pattern | **Critical** — underpins the auditability guarantee of every Core domain; a failure here silently removes accountability everywhere else |
| [BC-34 Configuration and Reference Data](bounded-context-catalog.md#bc-34--configuration-and-reference-data) | Reference-data management is a solved pattern | Must stay narrowly scoped (see exclusions) or it will silently absorb Core-domain rule content it has no authority to define |

## Outsourcing or Reuse Suitability

- **BC-02 Identity and Access**, **BC-31 Notifications**, and parts of **BC-30 Document and Records** (file storage mechanics via MinIO) are strong candidates for building on established Laravel ecosystem patterns (e.g., Fortify, already present in the repository) rather than custom-building from scratch.
- **BC-32 Audit and Compliance** should use an established append-only logging pattern rather than a bespoke design, though its *integration* (which contexts publish which events) is PMMS-specific.
- **Core domains** (BC-07 through BC-20 as classified above) should **not** be outsourced, templated, or generated from generic CRUD scaffolding — they require deliberate, validated business-rule modeling in Phase 0.3 and beyond.

## Areas Requiring Domain Expert Involvement

Per working rules 11–12, the following areas cannot be finalized by the development team alone and require input from an authorized sports or DepEd source before detailed design proceeds:

- Eligibility rules and approval chain (BC-09) — [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)
- Sport-specific competition formats and scoring rules (BC-10, BC-15) — [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source)
- Result certification and protest/appeal authority (BC-16, BC-17) — [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain), OD-09
- Medal tally and team-point computation rules (BC-18) — [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules), OD-13
- Medical data handling and consent for minors (BC-21) — [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling)
- AI-service data-access restrictions across all contexts — [Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions)

These are carried forward into [domain-open-decisions.md](domain-open-decisions.md) with context-specific framing.
