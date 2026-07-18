# PMMS Bounded Context Catalog

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md) · [domain-classification.md](domain-classification.md) · [context-map.md](context-map.md) · [data-ownership-map.md](data-ownership-map.md) · [domain-open-decisions.md](domain-open-decisions.md)

This is the authoritative catalog of PMMS bounded contexts. Thirty-four proposed candidate contexts were evaluated against business responsibility, language, ownership, consistency requirements, and lifecycle (not page names, table names, or committee names — see working rules). All thirty-four were retained as distinct contexts because each has a defensible, distinct reason to exist; several boundary questions (e.g., Scoring vs. Official Results, Accreditation vs. Access Validation) were evaluated explicitly rather than assumed, and are recorded as open decisions where a final answer requires stakeholder input.

**Status legend:** `Recommended` — direction is sound and should carry into Phase 0.3, pending the noted validations. `Requires validation` — the context's existence is reasonable but its boundary depends on an unresolved question. No context in this catalog is marked `Deferred`; all 34 are considered in-scope for domain modeling, though not all imply equal implementation priority (see [../00-product/product-scope.md](../00-product/product-scope.md) for release sequencing).

Context IDs (BC-01…BC-34) are catalog identifiers only and must **not** be used as future database, module, or namespace names.

---

## Summary Table

