# PMMS Assumptions, Constraints, and Risks

**Status:** Draft for Architecture and Stakeholder Validation
**Related:** [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md#20-assumptions) · [operating-model.md](operating-model.md) · [open-decisions.md](open-decisions.md)

This document consolidates and structures assumptions, constraints, dependencies, and risks across categories. It expands on the summary versions in the product foundation document (Sections 20–22). Likelihood and impact ratings are placeholders pending stakeholder input — no ratings are invented here.

---

## 1. Assumptions

| ID | Assumption | Validation Status |
|---|---|---|
| ASM-01 | DepEd is the primary organization for PMMS. | Requires validation |
| ASM-02 | Provincial meets are the initial deployment target. | Requires validation |
| ASM-03 | Division, regional, and national meet support is a future direction, not a launch requirement. | Assumption — direction, not commitment |
| ASM-04 | Both web and mobile access are required. | Requires validation |
| ASM-05 | Internet connectivity is unreliable at some or all venues. | Requires validation against actual venue conditions |
| ASM-06 | Different sports require different competition structures and rules. | Requires validation from sports specialists |
| ASM-07 | Official result validation remains a human responsibility. | Confirmed product principle |
| ASM-08 | Committee workflows vary and require configuration. | Requires validation |
| ASM-09 | Historical meet records must be preserved across cycles. | Requires validation on retention duration/scope |
| ASM-10 | Athlete, medical, and eligibility data require enhanced protection. | Confirmed principle — extent requires legal/privacy validation |
| ASM-11 | Multiple venues may operate simultaneously within one meet. | Requires validation |
| ASM-12 | Public information requires controlled publication, not direct exposure of working data. | Confirmed principle |
| ASM-13 | No prior PMMS system or structured historical meet data exists to migrate. | Confirmed by repository inspection; scope/format of any historical data requires DepEd confirmation |

## 2. Constraints

| ID | Constraint |
|---|---|
| CON-01 | Limited and variable internet connectivity at venues. |
| CON-02 | Limited ICT personnel available for on-site support. |
| CON-03 | Varied digital literacy among users. |
| CON-04 | Shared or limited devices at some venues. |
| CON-05 | Strict, time-boxed event schedules with little tolerance for process failure. |
| CON-06 | Late registration changes close to or during the meet. |
| CON-07 | Last-minute venue and schedule adjustments. |
| CON-08 | Sport-specific rules and formats requiring configurable handling. |
| CON-09 | Sensitive minor data given that most athletes are likely minors. |
| CON-10 | Budget limitations (assumed, pending confirmation). |
| CON-11 | Hardware availability at venues (scanners, printers, displays, network equipment). |
| CON-12 | Need for printable documents for offline/paper-record contexts. |
| CON-13 | Need for offline workflows in the field. |
| CON-14 | Time-sensitive result validation and publication requirements. |
| CON-15 | Need to prevent unauthorized result modification. |
| CON-16 | Need to support audit and post-event review. |

## 3. Dependencies

| ID | Dependency | Notes |
|---|---|---|
| DEP-01 | Authoritative sports rules, formats, and scoring systems | Must be sourced from DepEd/sports governing bodies — not invented (see Rule 8 of the working rules) |
| DEP-02 | Authoritative eligibility criteria | Must be sourced from DepEd policy |
| DEP-03 | Data privacy legal framework applicable to DepEd and to minors | Requires legal/data-privacy stakeholder input |
| DEP-04 | School and delegation master data source | Source system/process to be confirmed (see [open-decisions.md](open-decisions.md)) |
| DEP-05 | Venue and infrastructure information (connectivity, power, device availability) | Requires site assessment, likely in a later phase |
| DEP-06 | Stakeholder availability for validation activities | Required to close open decisions and assumptions |

---

## 4. Risk Register

Likelihood values are placeholders (`TBD`) pending stakeholder and pilot input. Owners are proposed roles, not named individuals.

| Risk ID | Risk | Category | Description | Impact | Likelihood | Initial Mitigation | Proposed Owner | Status |
|---|---|---|---|---|---|---|---|---|
| RSK-01 | Incorrect eligibility decisions | Product | Eligibility determined incorrectly due to incomplete data or process gaps | High — athlete disqualification, reputational harm | TBD | Human-validated eligibility workflow with source documentation | Secretariat / Eligibility validation role | Open |
| RSK-02 | Duplicate athlete records | Data | Same athlete registered multiple times across delegations or meets | Medium — data integrity, possible eligibility fraud | TBD | Duplicate-detection assistance (advisory), unique-identity workflow | Secretariat | Open |
| RSK-03 | Unauthorized score changes | Security | Official result altered without proper authority | High — result integrity, institutional trust | TBD | Separation of duties, audit logging, access control | Tournament Managers / ICT | Open |
| RSK-04 | Incorrect medal tally | Product | Tally computed from unvalidated or erroneous results | High — public trust, appeals | TBD | Tally computed only from validated official results | Tally Team | Open |
| RSK-05 | Delayed result publication | Operational | Validation/publication bottleneck delays public information | Medium — stakeholder frustration | TBD | Streamlined validation-to-publication workflow | Tournament Managers | Open |
| RSK-06 | Connectivity failure at venues | Technical | Loss of internet during live operations | High — operational disruption | TBD | Offline-resilient field workflows, local sync | ICT Committee | Open |
| RSK-07 | Lost or stolen mobile devices | Security | Field devices lost/stolen with access to sensitive data | High — data breach potential | TBD | Device-level access controls, remote deprovisioning (later-phase design) | ICT Committee | Open |
| RSK-08 | QR code / accreditation credential misuse | Security | Credentials shared, forged, or misused for unauthorized access | Medium — venue security | TBD | Credential validation design, time/venue-scoped credentials | Accreditation Officers / Security | Open |
| RSK-09 | Privacy breach involving athlete or medical data | Privacy | Unauthorized access or disclosure of sensitive personal data | High — legal, reputational, harm to minors | TBD | Role-based access control, data minimization, encryption (later-phase design) | Data Privacy / Legal Stakeholders | Open |
| RSK-10 | Insufficient user training ahead of go-live | Adoption | Users unable to perform tasks effectively on the platform | Medium — adoption failure, workarounds | TBD | Structured training plan tied to go-live readiness | Meet Organizing Committee | Open |
| RSK-11 | Inconsistent rules across sports | Product | Sport-specific rule variation not properly configured | Medium — incorrect officiating/scoring support | TBD | Source-backed, per-sport configuration | Sports Specialists / Tournament Managers | Open |
| RSK-12 | Poor committee adoption | Adoption | Committees revert to manual/parallel processes | High — fragmented source of truth | TBD | Committee-specific workflow design, early stakeholder involvement | Meet Organizing Committee | Open |
| RSK-13 | Over-customization eroding maintainability | Technical | Excessive one-off customization reduces long-term maintainability | Medium — technical debt | TBD | Configuration-over-code principle, architecture review | Vendors / Implementation Partners | Open |
| RSK-14 | Uncontrolled scope growth | Product | Scope expands without a controlled change process | Medium — schedule/budget risk | TBD | Formal scope change process (see [product-scope.md](product-scope.md#2-scope-change-process)) | Meet Organizing Committee / Vendors | Open |
| RSK-15 | AI overreliance in high-integrity domains | AI | AI recommendations treated as authoritative without human review | High — result/eligibility integrity | TBD | Strict AI limitations per Product Principles; advisory-only enforcement | Vendors / DepEd Leadership | Open |
| RSK-16 | Inadequate backup and recovery capability | Technical | Data loss due to insufficient backup/recovery design | High — institutional record loss | TBD | Backup and recovery design as a commercial-quality requirement | ICT Committee | Open |
| RSK-17 | Device failure during live operations | Operational | Hardware failure disrupts scoring/accreditation during competition | Medium — operational disruption | TBD | Device redundancy planning, offline fallback | ICT Committee | Open |
| RSK-18 | Data synchronization conflicts | Technical | Conflicting data from offline/online transitions | Medium — data integrity | TBD | Conflict surfacing rather than silent resolution (see [operating-model.md](operating-model.md#17-offline-and-synchronization-principles)) | ICT Committee / Vendors | Open |
| RSK-19 | Vendor lock-in | Commercialization | Platform architecture ties DepEd to a single vendor without portability | Medium — long-term cost/flexibility | TBD | Data portability and API readiness as commercial-quality requirements | DepEd Leadership | Open |
| RSK-20 | Lack of official policy validation for sports, eligibility, or scoring rules | Product | Rules configured without confirmed authoritative source | High — invalid or contested official outcomes | TBD | No rule implementation without a sourced, confirmed policy | DepEd Leadership / Sports Specialists | Open |

### Risk Categories Represented

Product risks: RSK-01, 02, 04, 11, 14, 20 · Operational risks: RSK-05, 17 · Data risks: RSK-02, 18 · Security risks: RSK-03, 07, 08 · Privacy risks: RSK-09 · Adoption risks: RSK-10, 12 · Technical risks: RSK-06, 13, 16, 18 · Commercialization risks: RSK-19 · AI-related risks: RSK-15

All risks are recorded with status `Open` as this is Phase 0.1; no mitigation has yet been implemented. Likelihood ratings, formal ownership assignment, and mitigation planning are later-phase (risk management) activities.
