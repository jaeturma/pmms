# PMMS Domain Glossary

**Status:** Draft Complete — Pending Domain and Stakeholder Validation
**Related:** [bounded-context-catalog.md](bounded-context-catalog.md) · [../../.ai/domain-glossary.md](../../.ai/domain-glossary.md) (condensed AI-facing version)

This glossary establishes one consistent meaning per term across all PMMS documentation and, eventually, code. Where a term's precise meaning varies by sport or by DepEd policy, that is stated explicitly — **do not resolve those variations by invention; they require validation from an authorized sports or DepEd source** (per working rule 11).

| Term | Definition | Owning Context |
|---|---|---|
| **Platform** | The overall PMMS system spanning all organizations, meets, and contexts. | [BC-01](bounded-context-catalog.md#bc-01--platform-administration) |
| **Organization** | A top-level entity (e.g., DepEd) that owns one or more meets and organizational hierarchy. | [BC-03](bounded-context-catalog.md#bc-03--organization-directory) |
| **Region** | An organizational subdivision above Division, per DepEd's structure. | [BC-03](bounded-context-catalog.md#bc-03--organization-directory) |
| **Division** | An organizational subdivision within a Region. | [BC-03](bounded-context-catalog.md#bc-03--organization-directory) |
| **District** | An organizational subdivision within a Division, where applicable. | [BC-03](bounded-context-catalog.md#bc-03--organization-directory) |
| **School** | A participating educational institution that may register a delegation. | [BC-03](bounded-context-catalog.md#bc-03--organization-directory) |
| **Meet** | A bounded, time-boxed athletic event with its own configuration, dates, venues, committees, delegations, sports, events, officials, schedules, and results. | [BC-04](bounded-context-catalog.md#bc-04--meet-administration) |
| **Host** | The organization or entity responsible for staging a specific meet. | [BC-04](bounded-context-catalog.md#bc-04--meet-administration) |
| **Organizing Committee** | The overall body accountable for a meet's planning and execution. | [BC-04](bounded-context-catalog.md#bc-04--meet-administration) / [BC-05](bounded-context-catalog.md#bc-05--committee-operations) |
| **Committee** | A functional group (e.g., Medical, Finance, Security) responsible for one operational domain within a meet. | [BC-05](bounded-context-catalog.md#bc-05--committee-operations) |
| **Delegation** | A participating group (typically a school or grouping of schools) entering athletes and coaches into a meet. Exact grouping unit requires validation — see [Phase 0.1 OD-04](../00-product/open-decisions.md#od-04--delegation-hierarchy). | [BC-06](bounded-context-catalog.md#bc-06--delegation-management) |
| **Participant** | The canonical identity record for any individual person known to PMMS (athlete, coach, official, or staff), independent of any single meet. | [BC-07](bounded-context-catalog.md#bc-07--participant-registry) |
| **Athlete** | A Participant registered to compete in one or more events at a meet. | [BC-07](bounded-context-catalog.md#bc-07--participant-registry) / [BC-08](bounded-context-catalog.md#bc-08--athlete-registration) |
| **Coach** | A Participant responsible for preparing and representing athletes within a delegation. | [BC-07](bounded-context-catalog.md#bc-07--participant-registry) |
| **Delegation Official** | A Participant holding an administrative or support role within a delegation (e.g., delegation head). | [BC-06](bounded-context-catalog.md#bc-06--delegation-management) |
| **Tournament Manager** | The role responsible for a sport's or venue's competition scheduling and progression. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Technical Official** | A qualified individual (referee, judge, umpire, scorer, timer) authorized to officiate or score competition. | [BC-13](bounded-context-catalog.md#bc-13--technical-officials) |
| **Accreditation Officer** | The role responsible for issuing and managing accreditation credentials. | [BC-19](bounded-context-catalog.md#bc-19--accreditation-high-integrity) |
| **Sport** | A defined competitive discipline (e.g., athletics, basketball) recognized in the Sports Catalog. | [BC-10](bounded-context-catalog.md#bc-10--sports-catalog) |
| **Discipline** | A specific sub-competition within a sport (e.g., a track distance within athletics). Sport-specific naming varies and requires validation. | [BC-10](bounded-context-catalog.md#bc-10--sports-catalog) |
| **Event** | A specific competitive unit within a sport that athletes enter and that produces a result (e.g., "100m Boys 16-U"). | [BC-10](bounded-context-catalog.md#bc-10--sports-catalog) |
| **Category** | A classification dimension for an event (e.g., age group, weight class, gender division). | [BC-10](bounded-context-catalog.md#bc-10--sports-catalog) |
| **Competition Entry** | A submitted request for a specific Athlete (or team) to participate in a specific Event. | [BC-11](bounded-context-catalog.md#bc-11--competition-entries) |
| **Tournament** | The overall competitive structure (draw, bracket, pool, or schedule of rounds) for an Event. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Draw** | The process/outcome of assigning entries to initial positions within a Tournament structure. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Bracket** | An elimination-style Tournament structure. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Pool** | A round-robin grouping of entries within a Tournament structure. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Heat** | A single timed or measured grouping of entries competing together (typically in track/field or swimming-style events). | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Lane** | A specific assigned position within a Heat. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Match** | A single competitive encounter between entries within a Tournament (sport-agnostic term covering "bout," "game," etc.). | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Bout** | Sport-specific term for a Match, typically used in combat sports. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Game** | Sport-specific term for a Match, typically used in ball sports. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Round** | A defined stage of progression within a Tournament (e.g., quarterfinal round). | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Seed** | A ranking assigned to an entry prior to a Draw, used to structure the Tournament. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) |
| **Schedule** | The assignment of Matches/Heats to specific times and venues. | [BC-14](bounded-context-catalog.md#bc-14--venue-and-schedule) |
| **Venue** | A physical location where one or more Matches/Heats occur. | [BC-14](bounded-context-catalog.md#bc-14--venue-and-schedule) |
| **Competition Area** | A specific usable space within a Venue (e.g., a specific court or field). | [BC-14](bounded-context-catalog.md#bc-14--venue-and-schedule) |
| **Score** | A raw captured value (time, measurement, point count, judged mark) recorded for a Match/Heat participant. | [BC-15](bounded-context-catalog.md#bc-15--scoring-high-integrity) |
| **Score Record** | The structured record of one or more Scores captured for a Match/Heat, including revisions and validation state. | [BC-15](bounded-context-catalog.md#bc-15--scoring-high-integrity) |
| **Result** | The outcome derived from validated Score Records for a Match/Heat/Event, prior to certification. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) |
| **Official Result** | A Result that has completed the validation and certification process and carries institutional authority. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) |
| **Published Result** | An Official Result that has been released through the controlled publication workflow and is visible to delegations and/or the public. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) / [BC-29](bounded-context-catalog.md#bc-29--public-information-non-authoritative) |
| **Placement** | The rank position (1st, 2nd, 3rd, etc.) assigned to an entry within an Official Result. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) |
| **Ranking** | The ordered list of Placements for an Event. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) |
| **Advancement** | The determination that an entry progresses to a subsequent Round based on an Official Result. | [BC-12](bounded-context-catalog.md#bc-12--tournament-management-high-integrity--progression) / [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) |
| **Medal Award** | The assignment of a medal (gold/silver/bronze or equivalent) to an entry based on an Official Result. | [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived) |
| **Medal Tally** | The aggregated count of Medal Awards, typically by delegation, used for overall standings. | [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived) |
| **Team Standing** | A delegation's overall ranking within a meet, which may be computed from Medal Tally and/or a separate team-points formula. | [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived) |
| **Protest** | A formal challenge to an Official Result or officiating decision, filed within a defined process. | [BC-17](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity) |
| **Appeal** | A request to reconsider a Protest decision at a higher authority level. | [BC-17](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity) |
| **Eligibility Case** | The record of a Participant's eligibility submission and its review/decision history for a given meet. | [BC-09](bounded-context-catalog.md#bc-09--eligibility-and-clearance-high-integrity) |
| **Clearance** | An approved status confirming a Participant may proceed (used both for eligibility and, distinctly, for medical fitness — the two are related but not identical; see [context-map.md](context-map.md) ACL note). | [BC-09](bounded-context-catalog.md#bc-09--eligibility-and-clearance-high-integrity) / [BC-21](bounded-context-catalog.md#bc-21--medical-operations-restricted) |
| **Accreditation** | The formal credentialing process confirming a Participant's authorized role and access privileges for a meet. | [BC-19](bounded-context-catalog.md#bc-19--accreditation-high-integrity) |
| **Credential** | The issued artifact (physical or digital) representing an Accreditation. | [BC-19](bounded-context-catalog.md#bc-19--accreditation-high-integrity) |
| **QR Token** | A scannable digital representation of a Credential used for Access Scan validation. | [BC-19](bounded-context-catalog.md#bc-19--accreditation-high-integrity) / [BC-20](bounded-context-catalog.md#bc-20--access-validation-high-integrity-high-volume-offline) |
| **Access Scan** | A single validation event checking a Credential against a specific access point (venue, meal, billeting, transport). | [BC-20](bounded-context-catalog.md#bc-20--access-validation-high-integrity-high-volume-offline) |
| **Validation** | The act of checking a submission, score, or credential against defined criteria before it can progress to the next workflow state. | Context-specific (see individual catalog entries) |
| **Certification** | The formal, authoritative confirmation that a Result is official and final pending only Protest/Appeal processes. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) |
| **Publication** | The controlled act of releasing validated/certified information (Results, Medal Tally, Schedules, Announcements) for delegation or public visibility. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) / [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived) / [BC-29](bounded-context-catalog.md#bc-29--public-information-non-authoritative) |
| **Withdrawal** | The act of removing a previously submitted Athlete Registration or Competition Entry before or during competition. | [BC-08](bounded-context-catalog.md#bc-08--athlete-registration) / [BC-11](bounded-context-catalog.md#bc-11--competition-entries) |
| **Substitution** | The replacement of one Participant for another within a Competition Entry, subject to sport-specific rules. | [BC-11](bounded-context-catalog.md#bc-11--competition-entries) |
| **Disqualification** | The removal of an entry's Result or standing due to a rule violation, determined by a Technical Official and subject to Protest/Appeal. Actual disqualification criteria are sport-specific and require validation. | [BC-16](bounded-context-catalog.md#bc-16--official-results-high-integrity) / [BC-17](bounded-context-catalog.md#bc-17--protest-and-appeals-high-integrity) |
| **Incident** | A logged occurrence requiring operational attention (medical, security, transportation, ICT), distinct from a Protest. | [BC-21](bounded-context-catalog.md#bc-21--medical-operations-restricted) / [BC-25](bounded-context-catalog.md#bc-25--security-operations) / others |
| **Audit Event** | A recorded fact about who did what, to what, when, and (where applicable) why, across any context. | [BC-32](bounded-context-catalog.md#bc-32--audit-and-compliance-elevated-integrity) |
| **Assignment** | The act of designating a Participant (official, committee member) to a specific role, duty, venue, or event. | Context-specific (BC-05, BC-13) |
| **Role** | A named function a user account may hold (e.g., Tournament Manager, Secretariat). Full role/permission architecture is a Phase 0.3 concern. | [BC-02](bounded-context-catalog.md#bc-02--identity-and-access) (boundary only) |
| **Permission** | A specific authorized action a Role may perform. Deferred to Phase 0.3. | [BC-02](bounded-context-catalog.md#bc-02--identity-and-access) (boundary only) |
| **Scope** | The boundary (organization, meet, committee, venue, sport, event) within which a Role/Permission applies. Deferred to Phase 0.3. | [BC-02](bounded-context-catalog.md#bc-02--identity-and-access) (boundary only) |
| **Read Model** | A denormalized, query-optimized projection of data derived from one or more authoritative contexts, used for reporting or display, never for writes. | [BC-33](bounded-context-catalog.md#bc-33--reporting-and-analytics-consumes-only) / [BC-29](bounded-context-catalog.md#bc-29--public-information-non-authoritative) |
| **Projection** | A specific materialized view built from Domain Events or authoritative records, used by a downstream context. | Cross-cutting (see [reporting-and-read-model-boundaries.md](reporting-and-read-model-boundaries.md)) |
| **Snapshot** | A point-in-time capture of a Projection or authoritative state, retained for historical reference. | [BC-18](bounded-context-catalog.md#bc-18--medal-tally-and-team-standings-high-integrity-derived) / [BC-33](bounded-context-catalog.md#bc-33--reporting-and-analytics-consumes-only) |

## Sport-Specific Terminology Note

Several terms above (Discipline, Bout/Game/Match, Heat/Lane, Round, Seed) have sport-specific variants and naming conventions that this glossary intentionally generalizes. **Sport-specific terminology mappings require validation from sports specialists per sport** before being encoded into any configuration or design artifact — see [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source) and [domain-classification.md](domain-classification.md#areas-requiring-domain-expert-involvement).