| ID | Name | Classification | Primary Responsibility | Authoritative Data | Main Users | Integrity Level | Offline Relevance | Public Exposure | Status |
|---|---|---|---|---|---|---|---|---|---|
| BC-01 | Platform Administration | Generic | Platform-wide configuration, org onboarding, branding, feature availability | Platform settings, feature flags | Platform/System Admins | Standard | None | None | Recommended |
| BC-02 | Identity and Access | Generic | Accounts, authentication, sessions, credentials, access grants (boundary only) | User accounts, credentials, sessions | All authenticated users | High (security) | Limited (cached session validation) | None | Recommended — detailed RBAC deferred to Phase 0.3 |
| BC-03 | Organization Directory | Supporting | Regions, divisions, districts, schools, partner orgs | Organization hierarchy records | Admins, Schools Division Office | Standard–High | Low (cached reference) | Low (org/school names) | Requires validation — data source (see DD-09) |
| BC-04 | Meet Administration | Core | Meet identity, lifecycle, dates, windows, status | Meet record and configuration | Meet Director, Organizing Committee | High | Low | Medium (identity/dates) | Recommended |
| BC-05 | Committee Operations | Supporting | Committee formation, mandates, membership, task coordination | Committee and membership records | Committee heads/members | Standard | Low–Medium | None | Recommended |
| BC-06 | Delegation Management | Supporting | Delegation identity, membership, officials, participation confirmation | Delegation record and roster composition | Delegation heads, coaches, Secretariat | Standard–High | Low | Low (delegation names) | Requires validation — hierarchy (see [Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy)) |
| BC-07 | Participant Registry | Core | Canonical identity for athletes, coaches, officials, staff; matching, duplicate detection | Person/participant identity record | Secretariat, Accreditation Officers | High | Low | None (restricted) | Requires validation — single vs. separate registries (DD-01) |
| BC-08 | Athlete Registration | Core | Meet-specific athlete registration, sport participation requests, guardian consent | Meet-scoped registration record | Coaches, Delegation heads, Secretariat | High | Low | None | Recommended |
| BC-09 | Eligibility and Clearance | **Core — High-Integrity** | Requirement submission, review, decision, appeals | Eligibility case and decision record | Secretariat/validators, Schools Division Office | Critical | None (no offline finalization) | None | Recommended — rules require DepEd validation ([Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)) |
| BC-10 | Sports Catalog | Core | Sports, disciplines, events, categories, classification, rule-source references | Sport and event definitions | Sports Specialists, Tournament Managers | High | Low (cached) | Medium (names) | Requires validation — master vs. meet-specific split (DD-03); rules must be source-backed ([Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source)) |
| BC-11 | Competition Entries | Core | Entry submission, validation, substitutions, withdrawals, locking | Entry record | Coaches, Tournament Managers | High | Low | Low (aggregate counts) | Recommended |
| BC-12 | Tournament Management | **Core — High-Integrity** | Draws, brackets, heats, lanes, matches, advancement, seeding | Tournament structure and progression state | Tournament Managers, Technical Officials | Critical | Medium (venue read access) | Medium (published brackets/schedule) | Recommended |
| BC-13 | Technical Officials | Supporting | Qualifications, certifications, meet/venue/event assignments, acceptance, conflicts | Assignment records | Tournament Managers, Technical Delegates | High | Medium | Low | Requires validation — identity vs. Participant Registry (DD-02) |
| BC-14 | Venue and Schedule | Supporting | Venues, facility availability, schedule slots, resource conflicts, public schedule | Venue records and schedule slots | Organizing Committee, Tournament Managers | High | Medium | High (public schedule) | Requires validation — boundary vs. Tournament Management (DD-07) |
| BC-15 | Scoring | **Core — High-Integrity** | Score capture, components, timings, measurements, attempts, penalties, revisions | Score records (raw captured facts) | Scorers, Timers, Technical Officials | Critical | High (venue capture) | None | Recommended |
| BC-16 | Official Results | **Core — High-Integrity** | Result assembly, validation, certification, versioning, ranking, publication eligibility | Official Result record | Tournament Managers, Technical Delegates | Critical | None (no offline certification) | High (once published) | Recommended — separation from Scoring confirmed (DD-04) |
| BC-17 | Protest and Appeals | **Core — High-Integrity** | Protest filing, review, decision, appeal, result hold | Protest/appeal case record | Technical Delegates, Delegation heads | Critical | Low | Low (outcome only) | Recommended — authority chain requires DepEd validation ([Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority)) |
| BC-18 | Medal Tally and Team Standings | **Core — High-Integrity (derived)** | Medal awarding, medal mapping, team points, standings | Medal award and tally record | Tally Team, Organizing Committee | Critical | None | High | Recommended — rules require DepEd validation ([Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules)) |
| BC-19 | Accreditation | **Core — High-Integrity** | Accreditation eligibility, credential issuance, QR generation, revocation, reprint | Accreditation credential record | Accreditation Officers | Critical | Low (issuance) | None | Recommended — separation from Access Validation confirmed (DD-05) |
| BC-20 | Access Validation | **Core — High-Integrity, high-volume offline** | QR scan validation, venue/meal/billeting/transport access, check-in/out | Access scan/transaction record | Security, gate staff, committee scanners | High | Critical | None | Recommended |
| BC-21 | Medical Operations | **Supporting — Restricted** | Medical alerts, encounters, injury reports, treatment, referral, emergency response | Medical encounter/incident record | Medical Team | Critical (sensitivity) | High | None | Requires validation — relationship to Eligibility (DD-08) |
| BC-22 | Billeting | Supporting | Facilities, capacity, room assignments, check-in/out, occupancy | Billeting assignment record | Billeting Committee | Standard | Medium | None | Recommended |
| BC-23 | Food Services | Supporting | Meal plans, entitlements, allocation, validation, distribution | Meal entitlement/distribution record | Food Committee | Standard | Medium–High | None | Recommended |
| BC-24 | Transportation | Supporting | Vehicles, drivers, routes, trips, dispatch, boarding | Transport trip record | Transportation Committee | Standard | Medium | Low (advisories) | Recommended |
| BC-25 | Security Operations | Supporting | Deployment, post assignments, incidents, lost/found, alerts | Security incident record | Security Committee | High (safety) | High | None (advisories via Media) | Recommended |
| BC-26 | Finance Operations | Supporting | Budget allocations, expense tracking, cash advances, liquidation | Financial transaction record (committee-level monitoring only) | Finance Committee | High | Low | None | Recommended — explicitly not an accounting-system replacement |
| BC-27 | ICT Service Operations | Supporting | Devices, network, support tickets, result-station readiness, connectivity monitoring | ICT ticket/asset record | ICT Committee | Standard | Medium | None | Recommended |
| BC-28 | Media and Communications | Supporting | Announcements, advisories, press releases, media accreditation references | Announcement/advisory record | Media Committee | Standard–High (accuracy) | Low | High | Recommended |
| BC-29 | Public Information | **Supporting — Non-Authoritative** | Public portal content: schedules, results, medal tally, profiles, advisories | None — consumes approved projections only | Public | Standard (freshness, not source of truth) | N/A | High (this is the public surface) | Recommended |
| BC-30 | Document and Records | Supporting | File metadata, categories, templates, versions, retention, archiving | Document record (metadata; MinIO is storage infrastructure, not the domain) | Secretariat, Auditors | High (institutional record) | Low | Low (published documents only) | Recommended |
| BC-31 | Notifications | Generic | Notification intent, recipient resolution, delivery channels, templates, status | Notification record | All (recipients); system-triggered | Standard | Low (queued) | N/A | Recommended |
| BC-32 | Audit and Compliance | **Generic — Elevated Integrity** | Audit events: actor, action, target, before/after, reason, timestamp | Audit event record | Auditors, DepEd Leadership | Critical (underpins all other integrity domains) | Low (buffered, guaranteed sync) | None | Recommended |
| BC-33 | Reporting and Analytics | **Supporting — Consumes Only** | Operational reports, dashboards, historical analytics, exports | None — read models/projections only | Organizers, Committees, DepEd Leadership | Standard (must not become source of truth) | Low | Low (some outputs feed Public Information) | Recommended |
| BC-34 | Configuration and Reference Data | Generic | Shared reference values, status vocabularies, controlled enumerations, versioned rule references | Shared reference/enumeration record | All contexts (consumers), Admins (maintainers) | Standard–High (feeds many contexts) | Low (cached) | None | Recommended — scope must stay narrow to avoid becoming a miscellaneous dumping ground |

---

## Catalog Entries

