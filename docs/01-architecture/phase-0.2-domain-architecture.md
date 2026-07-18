# PMMS Phase 0.2 — Domain Discovery and Bounded Context Architecture

## 1. Document Control

| Field | Value |
|---|---|
| Document title | PMMS Phase 0.2 — Domain Discovery and Bounded Context Architecture |
| Project | Provincial Meet Management System (PMMS) |
| Phase | Phase 0.2 — Domain Discovery and Bounded Context Architecture |
| Version | 0.2.0 |
| Status | Draft Complete — Pending Domain and Stakeholder Validation |
| Date | 2026-07-14 |
| Intended audience | Software architects, Laravel developers, React developers, Flutter developers, data architects, QA engineers, security engineers, sports specialists, tournament managers, DepEd stakeholders |
| Related documents | [business-capability-map.md](business-capability-map.md), [domain-classification.md](domain-classification.md), [bounded-context-catalog.md](bounded-context-catalog.md), [context-map.md](context-map.md), [domain-glossary.md](domain-glossary.md), [data-ownership-map.md](data-ownership-map.md), [domain-events-catalog.md](domain-events-catalog.md), [workflow-and-command-catalog.md](workflow-and-command-catalog.md), [high-integrity-domain-rules.md](high-integrity-domain-rules.md), [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md), [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md), [domain-open-decisions.md](domain-open-decisions.md), [README.md](README.md), [../00-product/phase-0.1-product-foundation.md](../00-product/phase-0.1-product-foundation.md), [../../.ai/architecture.md](../../.ai/architecture.md), [../../.ai/decisions/ADR-0002-domain-and-bounded-context-architecture.md](../../.ai/decisions/ADR-0002-domain-and-bounded-context-architecture.md) |

### Change History

| Version | Date | Description |
|---|---|---|
| 0.2.0 | 2026-07-14 | Initial Phase 0.2 draft: domain decomposition, bounded context catalog, context map, and open decisions built from the approved Phase 0.1 product foundation. |

---

## 2. Executive Summary

Phase 0.1 established that PMMS is a configurable, multi-meet, enterprise-grade platform spanning the full meet lifecycle — not a registration form or a medal-tally spreadsheet replacement (see [ADR-0001](../../.ai/decisions/ADR-0001-product-foundation.md)). Phase 0.2 exists to answer the question that decision leaves open: **how is that platform decomposed into pieces a team can actually build, reason about, and evolve independently?**

**Why domain decomposition is necessary.** PMMS touches at least 34 distinct areas of business responsibility (see [bounded-context-catalog.md](bounded-context-catalog.md)), spanning identity, eligibility, competition, scoring, logistics, finance, and public communication. Treating this as one undifferentiated data model — one enormous set of tables and one enormous application — would produce a system where a change to, say, meal-distribution logic risks destabilizing eligibility decisions, simply because everything lives in the same undifferentiated space. Bounded contexts give each area of responsibility its own language, its own rules, and its own boundary of change.

**Why PMMS should not be built as one large CRUD application.** A single CRUD application optimizes for the fastest path to a working screen for each entity. It does not protect the fact that an eligibility decision and a meal count have entirely different integrity requirements, different actors, different audit obligations, and different tolerance for offline operation. Undifferentiated CRUD collapses these differences until a defect in a low-stakes area (e.g., a transportation record) can accidentally corrupt a high-stakes one (e.g., an official result) because they share a table, a form, or an unexamined shortcut.

