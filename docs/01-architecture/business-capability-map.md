# PMMS Business Capability Map

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [domain-classification.md](domain-classification.md) · [bounded-context-catalog.md](bounded-context-catalog.md)

This map decomposes PMMS into business capabilities — *what the organization needs to be able to do*, independent of pages, menus, or database tables (per working rule 16). It is organized into 18 Level 1 capability groups, each broken into Level 2/3 capabilities mapped to an owning bounded context.

---

## 1. Platform Governance
*Owning contexts: [BC-01](bounded-context-catalog.md#bc-01--platform-administration), [BC-34](bounded-context-catalog.md#bc-34--configuration-and-reference-data)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Platform configuration | Manage platform-wide settings and branding | Generic | Platform/System Admins | Medium | Standard | None | None | Recommended |
| Feature availability control | Enable/disable platform capabilities | Generic | Platform Admins | Medium | Standard | None | None | Recommended |
| Reference data governance | Maintain shared enumerations/status vocabularies | Generic | Platform Admins | Medium | Standard–High | Low | None | Requires validation (DD-22) |

## 2. Organization Management
*Owning context: [BC-03](bounded-context-catalog.md#bc-03--organization-directory)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Organization hierarchy maintenance | Maintain regions/divisions/districts/schools | Supporting | Schools Division Office, Admins | High | Standard–High | Low | Low | Requires validation (DD-09) |
| Partner organization onboarding | Register non-DepEd participating organizations | Supporting | Admins | Low | Standard | Low | Low | Requires validation ([Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)) |

## 3. Meet Administration
*Owning context: [BC-04](bounded-context-catalog.md#bc-04--meet-administration)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Meet creation and configuration | Establish a new meet's identity, dates, host | Core | Meet Director, Organizing Committee | Critical | High | Low | Medium | Recommended |
| Meet lifecycle control | Activate, close, archive a meet | Core | Meet Director | Critical | High | None | Low | Recommended |
| Registration/publication window control | Define time-boxed windows for registration and publication | Core | Meet Director, Secretariat | High | High | Low | Medium | Recommended |
| Meet readiness tracking | Aggregate readiness signals across committees | Core | Organizing Committee | High | Standard | Low | None | Recommended |

## 4. Committee Operations
*Owning context: [BC-05](bounded-context-catalog.md#bc-05--committee-operations)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Committee formation | Create and define committee mandates | Supporting | Organizing Committee | Medium | Standard | Low | None | Recommended |
| Committee membership management | Assign members to committees | Supporting | Committee heads | Medium | Standard | Low | None | Recommended |
| Task and deliverable tracking | Track committee-level tasks/readiness | Supporting | Committee heads | Medium | Standard | Medium | None | Recommended |

## 5. Participant and Delegation Management
*Owning contexts: [BC-06](bounded-context-catalog.md#bc-06--delegation-management), [BC-07](bounded-context-catalog.md#bc-07--participant-registry), [BC-08](bounded-context-catalog.md#bc-08--athlete-registration)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Delegation registration | Register a school/grouping as a delegation | Supporting | Delegation heads, Secretariat | High | Standard–High | Low | Low | Requires validation ([Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy)) |
| Participant identity resolution | Establish/match canonical person identity | Core | Secretariat | Critical | High | Low | None | Requires validation (DD-01) |
| Duplicate participant detection | Detect duplicate athlete/coach/official records | Core | Secretariat | High | High | Low | None | Recommended (advisory AI assist) |
| Athlete registration | Register an athlete for a specific meet/sport | Core | Coaches, Delegation heads | Critical | High | Low | None | Recommended |
| Guardian consent tracking | Track consent for minor participants | Core | Coaches, Parents/Guardians | High | High | Low | None | Requires validation ([Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access)) |

## 6. Eligibility and Accreditation
*Owning contexts: [BC-09](bounded-context-catalog.md#bc-09--eligibility-and-clearance-high-integrity), [BC-19](bounded-context-catalog.md#bc-19--accreditation-high-integrity), [BC-20](bounded-context-catalog.md#bc-20--access-validation-high-integrity-high-volume-offline)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Eligibility requirement submission | Submit required eligibility documentation | Core | Coaches, Secretariat | Critical | Critical | None | None | Recommended |
| Eligibility review and decision | Validate and approve/reject eligibility | Core | Secretariat/validators | Critical | Critical | None | None | Blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) |
| Eligibility appeal/reconsideration | Handle disputed eligibility decisions | Core | Schools Division Office | High | Critical | None | None | Requires validation |
| Accreditation issuance | Issue credentials for approved participants/staff | Core | Accreditation Officers | Critical | Critical | Low | None | Requires validation ([Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage)) |
| Access scanning/validation | Validate credentials at points of access | Core | Security, gate staff | High | High | Critical | None | Recommended |

## 7. Sports and Competition Management
*Owning contexts: [BC-10](bounded-context-catalog.md#bc-10--sports-catalog), [BC-11](bounded-context-catalog.md#bc-11--competition-entries), [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression), [BC-13](bounded-context-catalog.md#bc-13--technical-officials)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Sport/event catalog configuration | Define sports, events, categories | Core | Sports Specialists | Critical | High | Low | Medium | Blocked pending [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source) |
| Competition entry submission | Submit an athlete/team for a specific event | Core | Coaches | Critical | High | Low | Low | Recommended |
| Entry validation and locking | Validate and lock entries ahead of competition | Core | Tournament Managers | Critical | High | Low | None | Recommended |
| Draw/bracket/seeding generation | Generate competition structures | Core | Tournament Managers | Critical | Critical | Medium | Medium | Recommended |
| Match/heat scheduling within tournament structure | Assign matches within the competition structure | Core | Tournament Managers | High | High | Medium | Medium | Recommended |
| Officials assignment | Assign technical officials to matches/venues | Supporting | Tournament Managers, Technical Delegates | High | High | Medium | Low | Recommended |

## 8. Scoring and Results
*Owning contexts: [BC-15](bounded-context-catalog.md#bc-15--scoring-high-integrity), [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity), [BC-17](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Score capture | Record raw scores/times/measurements | Core | Scorers, Timers | Critical | Critical | High | None | Recommended |
| Score validation | Validate captured scores before certification | Core | Technical Officials | Critical | Critical | None | None | Recommended |
| Result certification | Certify official results from validated scores | Core | Tournament Managers, Technical Delegates | Critical | Critical | None | High | Blocked pending [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain) |
| Result publication | Release certified results to delegations/public | Core | Tournament Managers | Critical | Critical | None | High | Recommended |
| Protest filing and resolution | File and adjudicate protests | Core | Technical Delegates | Critical | Critical | Low | Low | Blocked pending [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) |

## 9. Medal and Standings Management
*Owning context: [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Medal award computation | Award medals from certified results | Core | Tally Team | Critical | Critical | None | High | Blocked pending [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules) |
| Team/delegation standings computation | Compute overall team points/rankings | Core | Tally Team | Critical | Critical | None | High | Blocked pending [Phase 0.1 OD-13](../00-product/open-decisions.md#od-13--team-point-rules) |
| Tally correction and recalculation | Correct tally following a protest resolution | Core | Tally Team | Critical | Critical | None | High | Recommended |

## 10. Venue and Scheduling
*Owning context: [BC-14](bounded-context-catalog.md#bc-14--venue-and-schedule)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Venue registration and readiness | Register venues and track readiness | Supporting | Organizing Committee | High | High | Medium | Low | Recommended |
| Schedule slot management | Allocate time/place slots to matches | Supporting | Tournament Managers | High | High | Medium | Medium | Requires validation (DD-07) |
| Public schedule publication | Publish the finalized schedule | Supporting | Organizing Committee | High | High | Low | High | Recommended |
| Disruption recording | Record weather/operational disruptions | Supporting | Organizing Committee | Medium | Standard | Medium | Medium | Recommended |

## 11. Technical Officials
*Owning context: [BC-13](bounded-context-catalog.md#bc-13--technical-officials)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Official qualification tracking | Track certifications/competencies | Supporting | Technical Delegates | Medium | High | Low | None | Recommended |
| Official assignment and acceptance | Assign and confirm officiating duties | Supporting | Tournament Managers | High | High | Medium | None | Recommended |
| Conflict-of-interest declaration | Record officiating conflicts | Supporting | Technical Officials | High | High | Low | None | Recommended |

## 12. Medical and Safety
*Owning contexts: [BC-21](bounded-context-catalog.md#bc-21--medical-operations-restricted), [BC-25](bounded-context-catalog.md#bc-25--security-operations)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Medical incident logging | Log medical encounters/injuries | Supporting | Medical Team | High | Critical (sensitivity) | High | None | Blocked pending [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling) |
| Emergency response coordination | Coordinate emergency response | Supporting | Medical Team, Security | Critical | High | High | None | Recommended |
| Security incident management | Log and escalate security incidents | Supporting | Security Committee | High | High | High | None | Recommended |

## 13. Logistics
*Owning contexts: [BC-22](bounded-context-catalog.md#bc-22--billeting), [BC-23](bounded-context-catalog.md#bc-23--food-services), [BC-24](bounded-context-catalog.md#bc-24--transportation)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Billeting assignment | Assign accommodation to delegations | Supporting | Billeting Committee | Medium | Standard | Medium | None | Recommended |
| Meal entitlement and distribution | Manage meal counts and distribution | Supporting | Food Committee | Medium | Standard | Medium–High | None | Recommended |
| Transport dispatch and tracking | Manage vehicle trips and delegation transport | Supporting | Transportation Committee | Medium | Standard | Medium | Low | Recommended |

## 14. Finance
*Owning context: [BC-26](bounded-context-catalog.md#bc-26--finance-operations)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Budget allocation tracking | Track committee-level budget allocations | Supporting | Finance Committee | Medium | High | Low | None | Recommended |
| Expense and liquidation tracking | Track expenses and liquidation status | Supporting | Finance Committee | Medium | High | Low | None | Recommended |

## 15. ICT Operations
*Owning context: [BC-27](bounded-context-catalog.md#bc-27--ict-service-operations)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Device and asset tracking | Track devices/workstations at venues | Supporting | ICT Committee | Medium | Standard | Medium | None | Recommended |
| Support ticket management | Track and resolve on-site ICT issues | Supporting | ICT Committee | High | Standard | Medium | None | Recommended |
| Result-station readiness | Confirm scoring/result infrastructure readiness | Supporting | ICT Committee | High | High | Medium | None | Recommended |

## 16. Media and Public Information
*Owning contexts: [BC-28](bounded-context-catalog.md#bc-28--media-and-communications), [BC-29](bounded-context-catalog.md#bc-29--public-information-non-authoritative)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Announcement/advisory publication | Publish official meet communications | Supporting | Media Committee | High | Standard–High | Low | High | Recommended |
| Public schedule/result display | Present approved public information | Supporting | Public | High | Standard | N/A | High | Recommended |
| Public medal tally display | Present the public-facing medal tally | Supporting | Public | High | Standard | N/A | High | Recommended |

## 17. Records and Compliance
*Owning contexts: [BC-30](bounded-context-catalog.md#bc-30--document-and-records), [BC-32](bounded-context-catalog.md#bc-32--audit-and-compliance-elevated-integrity)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Document lifecycle management | Manage official document metadata/retention | Supporting | Secretariat | Medium | High | Low | Low | Recommended |
| Audit event capture | Capture actor/action/target audit events | Generic | Auditors | Critical | Critical | Low | None | Recommended |
| Audit review and export | Support audit/compliance review | Generic | Auditors | High | High | N/A | None | Recommended |

## 18. Analytics and Decision Support
*Owning context: [BC-33](bounded-context-catalog.md#bc-33--reporting-and-analytics-consumes-only)*

| Capability | Description | Classification | Stakeholders | Criticality | Integrity | Offline | Public | Validation Status |
|---|---|---|---|---|---|---|---|---|
| Operational dashboards | Present committee/meet readiness status | Supporting | Organizing Committee | Medium | Standard | Low | None | Recommended |
| Executive post-event reporting | Summarize meet outcomes for leadership | Supporting | DepEd Leadership | Medium | Standard | N/A | None | Recommended |
| Historical/cross-meet analytics | Analyze trends across meet cycles | Supporting | DepEd Leadership | Low (initially) | Standard | N/A | None | Future scope per [Phase 0.1 product-scope.md](../00-product/product-scope.md#6-future-scope-capabilities) |

---

This capability map deliberately stops at the level of business capability, stakeholder, and owning context. It does not enumerate pages, menus, forms, or database fields, consistent with working rule 16 and the documentation quality requirements for Phase 0.2.
