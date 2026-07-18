# PMMS Domain Open Decisions

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [context-map.md](context-map.md) · [../00-product/open-decisions.md](../00-product/open-decisions.md) (Phase 0.1 product-level open decisions)

This document records unresolved **domain-modeling** questions identified during Phase 0.2 — distinct from the Phase 0.1 product-level open decisions in [../00-product/open-decisions.md](../00-product/open-decisions.md), though several are related and cross-referenced. **No decision below is final;** each records the question, why it matters, the options considered, a recommended direction where one is reasonably defensible from architecture principles alone, and what remains for a decision owner to confirm.

---

### DD-01 — Participant Registry vs. Separate Athlete/Coach/Official Registries
- **Question:** Should PMMS maintain one shared Participant Registry for all person types (athletes, coaches, officials, committee staff), or separate registries per type?
- **Contexts affected:** [BC-07](bounded-context-catalog.md#bc-07--participant-registry), BC-08, BC-13, BC-19, BC-21
- **Why it matters:** This is the single most consequential domain-modeling decision in Phase 0.2 — it determines whether a person who is, e.g., both a coach in one meet and a technical official in another is one identity or two, directly affecting duplicate-detection accuracy (RSK-02) and long-term historical identity continuity.
- **Options:** (a) One shared Participant Registry with role-specific extension data owned by each consuming context; (b) Separate registries per person type with cross-registry duplicate-detection as a compensating control.
- **Recommended direction:** (a) — one shared registry with role-specific extensions. A single canonical identity avoids the duplicate-identity problem at its root rather than compensating for it after the fact, and better serves the "historical participant identity" goal (DD-18).
- **Evidence required:** Confirmation that DepEd does not already maintain separate, incompatible identity systems per role type that would make unification impractical.
- **Decision owner:** To be identified (data architect, in consultation with Secretariat)
- **Target phase:** Phase 0.3 (informs role/permission modeling) / early architecture phase
- **Status:** Open — recommended direction stated

### DD-02 — Technical Official Identity Ownership
- **Question:** Does official identity belong to Participant Registry (BC-07) with Technical Officials (BC-13) owning only the assignment, or does BC-13 own identity for officials specifically?
- **Contexts affected:** BC-07, [BC-13](bounded-context-catalog.md#bc-13--technical-officials)
- **Why it matters:** Determines whether an official who later becomes a coach (or vice versa) is recognized as the same person.
- **Options:** (a) BC-07 owns identity, BC-13 owns only qualification/assignment; (b) BC-13 owns official identity independently.
- **Recommended direction:** (a), consistent with DD-01.
- **Evidence required:** Same as DD-01.
- **Decision owner:** To be identified
- **Target phase:** Same as DD-01
- **Status:** Open — recommended direction stated, dependent on DD-01

### DD-03 — Meet-Specific vs. Global Sports Catalog
- **Question:** Is the Sports Catalog (BC-10) platform-level master data shared across all meets, meet-specific configuration, or a hybrid (global definitions with meet-specific overrides)?
- **Contexts affected:** [BC-10](bounded-context-catalog.md#bc-10--sports-catalog), BC-11, BC-12, BC-18
- **Why it matters:** Affects reusability across meet cycles versus flexibility for meet-specific variation (e.g., a provincial meet may include events a regional meet does not).
- **Options:** (a) Fully global catalog; (b) Fully meet-specific catalog; (c) Hybrid — global sport/discipline definitions with meet-specific event/category configuration layered on top.
- **Recommended direction:** (c) Hybrid — supports the Phase 0.1 principle of "configurability over hard-coding" while still enabling reuse of stable sport definitions across meets.
- **Evidence required:** Confirmed sports/events list variance across a sample of past provincial meets.
- **Decision owner:** To be identified (Sports Specialists)
- **Target phase:** Phase 0.3 / early architecture phase
- **Status:** Open — recommended direction stated

### DD-04 — Scoring and Official Results as Separate Contexts
- **Question:** Should Scoring (BC-15) and Official Results (BC-16) remain separate bounded contexts, or be merged into one?
- **Contexts affected:** [BC-15](bounded-context-catalog.md#bc-15--scoring-high-integrity), [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity)
- **Why it matters:** These have materially different integrity, offline, and approval characteristics (Scoring is high-volume, offline-capable, capture-oriented; Results is certification-oriented and never offline) — merging would blur separation of duties.
- **Options:** (a) Keep separate with an Anti-Corruption Layer between them (as catalogued); (b) Merge into one "Scoring and Results" context.
- **Recommended direction:** (a) — confirmed as the Phase 0.2 direction throughout this documentation package; recorded here formally for validation rather than as a silently assumed default.
- **Evidence required:** None blocking — this is an architectural judgment call validated against stated integrity requirements.
- **Decision owner:** To be identified (software architect, with Technical Delegate input)
- **Target phase:** Confirm before Phase 0.3
- **Status:** Open — recommended direction stated and applied throughout this documentation package

### DD-05 — Accreditation and Access Validation Separation
- **Question:** Should Accreditation (BC-19) and Access Validation (BC-20) remain separate, or be merged?
- **Contexts affected:** [BC-19](bounded-context-catalog.md#bc-19--accreditation-high-integrity), [BC-20](bounded-context-catalog.md#bc-20--access-validation-high-integrity-high-volume-offline)
- **Why it matters:** Access Validation has a fundamentally different consistency/offline profile (high-volume, offline-first, eventually-consistent) than Accreditation (issuance-time, connectivity-typical, stronger consistency).
- **Options:** (a) Keep separate, connected via a Published Language (cached credential-validity set); (b) Merge into one context.
- **Recommended direction:** (a) — confirmed as the Phase 0.2 direction.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified
- **Target phase:** Confirm before Phase 0.3
- **Status:** Open — recommended direction stated and applied

### DD-06 — Protest and Appeals as Separate Context or Results Subdomain
- **Question:** Should Protest and Appeals (BC-17) be a fully separate bounded context, or a subdomain/module within Official Results (BC-16)?
- **Contexts affected:** BC-16, [BC-17](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity)
- **Why it matters:** Protests involve distinct actors (filers, adjudicators) and a distinct process (evidence, deadlines, appeals) from result certification itself; keeping them separate makes the "who can hold/correct a result" boundary explicit rather than implicit.
- **Options:** (a) Separate context (as catalogued), interacting with Official Results through a defined hold/correction interface; (b) Subdomain within Official Results.
- **Recommended direction:** (a) — separate context, because the actors and authority chain (per [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority)) are likely to differ from result-certification authority, and conflating them risks blurring separation of duties.
- **Evidence required:** Confirmed protest/appeal authority structure (OD-09).
- **Decision owner:** To be identified (Technical Delegates)
- **Target phase:** Phase 0.3
- **Status:** Open — recommended direction stated

### DD-07 — Venue Scheduling Ownership
- **Question:** Where is the boundary between Tournament Management (BC-12, which determines what needs a slot) and Venue and Schedule (BC-14, which determines where/when it fits)?
- **Contexts affected:** [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression), [BC-14](bounded-context-catalog.md#bc-14--venue-and-schedule)
- **Why it matters:** An unclear boundary here risks either an oversized "Meet Management" context (violating working rule 18) or duplicated scheduling logic in both contexts.
- **Options:** (a) Keep separate with a Partnership relationship (as catalogued) — Tournament Management owns competition structure/progression, Venue and Schedule owns physical time/place allocation; (b) Merge into one context.
- **Recommended direction:** (a) — confirmed as the Phase 0.2 direction; the Partnership pattern is specifically chosen (rather than a one-directional Customer-Supplier) because both sides must co-evolve when conflicts arise.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified
- **Target phase:** Confirm before Phase 0.3
- **Status:** Open — recommended direction stated and applied

### DD-08 — Medical Clearance Relationship to Eligibility
- **Question:** How exactly does Medical Operations' (BC-21) clearance status inform Eligibility and Clearance (BC-09) without Eligibility holding raw medical data?
- **Contexts affected:** [BC-09](bounded-context-catalog.md#bc-09--eligibility-and-clearance-high-integrity), [BC-21](bounded-context-catalog.md#bc-21--medical-operations-restricted)
- **Why it matters:** This is the platform's most sensitive cross-context data flow; getting the Anti-Corruption Layer boundary wrong risks either leaking medical data into a broader-access context or leaving Eligibility unable to function.
- **Options:** (a) Minimal boolean/enum clearance-status flag exposed via ACL (as catalogued); (b) Richer medical-summary exposure to Eligibility reviewers.
- **Recommended direction:** (a) — minimal status flag only, consistent with data minimization and the restricted classification of BC-21. A richer exposure should only be considered if a specific, approved DepEd policy requires it.
- **Evidence required:** DepEd medical-clearance and eligibility policy — relates directly to [Phase 0.1 OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).
- **Decision owner:** To be identified (Medical Team lead, Data Privacy and Legal Stakeholders)
- **Target phase:** Phase 0.3 — should precede detailed Eligibility workflow design
- **Status:** Open — recommended direction stated, blocked on Phase 0.1 OD-15

### DD-09 — Organization Directory Data Source
- **Question:** Is Organization Directory (BC-03) data locally maintained within PMMS, imported, or synchronized from an external DepEd registry?
- **Contexts affected:** [BC-03](bounded-context-catalog.md#bc-03--organization-directory), BC-06
- **Why it matters:** Directly determines BC-03's upstream relationship (none vs. external Conformist) in [context-map.md](context-map.md).
- **Options:** (a) Locally maintained within PMMS; (b) Imported from a DepEd school/organization registry; (c) Live-synchronized integration.
- **Recommended direction:** (a) for MVP, consistent with [Phase 0.1 OD-06](../00-product/open-decisions.md#od-06--school-data-source), with (b)/(c) as future-scope integrations.
- **Evidence required:** Availability and access terms for a DepEd organization/school registry.
- **Decision owner:** To be identified
- **Target phase:** Phase 0.3 / integration architecture phase
- **Status:** Open

### DD-10 — Delegation Hierarchy (Domain-Modeling Implications)
- **Question:** Given the Phase 0.1 open question on delegation grouping unit ([OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy)), how should Delegation Management (BC-06) model the relationship between Organization Directory (BC-03) and Delegation once that policy question is answered?
- **Contexts affected:** BC-03, [BC-06](bounded-context-catalog.md#bc-06--delegation-management)
- **Why it matters:** A school-based delegation model and a municipality/district-grouped delegation model imply materially different aggregate boundaries for `Delegation` (see [phase-0.2-domain-architecture.md, Section 11](phase-0.2-domain-architecture.md#11-aggregate-candidate-analysis)).
- **Options:** Deferred — options mirror [Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy).
- **Recommended direction:** None — this is a direct domain-modeling consequence of OD-04 and cannot be resolved independently.
- **Evidence required:** Resolution of OD-04.
- **Decision owner:** To be identified (Schools Division Office / Meet Organizing Committee)
- **Target phase:** Phase 0.3
- **Status:** Open — blocked on Phase 0.1 OD-04

### DD-11 — Athlete Multi-Sport Participation
- **Question:** May one athlete represent multiple sports in the same meet, and if so, how does this affect the Competition Entries (BC-11) aggregate boundary?
- **Contexts affected:** BC-08, [BC-11](bounded-context-catalog.md#bc-11--competition-entries)
- **Why it matters:** Directly mirrors [Phase 0.1 organizational question](../00-product/phase-0.1-product-foundation.md#16-organizational-model): "whether one athlete may represent multiple sports."
- **Options:** (a) Unrestricted multi-sport entry; (b) Restricted per DepEd/sport-specific rules (e.g., scheduling-conflict-based restriction).
- **Recommended direction:** None — requires DepEd/sports-specialist input; the architecture should support either without assuming one at this stage (CompetitionEntry should not structurally assume single-sport participation).
- **Evidence required:** DepEd policy on multi-sport participation.
- **Decision owner:** To be identified (Sports Specialists)
- **Target phase:** Phase 0.3
- **Status:** Open

### DD-12 — Team and Individual Event Modeling
- **Question:** How should Competition Entries and Tournament Management model team events (e.g., relay, team sports) versus individual events within a shared aggregate structure?
- **Contexts affected:** BC-10, BC-11, [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression)
- **Why it matters:** A `CompetitionEntry` aggregate that assumes one athlete per entry cannot represent a relay team or a team-sport roster without a documented extension concept.
- **Options:** (a) A single `CompetitionEntry` concept with a variable participant-count concept (individual = 1, team = N); (b) Distinct `IndividualEntry` and `TeamEntry` concepts.
- **Recommended direction:** (a), to avoid unnecessary fragmentation (working rule 19) — but this must be validated against actual sport formats in the initial meet program.
- **Evidence required:** Confirmed sports/events list with team-vs-individual classification for the pilot meet.
- **Decision owner:** To be identified (Sports Specialists)
- **Target phase:** Phase 0.3
- **Status:** Open — recommended direction stated

### DD-13 — Sport-Specific Plugin Strategy and Tournament Format Configurability
- **Question:** How does Tournament Management (BC-12) and Sports Catalog (BC-10) support sport-specific variation (elimination, round-robin, time-based, judged) without hard-coding one tournament model or requiring a bespoke module per sport?
- **Contexts affected:** BC-10, [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression), BC-15
- **Why it matters:** This is explicitly named in the bounded-context catalog as a requirement ("must support sport-specific variation without hard-coding one tournament model") and is one of the highest-complexity areas of the entire platform.
- **Options:** (a) A configuration-driven format model where BC-10 defines format parameters and BC-12 interprets them generically; (b) A sport-specific "plugin" extension model where certain sports have bespoke logic modules; (c) Hybrid — generic formats handled by (a), with an explicit extension point for sports too idiosyncratic to generalize.
- **Recommended direction:** (c) Hybrid — start generic, add an explicit extension point rather than prematurely building a full plugin framework before real sport-format diversity is known.
- **Evidence required:** Confirmed format diversity across the pilot meet's sports list.
- **Decision owner:** To be identified (software architect, Sports Specialists)
- **Target phase:** Architecture phase following Phase 0.3
- **Status:** Open — recommended direction stated

### DD-14 — Committee-Specific Context Boundaries
- **Question:** Do highly specialized committees (Medical, Finance, Security, etc.) require fully separate bounded contexts (as catalogued), or could some be modules within a broader Committee Operations context?
- **Contexts affected:** [BC-05](bounded-context-catalog.md#bc-05--committee-operations), BC-21–BC-27
- **Why it matters:** Directly engages working rule 16 ("do not create bounded contexts based only on... committee names") — this decision validates that the specialized committee contexts exist because of distinct data/lifecycle/integrity needs, not merely because they map to an org chart.
- **Options:** (a) Keep each specialized committee context separate (as catalogued), with BC-05 owning only the administrative membership/mandate shell; (b) Consolidate low-complexity committees (e.g., ICT, Finance) into BC-05 as modules.
- **Recommended direction:** (a) is retained for Medical (BC-21, due to sensitivity), Security (BC-25, due to safety/offline needs), and Finance (BC-26, due to audit needs); billeting/food/transportation (BC-22–24) are retained separately because their data (facility capacity, meal entitlements, trip manifests) has no natural overlap with each other despite similar operational shape. This is a defensible outcome, not a default — validated per working rule 16, not merely accepted from the candidate list.
- **Evidence required:** None blocking; this is primarily an architecture judgment.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Confirm before Phase 0.3
- **Status:** Open — recommended direction stated and applied

### DD-15 — Result Versioning Authority
- **Question:** Who has authority to create a new version of an Official Result (i.e., trigger a correction), and under what circumstances short of a formal protest?
- **Contexts affected:** [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity), BC-17
- **Why it matters:** Without a clear answer, "correction instead of destructive overwrite" (a core high-integrity principle) has no defined actor.
- **Options:** (a) Only through a resolved Protest (BC-17); (b) Also through a defined administrative correction process for clerical errors (e.g., a data-entry typo caught before publication) distinct from a protest.
- **Recommended direction:** (b) — a narrow, heavily audited administrative correction path for pre-publication clerical errors, with all post-publication corrections requiring the full Protest process. This avoids forcing every trivial fix through a formal protest while preserving integrity guarantees for anything the public has seen.
- **Evidence required:** DepEd/sport policy on whether such an administrative correction path is acceptable.
- **Decision owner:** To be identified ([Phase 0.1 OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain) owner)
- **Target phase:** Phase 0.3
- **Status:** Open — recommended direction stated

### DD-16 — Medal Tally Correction Authority
- **Question:** Following a `MedalTallyRecalculated` event, who is authorized to confirm/publish the corrected tally, and is this the same authority as initial tally publication?
- **Contexts affected:** [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived)
- **Why it matters:** Medal tally corrections are highly visible and reputationally sensitive; an unclear authority chain risks either delay or an unauthorized correction.
- **Options:** (a) Same authority as initial publication; (b) A distinct, higher-authority confirmation step for any post-publication tally correction.
- **Recommended direction:** (b) — an extra confirmation step for post-publication corrections specifically, given the public visibility and correction-sensitivity of this domain.
- **Evidence required:** [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules) resolution.
- **Decision owner:** To be identified
- **Target phase:** Phase 0.3
- **Status:** Open — recommended direction stated, blocked on OD-12

### DD-17 — Public Publication Approval Chain
- **Question:** What is the exact approval chain from "result certified" to "result visible on the public portal" — is publication a separate authorized step (as catalogued) or automatic upon certification?
- **Contexts affected:** BC-16, BC-18, [BC-29](bounded-context-catalog.md#bc-29--public-information-non-authoritative)
- **Why it matters:** Directly shapes the Public Data Boundary (see [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary)) and the protest-window timing question.
- **Options:** (a) Certification and publication are always distinct, separately authorized steps (as catalogued); (b) Publication is automatic immediately upon certification.
- **Recommended direction:** (a) — distinct steps, so a certified-but-not-yet-public result can still be corrected before public exposure without requiring a visible public correction, reducing reputational risk from clerical errors while preserving full correction transparency for anything already public.
- **Evidence required:** DepEd/sport policy on protest-window timing relative to publication ([Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority)).
- **Decision owner:** To be identified
- **Target phase:** Phase 0.3
- **Status:** Open — recommended direction stated

### DD-18 — Historical Participant Identity
- **Question:** Does a Participant's identity persist and accumulate history across multiple meets/years, or is it re-established fresh per meet?
- **Contexts affected:** [BC-07](bounded-context-catalog.md#bc-07--participant-registry)
- **Why it matters:** Directly supports (or undermines) the Phase 0.1 goal of "creating reusable meet records and institutional knowledge."
- **Options:** (a) Persistent cross-meet identity (supports historical analytics, RSK-02 duplicate prevention over time); (b) Meet-scoped identity re-created each cycle.
- **Recommended direction:** (a) — persistent identity, consistent with [Phase 0.1 Section 5](../00-product/phase-0.1-product-foundation.md#5-product-mission) ("creating reusable meet records and institutional knowledge").
- **Evidence required:** Data retention policy confirmation ([Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)).
- **Decision owner:** To be identified
- **Target phase:** Phase 0.3
- **Status:** Open — recommended direction stated

### DD-19 — Offline Finality Rules
- **Question:** Beyond the explicitly prohibited actions in [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md), is there any narrowly scoped exception where an offline decision should be allowed to become final without a subsequent server-side confirmation step?
- **Contexts affected:** BC-15, BC-20, BC-21
- **Why it matters:** A blanket "nothing is ever final offline" rule may be operationally impractical for some edge cases (e.g., an isolated venue with days of no connectivity); this should be a deliberate exception process, not an ad hoc one.
- **Options:** (a) No exceptions — the prohibited-actions list in [offline-and-synchronization-boundaries.md](offline-and-synchronization-boundaries.md) is absolute; (b) A narrowly defined, pre-authorized exception process for extreme connectivity-loss scenarios.
- **Recommended direction:** (a) for Phase 0.2 — no exceptions should be assumed; if operational reality later demands an exception, it should be a deliberate, documented policy decision, not a default architectural allowance.
- **Evidence required:** Real-world connectivity data from a pilot meet.
- **Decision owner:** To be identified (ICT Committee, DepEd Leadership)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated (no exceptions by default)

### DD-20 — Cross-Meet Record Reuse
- **Question:** Which master/reference data (Participant Registry, Sports Catalog, Organization Directory) is reused across meets versus recreated per meet?
- **Contexts affected:** BC-03, BC-07, BC-10
- **Why it matters:** Directly affects the multi-meet operating model established in [Phase 0.1](../00-product/operating-model.md#1-multi-meet-concept).
- **Options:** (a) Master data (Organization, Participant, Sport definitions) is platform-level and reused; meet-scoped data (registrations, entries, results) is created fresh per meet; (b) Everything is meet-scoped.
- **Recommended direction:** (a) — consistent with the data-ownership distinction already drawn in [data-ownership-map.md](data-ownership-map.md) between master data and meet-scoped transactional data.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified
- **Target phase:** Confirm before Phase 0.3
- **Status:** Open — recommended direction stated

### DD-21 — Tenant Boundaries
- **Question:** If PMMS later supports organizations beyond DepEd (per [Phase 0.1 OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization)), which bounded contexts need tenant-scoping built in from the start versus retrofitted later?
- **Contexts affected:** BC-01, BC-03, BC-04, and by extension all meet-scoped contexts
- **Why it matters:** Retrofitting tenant isolation into a context that assumed a single organization is materially more expensive than designing for it from the start, even if multi-tenancy is not required at launch.
- **Options:** (a) Design all contexts with an implicit organization-scoping concept from the start, even though only one organization exists initially; (b) Design single-organization now, retrofit later.
- **Recommended direction:** (a) — consistent with the "tenant isolation readiness" commercial-quality principle from [Phase 0.1 Section 18](../00-product/phase-0.1-product-foundation.md#18-commercial-quality-product-direction), at low cost if designed in from the start.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Architecture phase
- **Status:** Open — recommended direction stated

### DD-22 — Shared Reference Data Ownership
- **Question:** What specifically belongs in Configuration and Reference Data (BC-34) versus staying local to its owning context, to prevent it from becoming a "miscellaneous data" dumping ground (working rule 20)?
- **Contexts affected:** [BC-34](bounded-context-catalog.md#bc-34--configuration-and-reference-data), all consumers
- **Why it matters:** An overly broad BC-34 would silently absorb rule content that belongs to Core domains (e.g., eligibility criteria, scoring rules), undermining the domain ownership principles established throughout this documentation.
- **Options:** (a) BC-34 holds only genuinely cross-context, non-business-rule reference values (status vocabularies, geographic references, generic enumerations); (b) BC-34 holds broader "settings" content including business rules.
- **Recommended direction:** (a) — strictly, per the exclusions already stated in [bounded-context-catalog.md](bounded-context-catalog.md#bc-34--configuration-and-reference-data). This is treated as an architectural guardrail, not merely a preference.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Confirm before Phase 0.3
- **Status:** Open — recommended direction stated and applied

### DD-23 — Document Retention Ownership
- **Question:** Does Document and Records (BC-30) own retention *policy* (how long, under what rule) or only retention *mechanics* (enforcing a policy set elsewhere)?
- **Contexts affected:** [BC-30](bounded-context-catalog.md#bc-30--document-and-records)
- **Why it matters:** Retention policy is a DepEd records-management/legal decision ([Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)); BC-30 should not invent it.
- **Options:** (a) BC-30 owns mechanics only, consuming policy parameters defined by DepEd records-management authority (possibly represented via BC-34 as versioned reference data); (b) BC-30 owns policy itself.
- **Recommended direction:** (a) — BC-30 enforces, it does not author, retention policy.
- **Evidence required:** DepEd records-management policy (OD-24).
- **Decision owner:** To be identified
- **Target phase:** Phase 0.3
- **Status:** Open — recommended direction stated, blocked on OD-24 for actual values

### DD-24 — Audit Data Ownership When a Context Is Itself Under Audit
- **Question:** If Audit and Compliance (BC-32) itself needs to be reviewed/audited (e.g., for tamper-evidence verification), who has that authority, and does it create a circular ownership problem?
- **Contexts affected:** [BC-32](bounded-context-catalog.md#bc-32--audit-and-compliance-elevated-integrity)
- **Why it matters:** BC-32 underpins every other high-integrity guarantee in this documentation package; if its own integrity cannot be independently verified, that guarantee is weaker than stated.
- **Options:** (a) BC-32's integrity-verification capability (e.g., tamper-evidence checks) is itself auditable by an external/independent role (Auditors), distinct from any administrative role that could otherwise influence audit content; (b) No independent verification mechanism.
- **Recommended direction:** (a) — external independent verification capability, consistent with the "no silent mutation" principle applied to the audit mechanism itself.
- **Evidence required:** None blocking architecturally; may require a specific DepEd audit-authority designation.
- **Decision owner:** To be identified (Auditors, DepEd Leadership)
- **Target phase:** Architecture phase
- **Status:** Open — recommended direction stated

### DD-25 — Data Warehouse Timing
- **Question:** When (which phase) should a dedicated analytics/data-warehouse capability be introduced for Reporting and Analytics (BC-33), versus relying on context-owned operational projections in the interim?
- **Contexts affected:** [BC-33](bounded-context-catalog.md#bc-33--reporting-and-analytics-consumes-only)
- **Why it matters:** Building a full data warehouse prematurely (before real reporting needs and data volume are known) risks overengineering; delaying too long risks ad hoc reporting queries creeping into transactional contexts.
- **Options:** (a) Defer a dedicated warehouse; use context-owned projections and simple cross-context read models until real reporting volume/complexity justifies more; (b) Build warehouse infrastructure early.
- **Recommended direction:** (a) — consistent with avoiding premature complexity (see [phase-0.2-domain-architecture.md, Section 21](phase-0.2-domain-architecture.md#21-alternatives-considered)); revisit once historical/cross-meet analytics (a named future-scope capability) becomes an active priority.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Future scope (post-MVP)
- **Status:** Open — recommended direction stated

### DD-26 — AI Service Data-Access Boundaries (Domain-Specific Framing)
- **Question:** For each high-integrity context, which specific data categories may ever be sent to an AI service for advisory purposes, and which must never leave the context boundary?
- **Contexts affected:** BC-09, BC-15, BC-16, BC-17, BC-18, BC-19, BC-21
- **Why it matters:** This is the domain-architecture-level expression of [Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions); Phase 0.2 must ensure context boundaries make it *structurally easy* to enforce whatever AI policy DepEd ultimately approves.
- **Options:** (a) Design each high-integrity context so AI-eligible data (e.g., non-sensitive metadata for anomaly detection) is architecturally separable from restricted data (raw medical records, full eligibility evidence) from the outset; (b) Address this only at implementation time.
- **Recommended direction:** (a) — the Anti-Corruption Layer boundaries already established (Medical → Eligibility, Scoring → Results) are deliberately structured so that a future AI-access policy can be enforced at those same boundaries rather than requiring new ones.
- **Evidence required:** Formal DepEd AI-use policy ([Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions)).
- **Decision owner:** To be identified (DepEd Leadership, Data Privacy and Legal Stakeholders)
- **Target phase:** Phase 0.3 — should precede any AI feature implementation
- **Status:** Open — recommended direction stated, blocked on OD-29 for final policy

---

## Summary of Decisions Blocking Phase 0.3

The following domain-modeling decisions should be prioritized for validation before Phase 0.3 (User, Role, Permission, Scope, and Assignment Architecture) begins, because Phase 0.3's role/assignment model depends on knowing who the accountable actors are within each workflow:

- DD-01 / DD-02 — Participant Registry model (affects how "a user account" relates to "a participant" for role assignment purposes)
- DD-06 — Protest and Appeals authority structure
- DD-08 — Medical clearance/Eligibility ACL boundary
- DD-15, DD-16, DD-17 — Result, medal tally, and publication correction/approval authority

These compound directly with the still-open Phase 0.1 product-level decisions (notably [OD-07](../00-product/open-decisions.md#od-07--eligibility-authority), [OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain), [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority), [OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules)) — Phase 0.3 cannot assign roles to authorities that have not yet been named.