**Why bounded contexts protect scoring, eligibility, results, accreditation, and medal integrity.** These five areas are explicitly named high-integrity domains in [Phase 0.1](../00-product/phase-0.1-product-foundation.md#8-product-principles). Phase 0.2 gives each one (or a small cluster of tightly related ones — see [domain-classification.md](domain-classification.md)) its own bounded context with an explicit authoritative owner, explicit exclusions, and explicit rules for how corrections happen (see [high-integrity-domain-rules.md](high-integrity-domain-rules.md)). This is the architectural expression of "no silent mutation" and "official human validation for official outcomes" from the Phase 0.1 product principles.

**Why committees, public information, and operational logistics need controlled integration.** Sixteen supporting domains (committee operations, medical, billeting, food, transportation, security, finance, ICT, media, public information, document/records, reporting) must coordinate with the core domains and with each other without becoming either isolated silos (which recreates the fragmented-spreadsheet problem Phase 0.1 identified) or an undifferentiated shared database (which recreates the CRUD-collapse problem above). [context-map.md](context-map.md) defines the specific, named relationship pattern for every such interaction.

**Why context boundaries should precede table design and code generation.** A database schema or a set of Eloquent models designed before these boundaries are understood tends to calcify the wrong boundaries — table foreign keys and Eloquent relationships are expensive to unwind once real data and real workflows depend on them. Phase 0.2 exists specifically to make these boundary decisions in a medium (documentation) that is cheap to revise, before Phase 0.3 (roles/permissions) and later architecture phases commit to a medium (schema, code) that is not.

---

## 3. Domain Architecture Goals

| Goal | How this documentation package addresses it |
|---|---|
| Clear business ownership | Every bounded context has a named primary responsibility and exclusions ([bounded-context-catalog.md](bounded-context-catalog.md)) |
| Controlled data authority | Every major data concept has one authoritative context ([data-ownership-map.md](data-ownership-map.md)) |
| Reduced coupling | Explicit relationship patterns (Customer-Supplier, ACL, Published Language) instead of ad hoc integration ([context-map.md](context-map.md)) |
| Consistent language | A single glossary shared across all documents and, eventually, code ([domain-glossary.md](domain-glossary.md)) |
| Protection of high-integrity workflows | Dedicated safeguard rules for 11 high-integrity domains ([high-integrity-domain-rules.md](high-integrity-domain-rules.md)) |
| Independent evolution of sports-specific capabilities | Sports Catalog and Tournament Management are explicitly designed for configuration over hard-coding (DD-03, DD-13 in [domain-open-decisions.md](domain-open-decisions.md)) |
| Support for web and mobile clients | Context boundaries do not assume a single client type; offline-relevant contexts are explicitly flagged ([offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md)) |
| Offline-ready field operations | Scoring and Access Validation are architected around offline-first capture from the outset |
| Public scalability | Public Information is explicitly non-authoritative and isolated from transactional write load ([reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md)) |
| Auditability | Audit and Compliance is a dedicated, elevated-integrity generic domain consumed by every other context |
| Testability | High-integrity safeguards are framed as testable invariants ([high-integrity-domain-rules.md](high-integrity-domain-rules.md)) |
| Commercial product reuse | Master vs. meet-scoped data separation supports the multi-meet operating model from [Phase 0.1](../00-product/operating-model.md) |

---

## 4. Domain Classification

Full classification with rationale is in [domain-classification.md](domain-classification.md). Summary:

- **Core domains (7 groupings, 13 bounded contexts):** Athlete Registration and Identity Resolution, Athlete Eligibility and Clearance, Competition and Tournament Management, Scoring and Official Results, Medal Tally and Team Standings, Accreditation and Access Validation, Meet Operations (Meet Administration only — see the scope note in [domain-classification.md](domain-classification.md) explaining why Committee Operations and Venue/Schedule are classified Supporting rather than folded into an oversized Core "Meet Management").
- **Supporting domains (16 bounded contexts):** Organization Directory, Committee Operations, Delegation Management, Technical Officials, Venue and Schedule, Medical Operations, Billeting, Food Services, Transportation, Security Operations, Finance Operations, ICT Service Operations, Media and Communications, Public Information, Document and Records, Reporting and Analytics.
- **Generic domains (5 bounded contexts):** Platform Administration, Identity and Access, Notifications, Audit and Compliance, Configuration and Reference Data — several of which (Identity and Access, Audit and Compliance) carry elevated security/reliability requirements despite being classified generic, per working rule 16's explicit caution against treating "generic" as unimportant.

---

## 5. Bounded Context Summary

The full 34-row summary table (Context ID, Name, Classification, Primary Responsibility, Authoritative Data, Main Users, Integrity Level, Offline Relevance, Public Exposure, Status) is maintained in [bounded-context-catalog.md, Summary Table](bounded-context-catalog.md#summary-table) to avoid duplicating a large table across two documents. Context IDs BC-01 through BC-34 are catalog identifiers only and must not be encoded into future database, module, or namespace naming.

---

## 6. Detailed Bounded Context Definitions

Full catalog-depth definitions (responsibilities, exclusions, authoritative data, upstream/downstream, aggregate/event candidates, open questions) for **all 34 bounded contexts** are in [bounded-context-catalog.md](bounded-context-catalog.md). This section provides the deeper narrative treatment — business owner placeholder, key terminology, value objects, domain services, commands, invariants, approval boundaries, audit/security classification, offline considerations, reporting needs, AI boundaries, risks, assumptions, and open questions — for the **13 Core bounded contexts**, where this level of detail matters most for downstream phases. Supporting and Generic contexts receive this depth in [bounded-context-catalog.md](bounded-context-catalog.md) at summary grain, which is proportionate to their classification (see [domain-classification.md](domain-classification.md)).

### BC-04 — Meet Administration
- **Purpose:** Own the identity and lifecycle of a meet — the backbone every other meet-scoped context conforms to.
- **Business owner role (to be identified):** Meet Director.
- **Key terminology:** Meet, Host, Activation, Closure — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `Meet`.
- **Entity candidates:** Registration window, publication window (may be value objects or sub-entities of `Meet` — TBD in later phase).
- **Value object candidates:** `MeetCode`, `ScheduleSlot` (shared concept with BC-14).
- **Domain service candidates:** Meet readiness evaluation (aggregates signals from other contexts — advisory, not authoritative over those contexts' own data).
- **Command candidates:** `CreateMeet`, `ActivateMeet`, `CloseMeet` (see [workflow-and-command-catalog.md](workflow-and-command-catalog.md)).
- **Domain event candidates:** `MeetCreated`, `MeetActivated`, `MeetClosed`, `MeetArchived`.
- **Business invariant candidates:** A meet cannot be activated without minimum configuration completeness; a meet cannot close with open protests (WF-14) or an unpublished medal tally (WF-15).
- **Approval/validation boundaries:** Activation and closure require Meet Director authority.
- **Audit requirements:** Every lifecycle transition logged with actor and timestamp.
- **Security and privacy classification:** Low sensitivity; high institutional-record importance.
- **Offline considerations:** None — lifecycle transitions require connectivity.
- **Reporting needs:** Feeds meet readiness dashboard ([reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md)).
- **AI-assistance boundaries:** AI may summarize readiness signals (advisory); must not activate or close a meet.
- **Risks:** Premature activation before committees are ready.
- **Assumptions:** Single active meet is sufficient for initial release (see [Phase 0.1 OD-01](../00-product/open-decisions.md#od-01--initial-deployment-scope)).
- **Open questions:** Concurrent multi-meet support timing.

### BC-07 — Participant Registry
- **Purpose:** Provide the single canonical identity for every person known to PMMS, regardless of the role(s) they hold across meets.
- **Business owner role (to be identified):** Secretariat / identity steward.
- **Key terminology:** Participant, Athlete, Coach — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `Participant`.
- **Entity candidates:** Identity-correction history entries.
- **Value object candidates:** `ParticipantIdentifier`.
- **Domain service candidates:** Participant identity matching, duplicate registration detection (both explicitly AI-advisory-eligible, human-confirmed — see [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction)).
- **Command candidates:** (implicit within registration flow — see BC-08).
- **Domain event candidates:** `ParticipantCreated`, `ParticipantMatched`, `ParticipantIdentityCorrected`.
- **Business invariant candidates:** No two `Participant` records should represent the same real person without an active review flag (the platform cannot fully prevent this, but must make it detectable and reviewable, never silently ignored).
- **Approval/validation boundaries:** Identity creation may be relatively open; identity correction/merge requires reviewer confirmation.
- **Audit requirements:** High — every creation and correction logged.
- **Security and privacy classification:** High (PII); restricted access.
- **Offline considerations:** Cached identity reference for offline scan validation (BC-20 dependency); no offline identity creation/correction.
- **Reporting needs:** Duplicate-rate metrics feed [Phase 0.1 KPIs](../00-product/success-framework.md#9-proposed-kpis).
- **AI-assistance boundaries:** Duplicate-detection suggestions allowed (advisory); auto-merge prohibited.
- **Risks:** RSK-02 (duplicate athlete records) from [Phase 0.1](../00-product/assumptions-constraints-risks.md).
- **Assumptions:** Coach/school-submitted identity is the initial source of truth (see [Phase 0.1 OD-05](../00-product/open-decisions.md#od-05--athlete-identity-source)).
- **Open questions:** DD-01, DD-02 in [domain-open-decisions.md](domain-open-decisions.md).

### BC-08 — Athlete Registration
- **Purpose:** Manage the meet-specific act of registering an athlete for participation, distinct from both identity (BC-07) and eligibility decision (BC-09).
- **Business owner role (to be identified):** Secretariat.
- **Key terminology:** Registration, Withdrawal — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `AthleteRegistration`.
- **Value object candidates:** `AthleteNumber`.
- **Domain service candidates:** Registration completeness evaluation.
- **Command candidates:** `RegisterAthlete`.
- **Domain event candidates:** `AthleteRegistered`, `RegistrationSubmitted`, `RegistrationWithdrawn`.
- **Business invariant candidates:** A registration cannot be submitted without a resolved delegation and participant identity.
- **Approval/validation boundaries:** Secretariat review before eligibility case creation.
- **Audit requirements:** Standard.
- **Security and privacy classification:** Medium.
- **Offline considerations:** Low — pre-meet administrative workflow.
- **Reporting needs:** Registration completion-rate KPI.
- **AI-assistance boundaries:** Missing-requirement detection allowed (advisory).
- **Risks:** Guardian consent gaps for minors.
- **Assumptions:** Registration precedes eligibility review, not concurrent.
- **Open questions:** [Phase 0.1 OD-16](../00-product/open-decisions.md#od-16--parent-or-guardian-access).

### BC-09 — Eligibility and Clearance *(High-Integrity)*
- **Purpose:** Provide a controlled, auditable workflow for determining whether a registered athlete may compete.
- **Business owner role (to be identified):** Blocked pending [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority).
- **Key terminology:** Eligibility Case, Clearance — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `EligibilityCase`.
- **Value object candidates:** `DecisionReason`.
- **Domain service candidates:** Eligibility completeness evaluation (advisory).
- **Command candidates:** `SubmitEligibilityCase`, `ApproveEligibility`, `RejectEligibility`.
- **Domain event candidates:** `EligibilityRequirementsSubmitted`, `EligibilityApproved`, `EligibilityRejected`.
- **Business invariant candidates:** A case cannot be approved without complete required evidence; the approver cannot be the submitter.
- **Approval/validation boundaries:** **Critical** — see [high-integrity-domain-rules.md](high-integrity-domain-rules.md#eligibility--bc-09).
- **Audit requirements:** Critical.
- **Security and privacy classification:** High.
- **Offline considerations:** None — never finalized offline.
- **Reporting needs:** Eligibility validation turnaround-time KPI, eligibility progress read model.
- **AI-assistance boundaries:** Document-completeness checks allowed (advisory); **AI must never approve/reject eligibility**.
- **Risks:** RSK-01, RSK-20 from [Phase 0.1](../00-product/assumptions-constraints-risks.md).
- **Assumptions:** Human validation is non-negotiable (confirmed Phase 0.1 principle).
- **Open questions:** **Blocking** — [Phase 0.1 OD-07](../00-product/open-decisions.md#od-07--eligibility-authority); DD-08.

### BC-10 — Sports Catalog
- **Purpose:** Define, in a source-backed and configurable way, the sports/events/categories PMMS supports.
- **Business owner role (to be identified):** Sports Specialists.
- **Key terminology:** Sport, Discipline, Event, Category — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `SportDefinition`, `EventDefinition`.
- **Value object candidates:** `CompetitionCategory`, `AgeCategory`, `EventCode`.
- **Domain service candidates:** None distinct — primarily reference-data management.
- **Command candidates:** (definition publishing, not yet named as a distinct command in [workflow-and-command-catalog.md](workflow-and-command-catalog.md) — a Phase 0.3+ addition).
- **Domain event candidates:** `SportDefinitionPublished`.
- **Business invariant candidates:** A published definition version is immutable; changes create a new version.
- **Approval/validation boundaries:** Definitions require sports-specialist sign-off before publication.
- **Audit requirements:** Standard, elevated for rule-source citation accuracy.
- **Security and privacy classification:** Low.
- **Offline considerations:** Cached reference, low offline write relevance.
- **Reporting needs:** Public sport/event catalog listing.
- **AI-assistance boundaries:** AI must not invent or infer sport rules; may assist in formatting/organizing sourced rule references.
- **Risks:** RSK-11, RSK-20 from [Phase 0.1](../00-product/assumptions-constraints-risks.md).
- **Assumptions:** None assumed — rules are explicitly deferred to authoritative sources.
- **Open questions:** **Blocking** — [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source); DD-03, DD-13.

### BC-11 — Competition Entries
- **Purpose:** Manage the submission and validation of entries into specific events.
- **Business owner role (to be identified):** Tournament Manager.
- **Key terminology:** Competition Entry, Substitution, Withdrawal — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `CompetitionEntry`.
- **Domain service candidates:** Entry conflict detection.
- **Command candidates:** `SubmitCompetitionEntry`, `LockEntries`.
- **Domain event candidates:** `CompetitionEntrySubmitted`, `CompetitionEntryConfirmed`, `EntryLocked`.
- **Business invariant candidates:** An entry cannot be confirmed for a non-cleared participant (gate on BC-09).
- **Approval/validation boundaries:** Tournament Manager confirms/locks.
- **Audit requirements:** Standard, elevated at lock.
- **Security and privacy classification:** Low–Medium.
- **Offline considerations:** Low.
- **Reporting needs:** Entry-count aggregates feed tournament-progress read model.
- **AI-assistance boundaries:** Entry-limit/conflict detection allowed (advisory).
- **Risks:** Entry submitted ahead of eligibility clearance creating a race condition — must be structurally prevented, not just discouraged.
- **Assumptions:** None blocking.
- **Open questions:** DD-11, DD-12.

### BC-12 — Tournament Management *(High-Integrity — progression)*
- **Purpose:** Own competition structure and progression — draws, brackets, heats, matches — across arbitrary sport-specific formats.
- **Business owner role (to be identified):** Tournament Manager.
- **Key terminology:** Tournament, Draw, Bracket, Pool, Heat, Match, Round, Seed — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `Tournament`, `Match`, `Heat`.
- **Value object candidates:** `Rank`, `Placement` (shared with BC-16 conceptually, though owned there once certified).
- **Domain service candidates:** Tournament seeding, schedule conflict detection, advancement determination.
- **Command candidates:** `GenerateDraw`, `AssignOfficial` (assignment execution lives in BC-13, triggered from here).
- **Domain event candidates:** `TournamentCreated`, `DrawCompleted`, `MatchScheduled`.
- **Business invariant candidates:** A draw cannot be generated from unlocked entries; advancement must derive only from certified results (BC-16), not raw scores.
- **Approval/validation boundaries:** Draw publication requires Tournament Manager confirmation; sport-specific seeding rules require sports-specialist sign-off.
- **Audit requirements:** High.
- **Security and privacy classification:** Low sensitivity, high competition-integrity importance.
- **Offline considerations:** Medium — published structure cached at venue level.
- **Reporting needs:** Tournament progress read model, public bracket/schedule display.
- **AI-assistance boundaries:** Seeding/scheduling recommendations allowed (advisory); draw finalization requires human confirmation.
- **Risks:** Hard-coded single tournament model (named smell in Section 20) — mitigated by DD-13's configuration-first recommendation.
- **Assumptions:** None blocking.
- **Open questions:** DD-07, DD-12, DD-13.

### BC-15 — Scoring *(High-Integrity)*
- **Purpose:** Capture raw, evidentiary scoring facts at the point of competition, with strong offline tolerance.
- **Business owner role (to be identified):** Technical Officials (Scorers/Timers).
- **Key terminology:** Score, Score Record — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `ScoreRecord`.
- **Value object candidates:** `ScoreValue`, `Measurement`, `Duration`, `DeviceIdentity`.
- **Domain service candidates:** None distinct — primarily capture and versioned correction.
- **Command candidates:** `RecordScore`, `ValidateScore`.
- **Domain event candidates:** `ScoreRecorded`, `ScoreCorrected`, `ScoreValidated`.
- **Business invariant candidates:** The validator of a score cannot be its original entering actor (separation of duties).
- **Approval/validation boundaries:** **Critical** — see [high-integrity-domain-rules.md](high-integrity-domain-rules.md#scoring--bc-15).
- **Audit requirements:** Critical.
- **Security and privacy classification:** Medium sensitivity, critical evidentiary importance.
- **Offline considerations:** **Critical** — see [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md#bc-15-scoring-critical-offline-priority).
- **Reporting needs:** None public directly (raw scores are not published).
- **AI-assistance boundaries:** AI must never alter a score.
- **Risks:** RSK-03, RSK-06 (connectivity failure) from [Phase 0.1](../00-product/assumptions-constraints-risks.md).
- **Assumptions:** Devices at venues can support offline capture (requires [Phase 0.1 CON-11](../00-product/assumptions-constraints-risks.md#2-constraints) validation).
- **Open questions:** DD-04.

### BC-16 — Official Results *(High-Integrity)*
- **Purpose:** Assemble, certify, version, and control publication of official competition outcomes.
- **Business owner role (to be identified):** Blocked pending [Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain).
- **Key terminology:** Result, Official Result, Published Result, Placement, Ranking, Advancement — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `OfficialResult`.
- **Value object candidates:** `ResultStatus`, `Rank`, `Placement`, `PublicationStatus`.
- **Domain service candidates:** Result ranking, publication eligibility evaluation.
- **Command candidates:** `CertifyResult`, `PublishResult`.
- **Domain event candidates:** `ResultGenerated`, `ResultCertified`, `ResultPublished`.
- **Business invariant candidates:** Certification requires all validated score inputs; publication requires no active protest hold.
- **Approval/validation boundaries:** **Critical** — see [high-integrity-domain-rules.md](high-integrity-domain-rules.md#official-results--bc-16).
- **Audit requirements:** Critical.
- **Security and privacy classification:** Low sensitivity, critical institutional-record importance.
- **Offline considerations:** None.
- **Reporting needs:** Live result board, medal tally input, public portal feed.
- **AI-assistance boundaries:** AI must never certify a result.
- **Risks:** RSK-03, RSK-04 from [Phase 0.1](../00-product/assumptions-constraints-risks.md).
- **Assumptions:** None blocking beyond OD-08.
- **Open questions:** **Blocking** — OD-08; DD-04, DD-06, DD-15, DD-17.

### BC-17 — Protest and Appeals *(High-Integrity)*
- **Purpose:** Provide the platform's designated, evidence-based correction mechanism for disputed results.
- **Business owner role (to be identified):** Blocked pending [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).
- **Key terminology:** Protest, Appeal — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `ProtestCase`.
- **Domain service candidates:** None distinct.
- **Command candidates:** `FileProtest`, `ResolveProtest`.
- **Domain event candidates:** `ProtestFiled`, `ResultPlacedOnHold`, `ProtestResolved`.
- **Business invariant candidates:** A protest references an immutable result version; only one active hold per result at a time.
- **Approval/validation boundaries:** **Critical**.
- **Audit requirements:** Critical.
- **Security and privacy classification:** Medium sensitivity (evidentiary), critical integrity importance.
- **Offline considerations:** Low — adjudication not finalized offline.
- **Reporting needs:** Protest outcome summary (not full case detail) feeds public information.
- **AI-assistance boundaries:** AI must never resolve a protest.
- **Risks:** Undefined authority chain delays resolution, extending result-hold periods.
- **Assumptions:** None blocking beyond OD-09.
- **Open questions:** **Blocking** — OD-09; DD-06.

### BC-18 — Medal Tally and Team Standings *(High-Integrity, derived)*
- **Purpose:** Compute and publish medal and team-standing outcomes strictly derived from certified results.
- **Business owner role (to be identified):** Blocked pending [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules).
- **Key terminology:** Medal Award, Medal Tally, Team Standing — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `MedalAward`, `TeamStanding`.
- **Value object candidates:** `MedalType`.
- **Domain service candidates:** Medal calculation, team standing calculation.
- **Command candidates:** `RecalculateMedalTally`.
- **Domain event candidates:** `MedalAwarded`, `MedalTallyRecalculated`.
- **Business invariant candidates:** Tally computation may only read from BC-16's certified/published results, never raw scores or manual entry.
- **Approval/validation boundaries:** **Critical**, elevated confirmation step for post-publication corrections (DD-16).
- **Audit requirements:** Critical.
- **Security and privacy classification:** Low sensitivity, critical public-trust importance.
- **Offline considerations:** None.
- **Reporting needs:** This *is* one of the platform's primary public read models.
- **AI-assistance boundaries:** AI must never award a medal.
- **Risks:** RSK-04 from [Phase 0.1](../00-product/assumptions-constraints-risks.md).
- **Assumptions:** None blocking beyond OD-12/OD-13.
- **Open questions:** **Blocking** — OD-12, OD-13; DD-16.

### BC-19 — Accreditation *(High-Integrity)*
- **Purpose:** Issue and manage the credentials that authorize physical/system access for participants and staff.
- **Business owner role (to be identified):** Accreditation Officer.
- **Key terminology:** Accreditation, Credential, QR Token — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `AccreditationCredential`.
- **Value object candidates:** `AccreditationNumber`, `QRCredentialToken`.
- **Domain service candidates:** Accreditation entitlement evaluation.
- **Command candidates:** `IssueAccreditation`, `RevokeCredential`.
- **Domain event candidates:** `AccreditationIssued`, `AccreditationRevoked`.
- **Business invariant candidates:** A credential cannot be issued without resolved identity (BC-07) and, where required, cleared eligibility (BC-09).
- **Approval/validation boundaries:** **Critical**.
- **Audit requirements:** High.
- **Security and privacy classification:** Medium sensitivity (identity-linked), critical access-control importance.
- **Offline considerations:** Low for issuance; the cached credential set it publishes to BC-20 is critical (see [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md)).
- **Reporting needs:** Accreditation issuance-status read model.
- **AI-assistance boundaries:** AI must never issue or revoke a credential.
- **Risks:** RSK-08 (QR credential misuse) from [Phase 0.1](../00-product/assumptions-constraints-risks.md).
- **Assumptions:** None blocking beyond OD-14.
- **Open questions:** [Phase 0.1 OD-14](../00-product/open-decisions.md#od-14--accreditation-coverage); DD-05.

### BC-20 — Access Validation *(High-Integrity, high-volume offline)*
- **Purpose:** Validate presented credentials against a locally cached authority at high volume, tolerant of extended offline operation.
- **Business owner role (to be identified):** Security Committee / gate operations lead.
- **Key terminology:** Access Scan — see [domain-glossary.md](domain-glossary.md).
- **Aggregate candidates:** `AccessScan`.
- **Value object candidates:** `DeviceIdentity` (shared concept with BC-15).
- **Domain service candidates:** Offline synchronization conflict resolution, access validation itself.
- **Command candidates:** `ValidateAccess`.
- **Domain event candidates:** `AccessGranted`, `AccessDenied`.
- **Business invariant candidates:** A scan decision is always made against the most recent locally available credential-validity cache; revocations propagate with the highest sync priority.
- **Approval/validation boundaries:** High — no human "approval" step in the normal case (the scan itself is the validation), but manual override for denied-in-error cases requires a distinct authorized role and reason capture.
- **Audit requirements:** High.
- **Security and privacy classification:** Medium sensitivity, critical operational-security importance.
- **Offline considerations:** **Critical** — the platform's primary high-volume offline workflow (see [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md#bc-20-access-validation-critical-offline-priority)).
- **Reporting needs:** Aggregate access counts only (not individual scan detail) for logistics dashboards.
- **AI-assistance boundaries:** AI may flag anomalous scan patterns (advisory) for Security Operations; must not itself deny/grant access outside the defined validation rule.
- **Risks:** RSK-08 from [Phase 0.1](../00-product/assumptions-constraints-risks.md); stale-cache false-accept window.
- **Assumptions:** Devices can maintain a local cache for the expected offline duration at venues.
- **Open questions:** [Phase 0.1 OD-28](../00-product/open-decisions.md#od-28--qr-validation-rules); DD-05, DD-19.

---

## 7. Context Map

Full relationship matrix, written narrative, Anti-Corruption Layer justifications, and six Mermaid diagrams (high-level landscape; participant/eligibility flow; tournament/scoring/results/medal flow; accreditation/access-validation flow; public publication flow; logistics/committee coordination flow) are in [context-map.md](context-map.md). Shared Kernel is avoided everywhere except one deliberately narrow, versioned-Published-Language treatment of Configuration and Reference Data (BC-34) — see the explicit justification in that document.

---

## 8. Data Ownership Principles

The complete data-ownership table (concept, authoritative context, steward, consumers, allowed copies/projections, sensitivity, retention, public exposure, offline replication, correction authority, audit requirement) covering 34 major data concepts is in [data-ownership-map.md](data-ownership-map.md). The governing principles, all upheld throughout that table:

- Every major concept has one authoritative owner.
- Other contexts may hold references, projections, caches, or snapshots — never a second authoritative copy.
- Public views (BC-29) never become authoritative.
- Reporting databases (BC-33) never become transactional sources of truth.
- Medal Tally (BC-18) does not own raw scores — it derives strictly from Official Results (BC-16).
- Official Results (BC-16) owns certified results.
- Scoring (BC-15) owns captured scoring facts.
- Eligibility (BC-09) owns eligibility decisions.
- Participant Registry (BC-07) owns canonical participant identity.
- Athlete Registration (BC-08) owns meet-specific registration.
- Accreditation (BC-19) owns credentials.
- Access Validation (BC-20) owns scan transactions.
- Organization Directory (BC-03) owns organization identities.
- Meet Administration (BC-04) owns meet identity and lifecycle.
- Sports Catalog (BC-10) owns approved sport and event definitions.
- Tournament Management (BC-12) owns competition structures.
- Document and Records (BC-30) owns document metadata and retention behavior.

All of these hold as stated against the repository evidence available at Phase 0.2 (a fresh Laravel/React starter kit with no PMMS domain code yet) — none required adjustment from the prompt's suggested defaults.

---

## 9. Transaction and Consistency Boundaries

These are conceptual consistency requirements only — no technical transaction, database isolation level, or queue mechanism is defined here.

### Strong Consistency Candidates
Score validation within one scoring record · Official result certification · Result version supersession · Eligibility approval state transition · Accreditation issuance and revocation (propagation to Access Validation is treated as eventual under offline conditions, but the issuance/revocation decision itself is strong) · Medal tally update from finalized results · Entry lock · Tournament progression · Protest result hold · Meet closure.

### Eventual Consistency Candidates
Public dashboards · Notifications · Analytics · Search indexes · Media displays · Public schedules · Historical projections · Mobile synchronization · Committee dashboards.

This split is already reflected in the Consistency column of [context-map.md, Relationship Matrix](context-map.md#relationship-matrix) for every specific relationship, rather than restated generically here.

---

## 10. High-Integrity Domains

Full dedicated treatment (why integrity is critical, required separation of duties, required evidence, required auditability, allowed correction pattern, prohibited silent changes, publication restrictions, retention expectations, AI limitations) for all 11 high-integrity domains — Participant Identity, Athlete Registration, Eligibility, Competition Entries, Tournament Progression, Scoring, Official Results, Protests and Appeals, Medal Tally, Accreditation, Medical Information, plus Financial Records and Audit History — is in [high-integrity-domain-rules.md](high-integrity-domain-rules.md), built from 15 common safeguard principles applied consistently across every domain.

---

## 11. Aggregate Candidate Analysis

| Aggregate Candidate | Context | Purpose | Consistency Boundary | Likely Root | Invariant Candidates | Referenced External Concepts | Risk of Oversizing | Validation Needed |
|---|---|---|---|---|---|---|---|---|
| `Meet` | BC-04 | Represent one bounded meet | Meet-level lifecycle state | Itself | Cannot activate incomplete config | Organization (BC-03) | Low | None blocking |
| `Committee` | BC-05 | Represent a functional committee | Committee membership/mandate | Itself | Cannot assign member without valid account | Meet (BC-04), User Account (BC-02) | Low | DD-14 |
| `Delegation` | BC-06 | Represent a participating group | Delegation roster composition | Itself | Cannot confirm without valid organization | Organization (BC-03) | Medium — depends on hierarchy resolution | DD-10 |
| `Participant` | BC-07 | Canonical person identity | Identity + correction history | Itself | No unreviewed duplicate | None (root of identity) | **High if it absorbs role-specific data** — must stay identity-only, with role-specific data owned by consuming contexts | DD-01 |
| `AthleteRegistration` | BC-08 | Meet-scoped registration | Registration lifecycle | Itself | Cannot submit without resolved participant + delegation | Participant (BC-07), Delegation (BC-06) | Low | None blocking |
| `EligibilityCase` | BC-09 | Eligibility decision record | Case lifecycle + decision | Itself | Approver ≠ submitter | Registration (BC-08), Document (BC-30), Medical status flag (BC-21 via ACL) | Low | OD-07 |
| `SportDefinition` / `EventDefinition` | BC-10 | Versioned sport/event rules reference | Per-version immutability | Itself (each version) | Published version immutable | None | Low | OD-10, DD-03 |
| `CompetitionEntry` | BC-11 | Entry into a specific event | Entry lifecycle | Itself | Cannot confirm without cleared eligibility | Registration (BC-08), Eligibility (BC-09), Event (BC-10) | Medium — team vs. individual modeling (DD-12) | DD-11, DD-12 |
| `Tournament` | BC-12 | Competition structure for an event | Structure + progression state | Itself | Draw immutable once published (absent exception process) | Entries (BC-11), Event (BC-10) | **High** if it absorbs venue/schedule concerns — must stay structure/progression-only | DD-07, DD-13 |
| `Match` / `Heat` | BC-12 | A single competitive unit | Match-level state | Tournament (as parent) or standalone — TBD | Cannot score without valid officials assignment | Officials (BC-13), Venue/Schedule (BC-14) | Low | None blocking |
| `OfficialAssignment` | BC-13 | Official-to-match/venue assignment | Assignment lifecycle | Itself | No double-booking | Participant (BC-07), Match (BC-12) | Low | DD-02 |
| `Schedule` | BC-14 | Venue/time allocation | Schedule-slot lifecycle | Itself | No double-booked slot | Venue, Match (BC-12) | Low | DD-07 |
| `ScoreRecord` | BC-15 | Raw captured scoring facts | Per-match score capture | Itself | Validator ≠ entering actor | Match (BC-12), Official (BC-13) | Low | None blocking |
| `OfficialResult` | BC-16 | Certified competition outcome | Result version lifecycle | Itself | Certification requires validated inputs | Score Records (BC-15, via ACL) | **High if it absorbs raw scoring detail** — must reference, not duplicate, score data | OD-08, DD-04 |
| `ProtestCase` | BC-17 | Formal challenge record | Case lifecycle | Itself | References immutable result version | Official Result (BC-16) | Low | OD-09, DD-06 |
| `MedalAward` / `TeamStanding` | BC-18 | Derived medal/standing outcome | Per-snapshot derivation | Itself (each snapshot) | Derives only from certified results | Official Result (BC-16), Delegation (BC-06) | Low | OD-12, OD-13 |
| `AccreditationCredential` | BC-19 | Issued credential | Credential lifecycle | Itself | Cannot issue without resolved identity | Participant (BC-07), Eligibility (BC-09) | Low | OD-14, DD-05 |
| `AccessScan` | BC-20 | Single scan transaction | Per-scan event | Itself | Idempotent on resync | Credential (BC-19, cached) | Low | OD-28 |
| `MedicalEncounter` | BC-21 | Medical incident/treatment record | Per-incident | Itself | Restricted access | Participant (BC-07) | Low (but must never be referenced directly by non-Medical contexts) | OD-15, DD-08 |
| `BilletingAssignment` | BC-22 | Accommodation assignment | Assignment lifecycle | Itself | No over-capacity assignment | Delegation (BC-06) | Low | None blocking |
| `MealEntitlement` | BC-23 | Meal entitlement/distribution record | Per-entitlement | Itself | No double-distribution without override | Delegation/Registration | Low | None blocking |
| `TransportTrip` | BC-24 | Trip/dispatch record | Per-trip | Itself | None named | Delegation (BC-06) | Low | None blocking |
| `SecurityIncident` | BC-25 | Security incident record | Per-incident | Itself | None named | Access Validation (BC-20, denial signals) | Low | None blocking |
| `BudgetAllocation` | BC-26 | Committee-level budget/expense record | Per-allocation | Itself | None named | Committee (BC-05) | Low | None blocking |
| `SupportTicket` | BC-27 | ICT service ticket | Per-ticket | Itself | None named | Venue (BC-14) | Low | None blocking |
| `PublicationItem` | BC-29 | Public projection (not a transactional aggregate) | N/A — read model | N/A | Never accepts direct writes | Results, Tally, Schedule, Announcements | N/A (structurally non-authoritative) | DD-17 |
| `DocumentRecord` | BC-30 | Document metadata | Per-document | Itself | Retention governed externally (DD-23) | Storage reference (MinIO — infrastructure) | Low | OD-24, DD-23 |
| `Notification` | BC-31 | Delivery record | Per-notification | Itself | None named | Triggering context's event | Low | None blocking |
| `AuditEvent` | BC-32 | Immutable audit record | Per-event, append-only | Itself | Never modified once written | Every other context (as source) | N/A (immutability is the point) | DD-24 |

---

## 12. Entity and Value Object Candidates

Conceptual value object candidates identified across contexts (no PHP classes, no field definitions):

`MeetCode`, `OrganizationCode`, `ParticipantIdentifier`, `AthleteNumber`, `AccreditationNumber`, `QRCredentialToken`, `CompetitionCategory`, `AgeCategory`, `EventCode`, `ResultStatus`, `ScoreValue`, `Measurement`, `Duration`, `Rank`, `Placement`, `MedalType`, `ScheduleSlot`, `VenueAssignment`, `PublicationStatus`, `DecisionReason`, `DocumentReference`, `AuditActor`, `DeviceIdentity`.

Each is attributed to its owning context in [Section 6](#6-detailed-bounded-context-definitions) above (for Core contexts) or [bounded-context-catalog.md](bounded-context-catalog.md) (for Supporting/Generic contexts). Several — `ScheduleSlot` (BC-04/BC-14), `DeviceIdentity` (BC-15/BC-20), `Rank`/`Placement` (BC-12/BC-16) — are deliberately shared *concepts* used by more than one context; this is not a Shared Kernel violation because each context that uses the concept still owns its own instance of it within its own aggregate boundary, per [context-map.md](context-map.md#why-shared-kernel-is-avoided).

---

## 13. Domain Service Candidates

| Domain Service | Context | Requires Approved Sports/Policy Rules? |
|---|---|---|
| Participant identity matching | BC-07 | No |
| Duplicate registration detection | BC-07 | No |
| Eligibility completeness evaluation | BC-09 | **Yes** — criteria source-backed (OD-07) |
| Entry conflict detection | BC-11 | Partially — limits may be sport-specific |
| Tournament seeding | BC-12 | **Yes** — seeding methodology is sport-specific (OD-10) |
| Schedule conflict detection | BC-14 | No |
| Official assignment conflict detection | BC-13 | No |
| Result ranking | BC-16 | **Yes** — ranking/tie-break rules are sport-specific |
| Advancement determination | BC-12 | **Yes** — format-specific |
| Medal calculation | BC-18 | **Yes** — [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules) |
| Team standing calculation | BC-18 | **Yes** — [Phase 0.1 OD-13](../00-product/open-decisions.md#od-13--team-point-rules) |
| Accreditation entitlement evaluation | BC-19 | Partially — category rules |
| Access validation | BC-20 | No (mechanical rule, not a sports rule) |
| Offline synchronization conflict resolution | BC-15, BC-20 | No |
| Public publication eligibility | BC-16, BC-29 | No (workflow rule, not a sports rule) |
| Meet readiness evaluation | BC-04 | No |

Services marked **Yes** are explicitly flagged as requiring an authorized sports or DepEd source before implementation — they must not be built from invented rules (working rule 11–12).

---

## 14. Domain Event Catalog Summary

The full catalog (trigger, business meaning, payload concepts, consumers, consistency expectation, audit/notification/public/offline relevance, idempotency concern, validation status) for every event across all 34 contexts is in [domain-events-catalog.md](domain-events-catalog.md). It includes all events named in this phase's scope (`MeetCreated` through `MeetArchived`, the full participant/eligibility/accreditation chain, the full competition/scoring/results/protest/medal chain, and operational events across every supporting context) plus several additional conceptual events surfaced during cataloging (e.g., `ParticipantIdentityCorrected`, `ResultPlacedOnHold`) that were not explicitly named in the original candidate list but were necessary to make the correction/hold patterns in [high-integrity-domain-rules.md](high-integrity-domain-rules.md) concrete.

---

## 15. Commands and Workflow Candidates

The full workflow catalog (23 workflows: meet lifecycle, delegation onboarding, athlete registration, eligibility validation, accreditation issuance, competition entry, tournament setup, officials assignment, schedule finalization, score capture, score validation, result certification, result publication, protest resolution, medal tally, access scanning, medical incident, billeting, food distribution, transportation dispatch, security incident, public announcement, meet closure/archiving) and the full command catalog (22 commands with owning context, actor, preconditions, outcome, event, audit requirement, and validation-source requirement) are in [workflow-and-command-catalog.md](workflow-and-command-catalog.md).

---

## 16. Public Data Boundary

- Public information (BC-29) must come from **approved projections** — it is architecturally incapable of reading directly from any other context's authoritative write-store.
- The public portal **cannot** directly modify authoritative records; BC-29 issues no writes upstream to any other context (see [context-map.md, Diagram 5](context-map.md#5-public-publication-flow)).
- Public result publication requires the **certified and publication-eligible** result state (WF-13) — a certified-but-unpublished result is never visible publicly.
- Public athlete profiles must respect privacy limits — see [Phase 0.1 OD-17](../00-product/open-decisions.md#od-17--public-athlete-profile-limits) and the "minimal public data" recommendation there.
- **Medical, eligibility evidence, guardian data, and restricted incident detail must never be publicly exposed**, under any circumstance, including aggregate/de-identified forms unless explicitly approved.
- Public views may be cached; caching does not change the non-authoritative classification of the cached data.
- Public traffic must be isolated from critical administration operations — a public traffic spike (e.g., a medal announcement) must not degrade BC-15 Scoring's or BC-20 Access Validation's ability to capture live data (see [reporting-and-read-model-boundaries.md, "High-Volume Public Delivery and Analytics Isolation"](reporting-and-read-model-boundaries.md#high-volume-public-delivery-and-analytics-isolation)).
- Corrections must propagate through the controlled publication workflow (WF-12/WF-13/WF-14) — never a direct edit to a public projection.

---

## 17. Reporting Boundary

- Transactional contexts remain authoritative; reports consume approved data (see [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md) for the full principle set and 17 candidate read models).
- Committee reports may use context-owned operational projections (e.g., Medical Operations owns and controls its own de-identified incident-summary projection).
- Cross-domain executive reports use read models, not live joins across multiple contexts' authoritative stores.
- Historical snapshots are required for results, medal tally, and eligibility decisions, consistent with the versioning requirements in [high-integrity-domain-rules.md](high-integrity-domain-rules.md).
- Generated documents (result sheets, certificates) include source and version references.
- Report corrections trace back to the source context; analytics never silently rewrites a source record.

---

## 18. Offline and Synchronization Boundary

Ten contexts carry meaningful offline relevance, ranging from **Critical** (Access Validation, Scoring) to Medium (Technical Officials, Venue and Schedule, Transportation, Billeting, ICT Service Operations). Full per-context boundaries (minimum offline data set, local action types, local identity/credential needs, conflict risk, synchronization priority, server authority, duplicate handling, audit requirements, device trust, recovery behavior) are in [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md), which also enumerates the specific actions that must never become final while offline: final eligibility approval, official result certification, final protest resolution, medal tally certification, meet closure, and any destructive administrative change.

---

## 19. AI Assistance by Context

Full AI-boundary treatment appears per-context in [Section 6](#6-detailed-bounded-context-definitions) above (Core contexts) and [bounded-context-catalog.md](bounded-context-catalog.md) (all contexts), consistent with [Phase 0.1 Section 19](../00-product/phase-0.1-product-foundation.md#19-ai-assisted-product-direction). Summary:

**Allowed (advisory only, always human-reviewable):** Duplicate detection suggestions (BC-07) · Missing requirement detection (BC-08, BC-09) · Schedule conflict analysis (BC-14) · Official assignment recommendations (BC-13) · Tournament seeding recommendations (BC-12) · Narrative summaries (BC-33) · Incident classification suggestions (BC-21, BC-25) · Search and knowledge assistance (cross-cutting) · Anomaly alerts (BC-20, BC-26) · Draft reports (BC-33).

**Prohibited everywhere:** Autonomous eligibility approval (BC-09) · Autonomous result certification (BC-16) · Autonomous score alteration (BC-15) · Autonomous disqualification (BC-16) · Autonomous protest resolution (BC-17) · Autonomous medal awarding (BC-18) · Autonomous medical decisions (BC-21) · Hidden/silent modification of any official data, anywhere.

The domain-specific data-access boundary question (which data categories may ever reach an AI service) is tracked as **DD-26** in [domain-open-decisions.md](domain-open-decisions.md), blocked on the Phase 0.1 policy decision [OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions).

---

## 20. Domain Risks and Smells

| Risk/Smell | Status in This Documentation Package |
|---|---|
| An oversized Meet Management context | **Avoided** — Meet Administration (BC-04) is scoped strictly to lifecycle/identity; committee, delegation, venue, and competition concerns each have their own context (see [domain-classification.md, Core Domains note](domain-classification.md#core-domains)) |
| Shared athlete data copied into many domains | **Avoided by design** — Participant Registry (BC-07) is the single canonical owner; consumers hold references only (see [data-ownership-map.md](data-ownership-map.md)) |
| Committee-specific duplicate workflows | **Mitigated** — Committee Operations (BC-05) owns the shared administrative shell; specialized committees own only their distinct operational data (DD-14) |
| Scoring logic embedded in UI | **Prevented by boundary** — Scoring (BC-15) and its domain services are UI-independent; enforcement is an implementation-phase concern to carry forward |
| Medal tally computed from unvalidated scores | **Structurally prevented** — BC-18 reads only from BC-16 certified results, never from BC-15 directly (see [context-map.md](context-map.md)) |
| Public portal querying operational tables directly | **Structurally prevented** — BC-29 has no authoritative data and consumes only approved projections (Section 16) |
| Authentication roles used as business assignments | **Flagged for Phase 0.3** — BC-02 defines account/session boundary only; business assignment (e.g., "Tournament Manager for Meet X") is explicitly deferred, not conflated with login role |
| Hard-coded sports formats | **Mitigated** — DD-13 recommends a configuration-first approach with an explicit extension point |
| Hard-coded committee structures | **Mitigated** — BC-05 treats committees as configurable entities, not fixed enum values |
| One status field handling unrelated workflows | **Avoided** — each aggregate candidate in Section 11 has its own state-transition set, not a shared generic "status" concept |
| Files without ownership metadata | **Avoided** — BC-30 Document and Records exists specifically to own document metadata, distinct from MinIO's storage mechanics |
| Audit logs treated as optional | **Avoided** — BC-32 is classified Generic-but-Critical, consumed as an Open Host Service by every other context |
| Offline clients acting as final authority | **Explicitly prevented** — see the prohibited-offline-finality list in Section 18 and [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) |
| Reports becoming transaction sources | **Structurally prevented** — BC-33 is read-only by design (Section 17) |
| Excessive shared database tables | **Avoided at this phase** — no database design has occurred yet; the data-ownership principles (Section 8) are the safeguard carried into that later phase |
| Cross-context direct writes | **Prevented by the relationship patterns** in [context-map.md](context-map.md) — every cross-context data flow is a named pattern (Customer-Supplier, ACL, Published Language), not an ad hoc write |
| Generic "settings" tables containing critical rules | **Explicitly guarded against** — DD-22 draws a hard line around what BC-34 Configuration and Reference Data may and may not contain |
| AI-generated rules without approved sources | **Prevented by working rules 11–12** and reinforced throughout Sections 6, 13, and 19 |

---

## 21. Alternatives Considered

| Architecture | Assessment |
|---|---|
| Single modular monolith (no explicit bounded contexts) | Rejected — would recreate the CRUD-collapse problem described in the Executive Summary; no clear ownership boundary for high-integrity domains |
| Domain-oriented modular monolith with explicit bounded contexts | **Recommended** (see Section 22) |
| Microservices from the beginning | Rejected for the initial build — introduces distributed-systems complexity (network partitions, eventual-consistency debugging, deployment orchestration) disproportionate to current team size and to a product still validating its domain model through Phase 0.1/0.2. Revisit once specific contexts (most plausibly BC-15 Scoring or BC-20 Access Validation, given their offline/high-volume profile) demonstrate a genuine independent-scaling need. |
| Hybrid modular monolith with independently scalable public and real-time components | Partially adopted in spirit — Public Information (BC-29) is already architecturally isolated from transactional write load (Section 16), which is the most valuable piece of this alternative, without committing to full service extraction |
| Separate operational and public applications | Considered and folded into the recommended direction below — the "separate application" boundary is achieved through BC-29's non-authoritative, read-only design rather than a literal second deployable at this stage |

**Recommended direction:**

> Domain-oriented modular monolith for initial implementation, with explicit bounded contexts, event-driven internal integration, and extraction-ready boundaries.

---

## 22. Recommended Initial Architecture Direction

A **domain-oriented modular monolith** is recommended for PMMS's initial implementation, for the following reasons:

- **Development team size:** Nothing in the repository or Phase 0.1 documentation indicates a large, multi-team engineering organization; a modular monolith lets a small-to-medium team ship coherently without paying distributed-systems coordination overhead prematurely.
- **Commercial-quality goals:** The Phase 0.1 commercial-quality direction (testability, observability, maintainability) is achievable within a well-modularized monolith and does not, by itself, require microservices.
- **Laravel strengths:** Laravel is designed around a modular-monolith-friendly structure (service providers, event/listener architecture, queues via Horizon) that maps naturally onto bounded-context boundaries without requiring service-per-context deployment.
- **Need for fast iteration:** Phase 0.1's open decisions (eligibility rules, sports rules, medal rules) are still being resolved with DepEd; a monolith is far cheaper to restructure as those answers arrive than a set of already-deployed independent services.
- **Need for clear boundaries:** This is satisfied by the bounded-context catalog and context map, not by physical service separation — the boundary discipline documented in this package is designed to be enforceable in a monolith (e.g., via module boundaries, internal event dispatch mimicking the domain events catalog) just as much as across services.
- **Operational complexity:** A modular monolith has one deployment unit, one database connection pool (with clear logical/schema-level separation by context), and one observability surface — materially simpler to operate for a team standing up infrastructure (Redis, Horizon, Reverb, MinIO) for the first time.
- **Future scaling:** "Extraction-ready boundaries" means the bounded contexts are designed so that, if a specific context later needs independent scaling (most plausibly BC-15 Scoring or BC-20 Access Validation, given their offline/high-volume profile, or BC-29 Public Information, given public traffic spikes), it can be extracted because its data ownership and interfaces are already explicit — not because the whole system was pre-emptively split.
- **Offline mobile clients:** Offline-first contexts (BC-15, BC-20) need a well-defined sync boundary regardless of monolith-vs-microservice; this is addressed at the domain-boundary level (Section 18) independent of the deployment topology decision.
- **Public traffic:** Isolated via the non-authoritative, read-model design of BC-29 (Section 16) rather than via physical service separation, which is achievable within a monolith through caching/read-replica patterns deferred to a later architecture phase.
- **Real-time scoring:** Laravel Reverb (already in the confirmed technology direction) supports real-time event broadcasting from within a monolith, without requiring a separate real-time service.
- **Avoidance of premature distributed-system complexity:** Every alternative above that involves service decomposition was rejected specifically because Phase 0.1/0.2 have not yet validated enough of the real-world operating conditions (actual connectivity patterns, actual traffic volume, actual team scaling) to justify that cost.

**This does not finalize deployment topology or microservice extraction plans** — per working rule 10, that remains a later architecture-phase decision. What Phase 0.2 commits to is that *whatever* the deployment topology turns out to be, the bounded-context boundaries defined in this package are the seams along which extraction would occur, should it ever become necessary.

---

## 23. Phase 0.2 Deliverables

1. [phase-0.2-domain-architecture.md](phase-0.2-domain-architecture.md) (this document)
2. [business-capability-map.md](business-capability-map.md)
3. [domain-classification.md](domain-classification.md)
4. [bounded-context-catalog.md](bounded-context-catalog.md)
5. [context-map.md](context-map.md)
6. [domain-glossary.md](domain-glossary.md)
7. [data-ownership-map.md](data-ownership-map.md)
8. [domain-events-catalog.md](domain-events-catalog.md)
9. [workflow-and-command-catalog.md](workflow-and-command-catalog.md)
10. [high-integrity-domain-rules.md](high-integrity-domain-rules.md)
11. [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md)
12. [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md)
13. [domain-open-decisions.md](domain-open-decisions.md)
14. [README.md](README.md) (architecture documentation index)
15. [../../.ai/project-context.md](../../.ai/project-context.md) (updated)
16. [../../.ai/current-phase.md](../../.ai/current-phase.md) (updated)
17. [../../.ai/architecture.md](../../.ai/architecture.md) (new)
18. [../../.ai/domain-glossary.md](../../.ai/domain-glossary.md) (new, condensed AI-facing version)
19. [../../.ai/decisions/ADR-0002-domain-and-bounded-context-architecture.md](../../.ai/decisions/ADR-0002-domain-and-bounded-context-architecture.md) (new)

---

## 24. Phase 0.2 Acceptance Criteria

- [x] Business capability map exists.
- [x] Domains are classified (13 Core, 16 Supporting, 5 Generic bounded contexts across 34 total).
- [x] Bounded contexts are identified (all 34 candidates evaluated, none blindly accepted — see per-context validation notes in [bounded-context-catalog.md](bounded-context-catalog.md)).
- [x] Responsibilities and exclusions are explicit for every context.
- [x] Authoritative data ownership is defined for 34 major data concepts.
- [x] Context relationships are documented (relationship matrix + 6 diagrams).
- [x] High-integrity domains are identified (11 domains with dedicated safeguard rules).
- [x] Aggregate candidates are documented (29 candidates).
- [x] Entity and value object candidates are documented (23 value object candidates).
- [x] Domain service candidates are documented (16 candidates, each flagged for approved-source dependency where applicable).
- [x] Domain events are cataloged (per-context tables covering every named candidate event plus several surfaced during cataloging).
- [x] Commands and workflows are cataloged (23 workflows, 22 commands).
- [x] Reporting boundaries are documented (17 candidate read models).
- [x] Public data boundaries are documented.
- [x] Offline boundaries are documented (10 contexts with offline relevance).
- [x] AI boundaries are documented (per-context and summarized).
- [x] Context risks and smells are documented (18 named risks/smells, each with its mitigation status).
- [x] Open decisions are recorded (26 domain-modeling decisions in [domain-open-decisions.md](domain-open-decisions.md), cross-referenced against 29 Phase 0.1 product-level decisions).
- [x] AI workspace is updated (`.ai/project-context.md`, `.ai/current-phase.md`, `.ai/architecture.md`, `.ai/domain-glossary.md`, ADR-0002).
- [x] No production implementation code is generated.
- [x] No migrations or database schema are created.
- [x] No official rules are invented (every sports/eligibility/scoring/protest/medal rule reference points to a Phase 0.1 or Phase 0.2 open decision requiring DepEd/sports-specialist validation, never an invented value).
- [x] Documents are internally consistent (cross-reference and DD-ID consistency verified — see completion report).

---

## 25. Exit Criteria

Phase 0.2 is complete because:

- The major PMMS business domains are understandable through [business-capability-map.md](business-capability-map.md) and [domain-classification.md](domain-classification.md).
- Every major data concept has a proposed authoritative owner ([data-ownership-map.md](data-ownership-map.md)).
- High-integrity workflows are separated from routine operational workflows, with dedicated safeguard rules ([high-integrity-domain-rules.md](high-integrity-domain-rules.md)).
- Context relationships are documented ([context-map.md](context-map.md)).
- Shared terminology is defined ([domain-glossary.md](domain-glossary.md)).
- Open boundary questions are recorded ([domain-open-decisions.md](domain-open-decisions.md)), cross-referenced against Phase 0.1's still-open product decisions.
- **Phase 0.3 can design users, roles, permissions, scopes, and assignments using the approved context model** — every workflow in [workflow-and-command-catalog.md](workflow-and-command-catalog.md) names its expected actor type, giving Phase 0.3 a concrete list of roles to formalize, even where the specific authority (e.g., who exactly certifies a result) remains blocked on a Phase 0.1 policy decision.
- No implementation was prematurely generated — verified in the completion report's quality checks.

---

## 26. Next Phase

```text
Phase 0.3 — User, Role, Permission, Scope, and Assignment Architecture
```

Phase 0.3 is not started as part of this task.