Each entry below gives the condensed catalog view (responsibilities, exclusions, ownership, upstream/downstream, aggregates, events, open questions). Full narrative treatment (business owner, terminology, invariants, AI boundaries, risks) for the highest-priority contexts is in [phase-0.2-domain-architecture.md, Section 6](phase-0.2-domain-architecture.md#6-detailed-bounded-context-definitions); this catalog is the quick-reference index for all 34.

### BC-01 — Platform Administration
- **Responsibilities:** Platform configuration, organization onboarding, tenant/deployment configuration, system-level settings, branding, feature availability, platform-wide reference governance.
- **Exclusions:** Meet-specific setup (BC-04), authentication internals (BC-02), sports configuration (BC-10).
- **Authoritative data:** Platform settings, feature flags, org onboarding records.
- **Upstream contexts:** None (top-level).
- **Downstream contexts:** BC-04 Meet Administration (conforms to platform-level policy), most contexts (feature availability).
- **Aggregate candidates:** `PlatformSettings`, `FeatureFlag`.
- **Domain event candidates:** `OrganizationOnboarded`, `FeatureFlagChanged`.
- **Open questions:** Extent of multi-tenant configuration needed pre-launch (see [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)).

### BC-02 — Identity and Access
- **Responsibilities:** User accounts, authentication, login security, sessions, credentials, MFA readiness, access grants (boundary only — see Phase 0.3 note below).
- **Exclusions:** Detailed role/permission/scope architecture (explicitly Phase 0.3), business-domain assignment logic (e.g., "who is a Tournament Manager for Meet X" is a Phase 0.3 assignment concept layered on top of this context's account/credential primitives).
- **Authoritative data:** User accounts, credentials, sessions.
- **Upstream contexts:** None.
- **Downstream contexts:** All contexts requiring actor identity (via Open Host Service — see [context-map.md](context-map.md)).
- **Aggregate candidates:** `UserAccount`, `Credential`, `Session`.
- **Domain event candidates:** `AccountCreated`, `AccountLocked`, `SessionStarted`, `CredentialChanged`.
- **Open questions:** Passkey/2FA scope already present in repository scaffolding — confirm whether it satisfies MFA readiness or requires extension.

### BC-03 — Organization Directory
- **Responsibilities:** Regions, divisions, districts, schools, partner organizations, organizational hierarchy, status, identifiers, contacts.
- **Exclusions:** Meet-specific delegation composition (BC-06), user accounts (BC-02).
- **Authoritative data:** Organization hierarchy records.
- **Upstream contexts:** None (or an external DepEd registry — see DD-09).
- **Downstream contexts:** BC-06 Delegation Management, BC-04 Meet Administration (host organization reference).
- **Aggregate candidates:** `Organization`, `OrganizationHierarchyNode`.
- **Domain event candidates:** `OrganizationRegistered`, `OrganizationStatusChanged`.
- **Open questions:** Whether organization records are locally maintained, imported, or synchronized from a DepEd system of record (DD-09; relates to [Phase 0.1 OD-06](../00-product/open-decisions.md#od-06--school-data-source)).

### BC-04 — Meet Administration
- **Responsibilities:** Meet creation, identity, dates, status, host organization, branding, competition period, registration/publication windows, meet-wide configuration, activation/closure, readiness status.
- **Exclusions:** Detailed tournament structures (BC-12), results (BC-16), eligibility decisions (BC-09), committee internal operations (BC-05).
- **Authoritative data:** Meet record and configuration.
- **Upstream contexts:** BC-01 Platform Administration, BC-03 Organization Directory (host org reference).
- **Downstream contexts:** Nearly every meet-scoped context conforms to Meet Administration's lifecycle state (Open Host Service / Published Language for "is this meet active/closed").
- **Aggregate candidates:** `Meet`.
- **Domain event candidates:** `MeetCreated`, `MeetActivated`, `MeetClosed`, `MeetArchived`.
- **Open questions:** Single vs. concurrent multi-meet support at launch ([Phase 0.1 OD-01](../00-product/open-decisions.md#od-01--initial-deployment-scope), OD-03).

### BC-05 — Committee Operations
- **Responsibilities:** Committee creation, mandates, membership, work assignments, task coordination, deliverables, readiness tracking, operational reports.
- **Exclusions:** Committee-specific domain operations that have their own contexts (Medical → BC-21, Finance → BC-26, Security → BC-25, etc.) — Committee Operations owns the *administrative shell* (who is on the committee, what is its mandate/readiness), not the specialized operational data those committees produce.
- **Authoritative data:** Committee and membership records.
- **Upstream contexts:** BC-04 Meet Administration, BC-02 Identity and Access (member accounts).
- **Downstream contexts:** BC-33 Reporting and Analytics (readiness dashboards).
- **Aggregate candidates:** `Committee`, `CommitteeMembership`.
- **Domain event candidates:** `CommitteeCreated`, `CommitteeMemberAssigned`, `CommitteeReadinessUpdated`.
- **Open questions:** Whether highly specialized committees (Medical, Finance, Security, etc.) require this context's membership shell to feed their own contexts, or maintain independent membership — recommended: Committee Operations owns membership as the single source, specialized contexts reference it (DD-14).

### BC-06 — Delegation Management
- **Responsibilities:** Delegation creation, identity, membership, officials, representatives, school/organization composition, status, participation confirmation, contacts, roster coordination.
- **Exclusions:** Athlete master identity (BC-07), eligibility approval (BC-09), tournament entries (BC-11), accreditation issuance (BC-19).
- **Authoritative data:** Delegation record and roster composition.
- **Upstream contexts:** BC-03 Organization Directory, BC-04 Meet Administration.
- **Downstream contexts:** BC-08 Athlete Registration, BC-18 Medal Tally and Team Standings (delegation-level standings).
- **Aggregate candidates:** `Delegation`, `DelegationMembership`.
- **Domain event candidates:** `DelegationRegistered`, `DelegationConfirmed`.
- **Open questions:** Delegation hierarchy/grouping unit — carried directly from [Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy).

### BC-07 — Participant Registry
- **Responsibilities:** Athlete/coach/delegation-staff identity, person matching, duplicate detection, biographical data, contact and guardian data, person-to-organization association, historical participation identity, identity correction workflow.
- **Exclusions:** Meet-specific registration status (BC-08), eligibility decisions (BC-09), accreditation credentials (BC-19).
- **Authoritative data:** Person/participant identity record.
- **Upstream contexts:** None (or external identity source — see [Phase 0.1 OD-05](../00-product/open-decisions.md#od-05--athlete-identity-source)).
- **Downstream contexts:** BC-08, BC-09, BC-13, BC-19, BC-21 (each references canonical identity rather than duplicating it).
- **Aggregate candidates:** `Participant`.
- **Domain event candidates:** `ParticipantCreated`, `ParticipantMatched`, `ParticipantIdentityCorrected`.
- **Open questions:** Single shared registry vs. separate athlete/coach/official/committee-staff registries (DD-01 — this is the single most consequential open modeling decision in Phase 0.2).

### BC-08 — Athlete Registration
- **Responsibilities:** Meet-specific athlete registration, sport participation requests, registration status, required submissions, athlete-delegation association, guardian consent tracking, registration review, completion, withdrawal.
- **Exclusions:** Eligibility decision logic (BC-09), competition entries and seedings (BC-11), official accreditation issuance (BC-19).
- **Authoritative data:** Meet-scoped registration record.
- **Upstream contexts:** BC-07 Participant Registry, BC-06 Delegation Management, BC-04 Meet Administration.
- **Downstream contexts:** BC-09 Eligibility and Clearance, BC-11 Competition Entries.
- **Aggregate candidates:** `AthleteRegistration`.
- **Domain event candidates:** `AthleteRegistered`, `RegistrationSubmitted`, `RegistrationWithdrawn`.
- **Open questions:** Guardian consent mechanism scope ([Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access)).

### BC-09 — Eligibility and Clearance *(High-Integrity)*
- **Responsibilities:** Eligibility requirements, requirement submission, document review, validation findings, reviewer decisions, medical-clearance status reference, approval, rejection/return, appeals/reconsideration, history, decision evidence, eligibility locks.
- **Exclusions:** The actual eligibility criteria are not defined here — this context defines the *workflow*, not the *rules* (rules require DepEd authority, [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)). Does not hold raw medical records (references BC-21 via ACL, see DD-08).
- **Authoritative data:** Eligibility case and decision record.
- **Upstream contexts:** BC-08 Athlete Registration, BC-30 Document and Records (evidence), BC-21 Medical Operations (clearance status reference only, via ACL).
- **Downstream contexts:** BC-11 Competition Entries, BC-19 Accreditation.
- **Aggregate candidates:** `EligibilityCase`.
- **Domain event candidates:** `EligibilityRequirementsSubmitted`, `EligibilityApproved`, `EligibilityRejected`.
- **Open questions:** Authoritative eligibility rules and approval chain (blocking — [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority)).

### BC-10 — Sports Catalog
- **Responsibilities:** Sports, disciplines, divisions, categories, events, competition classes, age/grade group configuration, team/individual classification, scoring-model references, medal-eligibility configuration, rule-source references, sport-specific terminology.
- **Exclusions:** Actual competition rule content beyond a reference/citation to an authoritative source (BC-10 stores *that* a rule exists and where it comes from, not an invented rule body — see [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source)).
- **Authoritative data:** Sport and event definitions.
- **Upstream contexts:** None (or an external sports-federation reference — deferred integration per [Phase 0.1 product-scope.md](../00-product/product-scope.md#8-deferred-integrations)).
- **Downstream contexts:** BC-11 Competition Entries, BC-12 Tournament Management, BC-18 Medal Tally.
- **Aggregate candidates:** `SportDefinition`, `EventDefinition`.
- **Domain event candidates:** `SportDefinitionPublished`, `EventDefinitionRevised`.
- **Open questions:** Platform-level master data vs. meet-specific configuration vs. hybrid (DD-03).

### BC-11 — Competition Entries
- **Responsibilities:** Athlete/team entry submission, entry limits, event participation, entry validation, substitutions, withdrawals, entry locking, entry conflicts, eligibility dependency, roster confirmation.
- **Exclusions:** Bracket generation (BC-12), score entry (BC-15), medal calculation (BC-18).
- **Authoritative data:** Entry record.
- **Upstream contexts:** BC-08 Athlete Registration, BC-09 Eligibility and Clearance, BC-10 Sports Catalog.
- **Downstream contexts:** BC-12 Tournament Management.
- **Aggregate candidates:** `CompetitionEntry`.
- **Domain event candidates:** `CompetitionEntrySubmitted`, `CompetitionEntryConfirmed`, `EntryLocked`.
- **Open questions:** Athlete multi-sport participation rules (DD-11).

### BC-12 — Tournament Management *(High-Integrity — progression)*
- **Responsibilities:** Tournament formats, draws, brackets, pools, elimination rounds, round-robin structures, heats, lanes, bouts, matches, games, advancement, seeding, tournament scheduling, competition progression.
- **Exclusions:** Score capture (BC-15), result certification (BC-16), venue facility management (BC-14 — Tournament Management determines *what* needs a time/place slot; Venue and Schedule determines *where/when* it fits among competing demands).
- **Authoritative data:** Tournament structure and progression state.
- **Upstream contexts:** BC-11 Competition Entries, BC-10 Sports Catalog, BC-13 Technical Officials, BC-14 Venue and Schedule.
- **Downstream contexts:** BC-15 Scoring, BC-29 Public Information (published brackets/schedule).
- **Aggregate candidates:** `Tournament`, `Match`, `Heat`.
- **Domain event candidates:** `TournamentCreated`, `DrawCompleted`, `MatchScheduled`.
- **Open questions:** Must support sport-specific variation without hard-coding one tournament model (DD-12, DD-13).

### BC-13 — Technical Officials
- **Responsibilities:** Official qualifications, certifications, availability, sport competency, meet/venue/event assignments, acceptance, conflict declarations, attendance, performance/incident notes.
- **Exclusions:** Official personal identity (sourced from BC-07 per DD-02).
- **Authoritative data:** Assignment records.
- **Upstream contexts:** BC-07 Participant Registry, BC-12 Tournament Management (which matches need officials).
- **Downstream contexts:** BC-15 Scoring, BC-16 Official Results (who is authorized to enter/validate).
- **Aggregate candidates:** `OfficialAssignment`.
- **Domain event candidates:** `OfficialAssigned`, `OfficialAssignmentAccepted`, `OfficialConflictDeclared`.
- **Open questions:** DD-02.

### BC-14 — Venue and Schedule
- **Responsibilities:** Venues, competition areas, facility availability, venue readiness, schedule slots, event schedules, resource conflicts, venue assignments, schedule revisions, public schedule publication, disruption records.
- **Exclusions:** Competition structure/progression itself (BC-12).
- **Authoritative data:** Venue records and schedule slots.
- **Upstream contexts:** BC-04 Meet Administration, BC-12 Tournament Management (match list requiring slots).
- **Downstream contexts:** BC-29 Public Information.
- **Aggregate candidates:** `Venue`, `Schedule`.
- **Domain event candidates:** `VenueScheduleChanged`, `ScheduleFinalized`.
- **Open questions:** DD-07.

### BC-15 — Scoring *(High-Integrity)*
- **Responsibilities:** Score capture, score components, timings, measurements, attempts, penalties, technical observations, score revisions, source device/encoder, score validation states, scoring audit history.
- **Exclusions:** Sport-specific scoring formulas without approved sources (must reference BC-10's rule-source, never invent one); official result assembly (BC-16).
- **Authoritative data:** Score records (raw captured facts).
- **Upstream contexts:** BC-12 Tournament Management (the match/heat being scored), BC-13 Technical Officials (who is entering).
- **Downstream contexts:** BC-16 Official Results (via recommended Anti-Corruption Layer — see [context-map.md](context-map.md)).
- **Aggregate candidates:** `ScoreRecord`.
- **Domain event candidates:** `ScoreRecorded`, `ScoreCorrected`, `ScoreValidated`.
- **Open questions:** None blocking; scoring-model specifics depend on BC-10 rule sourcing.

### BC-16 — Official Results *(High-Integrity)*
- **Responsibilities:** Result assembly, validation, certification, versioning, ranking, placement, qualification, advancement confirmation, publication eligibility, corrections, supersession, finalization, official result documents.
- **Exclusions:** Raw score capture (BC-15); protest adjudication itself (BC-17, though results can be placed on hold by BC-17).
- **Authoritative data:** Official Result record.
- **Upstream contexts:** BC-15 Scoring.
- **Downstream contexts:** BC-17 Protest and Appeals, BC-18 Medal Tally, BC-29 Public Information, BC-33 Reporting and Analytics.
- **Aggregate candidates:** `OfficialResult`.
- **Domain event candidates:** `ResultGenerated`, `ResultCertified`, `ResultPublished`.
- **Open questions:** Result versioning/correction authority (DD-15).

### BC-17 — Protest and Appeals *(High-Integrity)*
- **Responsibilities:** Protest filing, eligibility to protest, evidence, filing deadlines, review assignment, decision, appeal, resolution, impacted results, result hold, decision publication, audit history.
- **Exclusions:** Does not itself score or certify results; it acts *on* BC-16 Official Results through a defined hold/correction interface.
- **Authoritative data:** Protest/appeal case record.
- **Upstream contexts:** BC-16 Official Results.
- **Downstream contexts:** BC-16 (result hold/correction trigger), BC-29 Public Information (outcome only).
- **Aggregate candidates:** `ProtestCase`.
- **Domain event candidates:** `ProtestFiled`, `ResultPlacedOnHold`, `ProtestResolved`.
- **Open questions:** Authority chain — [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).

### BC-18 — Medal Tally and Team Standings *(High-Integrity, derived)*
- **Responsibilities:** Medal events, medal awarding, gold/silver/bronze mapping, shared/tied placements, team points, delegation standings, medal reconciliation, tally certification, publication, correction/recalculation, historical snapshots.
- **Exclusions:** Does not own raw scores (BC-15) or certify results (BC-16) — it is strictly derived from BC-16's certified official results.
- **Authoritative data:** Medal award and tally record.
- **Upstream contexts:** BC-16 Official Results, BC-06 Delegation Management (team aggregation).
- **Downstream contexts:** BC-29 Public Information, BC-33 Reporting and Analytics.
- **Aggregate candidates:** `MedalAward`, `TeamStanding`.
- **Domain event candidates:** `MedalAwarded`, `MedalTallyRecalculated`.
- **Open questions:** Tally/team-point rules — [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules), OD-13.

### BC-19 — Accreditation *(High-Integrity)*
- **Responsibilities:** Accreditation eligibility, request, categories, credential issuance, QR credential generation, credential status, revocation, replacement, reprint, validity periods, access privileges, credential audit.
- **Exclusions:** The scan-time validation event itself (BC-20 — accreditation issues the credential; access validation checks it).
- **Authoritative data:** Accreditation credential record.
- **Upstream contexts:** BC-07 Participant Registry, BC-09 Eligibility and Clearance (eligibility-to-accredit dependency).
- **Downstream contexts:** BC-20 Access Validation (via Published Language — credential validity rules).
- **Aggregate candidates:** `AccreditationCredential`.
- **Domain event candidates:** `AccreditationIssued`, `AccreditationRevoked`.
- **Open questions:** Coverage scope — [Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage).

### BC-20 — Access Validation *(High-Integrity, high-volume offline)*
- **Responsibilities:** QR scan validation, venue access, meal access, billeting access, transport access, check-in/out, duplicate scan handling, offline scan validation, synchronization, security alerts, device identity, scan audit.
- **Exclusions:** Credential issuance/lifecycle (BC-19).
- **Authoritative data:** Access scan/transaction record.
- **Upstream contexts:** BC-19 Accreditation (credential validity rules, cached locally for offline scanning).
- **Downstream contexts:** BC-25 Security Operations (anomaly/denial alerts), BC-22/23/24 (venue/meal/transport-specific access checks).
- **Aggregate candidates:** `AccessScan`.
- **Domain event candidates:** `AccessGranted`, `AccessDenied`.
- **Open questions:** DD-05 (relationship to Accreditation), [Phase 0.1 OD-28](../00-product/open-decisions.md#od-28--qr-validation-rules).

### BC-21 — Medical Operations *(Restricted)*
- **Responsibilities:** Medical clearance reference, medical alerts, encounters, injury reports, treatment records, referral, emergency response, athlete fitness status, incident summaries, restricted medical access.
- **Exclusions:** Clinical protocols are not defined by PMMS (must come from medical authorities); eligibility decisions (BC-09) — Medical Operations exposes only a minimal clearance-status reference to Eligibility via ACL.
- **Authoritative data:** Medical encounter/incident record.
- **Upstream contexts:** BC-07 Participant Registry.
- **Downstream contexts:** BC-09 Eligibility and Clearance (status reference only), BC-25 Security Operations (emergency coordination).
- **Aggregate candidates:** `MedicalEncounter`.
- **Domain event candidates:** `MedicalIncidentRecorded`.
- **Open questions:** DD-08; consent/legal basis — [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).

### BC-22 — Billeting
- **Responsibilities:** Facilities, capacity, room assignments, delegation assignments, check-in/out, occupancy, facility issues, incidents, accommodation reports.
- **Exclusions:** Access scan mechanics (BC-20 performs the scan; Billeting owns the assignment being checked against).
- **Authoritative data:** Billeting assignment record.
- **Upstream contexts:** BC-06 Delegation Management.
- **Downstream contexts:** BC-20 Access Validation, BC-33 Reporting.
- **Aggregate candidates:** `BilletingAssignment`.
- **Domain event candidates:** `BilletingAssigned`, `BilletingCheckedIn`.
- **Open questions:** Whether billeting applies to all provincial meets or only some ([Phase 0.1 stakeholder-register.md](../00-product/stakeholder-register.md)).

### BC-23 — Food Services
- **Responsibilities:** Meal plans, schedules, entitlements, dietary considerations, allocation, validation, distribution, supplier delivery records, consumption, exceptions, wastage, committee reports.
- **Exclusions:** Access scan mechanics (BC-20).
- **Authoritative data:** Meal entitlement/distribution record.
- **Upstream contexts:** BC-06 Delegation Management, BC-08 Athlete Registration (headcount).
- **Downstream contexts:** BC-20 Access Validation, BC-33 Reporting.
- **Aggregate candidates:** `MealEntitlement`.
- **Domain event candidates:** `MealEntitlementIssued`, `MealDistributed`.
- **Open questions:** None blocking.

### BC-24 — Transportation
- **Responsibilities:** Vehicles, drivers, routes, trips, delegation assignments, dispatch, boarding, arrival, delays, incidents, fuel/utilization, reports.
- **Exclusions:** Access scan mechanics (BC-20).
- **Authoritative data:** Transport trip record.
- **Upstream contexts:** BC-06 Delegation Management.
- **Downstream contexts:** BC-20 Access Validation, BC-25 Security Operations (incident escalation), BC-33 Reporting.
- **Aggregate candidates:** `TransportTrip`.
- **Domain event candidates:** `TransportTripDispatched`, `TransportIncidentReported`.
- **Open questions:** None blocking.

### BC-25 — Security Operations
- **Responsibilities:** Security deployment, post assignments, access incidents, lost credentials, crowd incidents, lost and found, security alerts, emergency coordination, venue security logs, incident escalation.
- **Exclusions:** Access scan mechanics themselves (BC-20) — Security consumes denial/anomaly signals from Access Validation.
- **Authoritative data:** Security incident record.
- **Upstream contexts:** BC-20 Access Validation, BC-14 Venue and Schedule.
- **Downstream contexts:** BC-28 Media and Communications (public safety advisories), BC-31 Notifications.
- **Aggregate candidates:** `SecurityIncident`.
- **Domain event candidates:** `SecurityIncidentReported`, `SecurityIncidentEscalated`.
- **Open questions:** None blocking.

### BC-26 — Finance Operations
- **Responsibilities:** Budget allocations, categories, expense tracking, requests, obligations, disbursement references, cash advances, liquidation tracking, supporting documents, financial summaries, committee-level monitoring.
- **Exclusions:** Full government accounting/financial-management system functions — PMMS is explicitly not a full accounting replacement (see [Phase 0.1 product boundaries](../00-product/phase-0.1-product-foundation.md#10-product-boundaries)).
- **Authoritative data:** Financial transaction/monitoring record (committee-level, not a general ledger).
- **Upstream contexts:** BC-05 Committee Operations, BC-04 Meet Administration.
- **Downstream contexts:** BC-33 Reporting and Analytics.
- **Aggregate candidates:** `BudgetAllocation`.
- **Domain event candidates:** `BudgetAllocated`, `ExpenseRecorded`.
- **Open questions:** Whether any integration with an actual DepEd financial system is required (relates to [Phase 0.1 product-scope.md, Section 8](../00-product/product-scope.md#8-deferred-integrations)).

### BC-27 — ICT Service Operations
- **Responsibilities:** Devices, workstations, network locations, user support, incident tickets, service requests, result-station readiness, device assignment, connectivity monitoring, backup checks, support logs, technology asset status.
- **Exclusions:** The application/infrastructure itself (this context tracks *operational* ICT support during a meet, not platform engineering).
- **Authoritative data:** ICT ticket/asset record.
- **Upstream contexts:** BC-04 Meet Administration, BC-14 Venue and Schedule.
- **Downstream contexts:** BC-33 Reporting (readiness dashboards).
- **Aggregate candidates:** `SupportTicket`.
- **Domain event candidates:** `SupportTicketOpened`, `ResultStationReadinessConfirmed`.
- **Open questions:** None blocking.

### BC-28 — Media and Communications
- **Responsibilities:** Announcements, advisories, press releases, public stories, media accreditation references, photo/video assets, publication approvals, public information scheduling, emergency communications, media requests.
- **Exclusions:** Direct authorship of the public portal presentation layer (BC-29 renders what BC-28 approves for publication).
- **Authoritative data:** Announcement/advisory record.
- **Upstream contexts:** BC-25 Security Operations (emergency comms triggers), BC-04 Meet Administration.
- **Downstream contexts:** BC-29 Public Information.
- **Aggregate candidates:** `Announcement`.
- **Domain event candidates:** `AnnouncementPublished`.
- **Open questions:** Media accreditation process — [Phase 0.1 stakeholder-register.md](../00-product/stakeholder-register.md).

### BC-29 — Public Information *(Non-Authoritative)*
- **Responsibilities:** Public portal content, public schedules, published results, medal tally views, public athlete/delegation profiles, public venue information, public advisories, public search, caching for high-volume delivery.
- **Exclusions:** Owns no authoritative operational data of its own; must never accept writes that mutate upstream authoritative records.
- **Authoritative data:** None — consumes approved projections only.
- **Upstream contexts:** BC-16 Official Results, BC-18 Medal Tally, BC-14 Venue and Schedule, BC-28 Media and Communications.
- **Downstream contexts:** None (terminal/public-facing).
- **Aggregate candidates:** `PublicationItem` (a projection, not a transactional aggregate).
- **Domain event candidates:** Consumes events from upstream contexts; does not originate business events itself beyond `PublicationItemRefreshed`.
- **Open questions:** Public athlete-profile limits — [Phase 0.1 OD-17](../00-product/open-decisions.md#od-17--public-athlete-profile-limits).

### BC-30 — Document and Records
- **Responsibilities:** File metadata, document categories, official templates, attachments, document versions, retention, archiving, access classification, record export, certificate-generation references, document authenticity metadata.
- **Exclusions:** File binary storage mechanics — MinIO is infrastructure; this context owns metadata, lifecycle, and access classification, not the storage engine itself.
- **Authoritative data:** Document record (metadata).
- **Upstream contexts:** Any context producing documents/evidence (BC-09, BC-16, BC-26, etc.).
- **Downstream contexts:** BC-33 Reporting, BC-32 Audit and Compliance.
- **Aggregate candidates:** `DocumentRecord`.
- **Domain event candidates:** `DocumentUploaded`, `DocumentArchived`.
- **Open questions:** Retention ownership — [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements), DD-23.

### BC-31 — Notifications
- **Responsibilities:** Notification intent, recipient resolution, delivery channels, templates, queuing, delivery status, retry, failure handling, read status, escalation notifications, public vs. private notices.
- **Exclusions:** The business decision that *triggers* a notification (owned by the originating context); Notifications only handles delivery.
- **Authoritative data:** Notification record.
- **Upstream contexts:** Any context emitting a notification-worthy event.
- **Downstream contexts:** None (terminal delivery).
- **Aggregate candidates:** `Notification`.
- **Domain event candidates:** `NotificationQueued`, `NotificationDelivered`, `NotificationFailed`.
- **Open questions:** SMS/email gateway selection — deferred integration ([Phase 0.1 product-scope.md, Section 8](../00-product/product-scope.md#8-deferred-integrations)).

### BC-32 — Audit and Compliance *(Elevated Integrity)*
- **Responsibilities:** Audit events, actor, action, target, before/after references, reason, source, device, timestamp, security-event classification, export, retention, integrity verification.
- **Exclusions:** Does not decide *what* is auditable in business terms (each context determines which of its own actions are audit-worthy); Audit and Compliance defines the common shape and guarantees durability/immutability.
- **Authoritative data:** Audit event record.
- **Upstream contexts:** All contexts (Open Host Service — every context conforms to a published audit-event language).
- **Downstream contexts:** BC-33 Reporting, external audit review.
- **Aggregate candidates:** `AuditEvent`.
- **Domain event candidates:** N/A — Audit and Compliance *consumes* domain events from other contexts rather than emitting its own business events.
- **Open questions:** Audit data ownership when a context is itself under audit (DD-24).

### BC-33 — Reporting and Analytics *(Consumes Only)*
- **Responsibilities:** Operational reports, executive dashboards, committee reports, historical analytics, performance trends, data marts/read models, scheduled reports, exports, post-event analysis, AI-assisted summaries.
- **Exclusions:** Must never become a transactional source of truth for any business decision.
- **Authoritative data:** None — read models/projections only.
- **Upstream contexts:** All contexts (read-only).
- **Downstream contexts:** BC-29 Public Information (some outputs), DepEd Leadership.
- **Aggregate candidates:** None (read models, not transactional aggregates).
- **Domain event candidates:** Consumes events; does not originate them.
- **Open questions:** Data-warehouse timing (DD-25).

### BC-34 — Configuration and Reference Data
- **Responsibilities:** Shared reference values, status vocabularies, geographic references, configurable categories, meet-level settings that are genuinely cross-context, controlled enumerations, versioned rule references.
- **Exclusions:** Context-specific configuration stays local to its owning context; this context holds only values genuinely shared across multiple contexts (guardrail against becoming a "miscellaneous data" dumping ground per working rule 20).
- **Authoritative data:** Shared reference/enumeration record.
- **Upstream contexts:** None.
- **Downstream contexts:** Most contexts (as a narrow, versioned, read-mostly Published Language — explicitly not a Shared Kernel; see [context-map.md](context-map.md)).
- **Aggregate candidates:** `ReferenceValue`, `ControlledEnumeration`.
- **Domain event candidates:** `ReferenceValueVersioned`.
- **Open questions:** Shared reference data ownership boundary (DD-22).
