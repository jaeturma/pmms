# PMMS Stakeholder Register

**Status:** Draft for Architecture and Stakeholder Validation
**Related:** [phase-0.1-product-foundation.md](phase-0.1-product-foundation.md#13-stakeholder-groups) · [operating-model.md](operating-model.md)

This register documents the stakeholder groups identified for PMMS, their interests, responsibilities, and validation needs. No personal names are recorded — all entries are by role/group. Consultation priority is a Phase 0.1 proposal for sequencing stakeholder engagement in later phases, not a commitment.

**Consultation priority legend:** `High` — should be consulted before or during Phase 0.2; `Medium` — should be consulted before major design decisions in their domain; `Low` — should be consulted before release planning or when their domain becomes active.

---

### DepEd Leadership

- **Interest:** Ensures PMMS aligns with DepEd policy, governance, and institutional accountability.
- **Responsibilities:** Policy authorization, resource sponsorship, institutional endorsement.
- **Decisions influenced:** Overall program approval, policy boundaries (e.g., what AI automation is permitted), data governance policy.
- **Information needed:** Program status, risk exposure, compliance posture, budget/resource implications.
- **Risks/concerns:** Institutional reputational risk if results or data are mishandled; compliance with government data-privacy law.
- **Expected system interaction:** Indirect — via reporting and governance oversight, not day-to-day use.
- **Consultation priority:** High
- **Validation questions:** What official policy exists (or must be created) governing eligibility, data privacy, and AI use in DepEd sports meets? Who has final sign-off authority on Phase 0.1 direction?

### Meet Organizing Committee

- **Interest:** Successful, well-run execution of the meet using PMMS as the operating platform.
- **Responsibilities:** Overall meet planning and execution, committee coordination.
- **Decisions influenced:** Meet configuration, committee structure, schedule, escalation handling.
- **Information needed:** Real-time operational status across all committees and venues.
- **Risks/concerns:** Platform reliability during live operations; adoption resistance from committees used to manual processes.
- **Expected system interaction:** Primary/daily use of the Administrative and Operations Portal.
- **Consultation priority:** High
- **Validation questions:** What does the current (manual) organizing workflow look like, end to end? What decisions currently take too long?

### Schools Division Office

- **Interest:** Oversight of participating schools' compliance and representation.
- **Responsibilities:** School-level coordination, potentially eligibility endorsement.
- **Decisions influenced:** School/delegation registration approval, eligibility endorsement chain.
- **Information needed:** School participation status, delegation rosters.
- **Risks/concerns:** Data accuracy for schools under their jurisdiction.
- **Expected system interaction:** Administrative Portal, scoped to their division.
- **Consultation priority:** High
- **Validation questions:** What is the Schools Division Office's formal role in eligibility validation today? Is it advisory or authoritative?

### Participating Schools

- **Interest:** Successful representation of their students/athletes.
- **Responsibilities:** Submitting athlete/coach registrations, eligibility documentation.
- **Decisions influenced:** Roster composition, entry submissions.
- **Information needed:** Registration status, schedule, results for their delegation.
- **Risks/concerns:** Registration errors, missed deadlines, lost documentation.
- **Expected system interaction:** Registration and entry submission workflows.
- **Consultation priority:** Medium
- **Validation questions:** What registration process do schools currently follow, and what causes the most friction?

### Delegations

- **Interest:** Fair, accurate representation and treatment of their athletes.
- **Responsibilities:** Delegation-level roster and logistics management.
- **Decisions influenced:** Entry submissions, athlete assignments.
- **Information needed:** Schedule, results, logistics (billeting, transport, meals).
- **Risks/concerns:** Miscommunication of schedule changes; unfair treatment in officiating or scoring.
- **Expected system interaction:** Delegation-scoped views across registration, schedule, and results.
- **Consultation priority:** Medium
- **Validation questions:** How are delegations currently structured relative to schools (see organizational questions in the foundation document)?

### Athletes

- **Interest:** Fair competition, accurate results, personal data protection, minimal administrative friction.
- **Responsibilities:** Providing accurate personal/eligibility information (typically via coach/school).
- **Decisions influenced:** None directly in most cases; primarily represented by coaches/delegation heads.
- **Information needed:** Their schedule, event assignments, results.
- **Risks/concerns:** Data privacy (many athletes are minors), incorrect eligibility determination.
- **Expected system interaction:** Minimal direct interaction; may view schedules/results, possibly via delegation or public portal.
- **Consultation priority:** Medium
- **Validation questions:** What is the minimum athlete data required, and what special protections are needed given minors are likely the majority of participants?

### Parents and Guardians

- **Interest:** Visibility into their child's schedule, safety, and results.
- **Responsibilities:** None operationally; may provide consent for data collection involving minors.
- **Decisions influenced:** Consent for minor data handling, where applicable.
- **Information needed:** Schedule, results, safety/medical advisories concerning their child.
- **Risks/concerns:** Privacy of their child's data; safety during the event.
- **Expected system interaction:** Public portal; possibly a parent-facing view if scoped in later phases.
- **Consultation priority:** Low
- **Validation questions:** Is direct parent/guardian access to the system required, or is information relayed through schools/delegations? (See [open-decisions.md](open-decisions.md).)

### Coaches

- **Interest:** Efficient registration, accurate scheduling, fair officiating.
- **Responsibilities:** Athlete registration support, entry submission, team management during the meet.
- **Decisions influenced:** Entry submissions, athlete assignments to events.
- **Information needed:** Schedule, entry status, results.
- **Risks/concerns:** Registration errors, last-minute schedule changes.
- **Expected system interaction:** Registration and entry workflows; mobile schedule lookup during the meet.
- **Consultation priority:** High
- **Validation questions:** What is the current coach registration workflow, and what tools (if any) do coaches currently use?

### Tournament Managers

- **Interest:** Smooth, well-organized competition execution for their sport(s).
- **Responsibilities:** Scheduling, bracket/heat management, officiating coordination.
- **Decisions influenced:** Schedule, brackets, officiating assignments, result validation.
- **Information needed:** Entries, venue availability, officials availability, live results.
- **Risks/concerns:** Scheduling conflicts, incomplete entry data, system downtime during competition.
- **Expected system interaction:** Primary/daily use of the Tournament Management Portal.
- **Consultation priority:** High
- **Validation questions:** What scheduling/bracket process is used today per sport, and how much varies sport-to-sport?

### Technical Officials

- **Interest:** Accurate, defensible officiating and scoring supported by the platform.
- **Responsibilities:** Score entry, rule enforcement, protest handling.
- **Decisions influenced:** Official results, disqualifications, protest outcomes.
- **Information needed:** Event assignments, scoring interfaces, protest records.
- **Risks/concerns:** Platform undermining or overriding their authority; usability under live competition pressure.
- **Expected system interaction:** Score entry and result validation workflows, likely via mobile or venue devices.
- **Consultation priority:** High
- **Validation questions:** What officiating and scoring process is used today per sport? What would make a digital scoring tool trustworthy to officials?

### Committee Heads (Medical, Food, Transportation, Billeting, Finance, Security, ICT, Media)

- **Interest:** Effective execution of their committee's function with visibility into cross-committee dependencies.
- **Responsibilities:** Committee-specific operational management.
- **Decisions influenced:** Committee-specific operational decisions (e.g., medical triage, transport routing).
- **Information needed:** Delegation counts, schedules, venue assignments relevant to their function.
- **Risks/concerns:** Siloed data preventing coordination with other committees.
- **Expected system interaction:** Committee-scoped operational modules.
- **Consultation priority:** Medium
- **Validation questions:** What does each committee's current workflow look like, and what cross-committee coordination currently fails or is delayed?

### ICT Support Teams

- **Interest:** A platform that is operable and supportable with realistic on-site infrastructure and staffing.
- **Responsibilities:** On-site technical support, device/network management.
- **Decisions influenced:** Deployment model, device provisioning, network setup.
- **Information needed:** System requirements, offline behavior, support procedures.
- **Risks/concerns:** Being unable to resolve on-site incidents with available skills/tools.
- **Expected system interaction:** Administrative/technical access for support and monitoring.
- **Consultation priority:** High
- **Validation questions:** What ICT staffing and infrastructure is realistically available at provincial venues?

### Medical Personnel

- **Interest:** Efficient incident logging and athlete safety tracking.
- **Responsibilities:** Medical incident response and documentation.
- **Decisions influenced:** Medical clearance/withdrawal decisions (human authority, not system-determined).
- **Information needed:** Athlete medical-relevant information (scoped tightly), venue locations.
- **Risks/concerns:** Medical data privacy; system slowing down emergency response.
- **Expected system interaction:** Medical operations module, likely mobile.
- **Consultation priority:** Medium
- **Validation questions:** What medical data is currently collected, and what privacy/consent framework governs it?

### Local Government Units

- **Interest:** Community/civic involvement, local logistics support.
- **Responsibilities:** May provide venues, security, or logistics support.
- **Decisions influenced:** Venue availability, local security arrangements.
- **Information needed:** Meet schedule, venue requirements.
- **Risks/concerns:** Coordination gaps between LGU support and meet operations.
- **Expected system interaction:** Indirect; possibly limited reporting access.
- **Consultation priority:** Low
- **Validation questions:** What is the LGU's formal role, if any, in provincial meets?

### Venue Owners

- **Interest:** Proper use and scheduling of their facilities.
- **Responsibilities:** Venue availability and readiness.
- **Decisions influenced:** Venue scheduling.
- **Information needed:** Venue booking schedule.
- **Risks/concerns:** Scheduling conflicts, facility misuse.
- **Expected system interaction:** Minimal; possibly a venue-scoped calendar view.
- **Consultation priority:** Low
- **Validation questions:** Are venues DepEd-owned, LGU-owned, or third-party, and does this affect access requirements?

### Security Providers

- **Interest:** Safety and order at venues.
- **Responsibilities:** On-site security operations.
- **Decisions influenced:** Access control at venues (may rely on accreditation validation).
- **Information needed:** Accreditation validity, venue schedules, incident reports.
- **Risks/concerns:** Credential fraud, inability to verify accreditation quickly.
- **Expected system interaction:** Accreditation/QR validation surface.
- **Consultation priority:** Medium
- **Validation questions:** What accreditation/access-control process is used today, and what validation speed is required at gates?

### Transportation Providers

- **Interest:** Efficient, accurate transport scheduling for delegations.
- **Responsibilities:** Executing transport logistics.
- **Decisions influenced:** Transport schedules and manifests.
- **Information needed:** Delegation counts, arrival/departure schedules.
- **Risks/concerns:** Manifest inaccuracy, last-minute changes.
- **Expected system interaction:** Transportation committee module.
- **Consultation priority:** Low
- **Validation questions:** Is transportation centrally coordinated by the organizing committee or independently by delegations?

### Food Providers

- **Interest:** Accurate meal counts and delivery schedules.
- **Responsibilities:** Meal preparation and distribution.
- **Decisions influenced:** Meal counts, distribution logistics.
- **Information needed:** Delegation/athlete counts, dietary requirements if tracked.
- **Risks/concerns:** Meal count inaccuracy leading to shortage/waste.
- **Expected system interaction:** Food committee module, possibly QR-based meal validation.
- **Consultation priority:** Low
- **Validation questions:** How are meal counts currently tracked and validated?

### Billeting Providers

- **Interest:** Accurate accommodation assignments.
- **Responsibilities:** Housing delegations during multi-day meets.
- **Decisions influenced:** Room/accommodation assignments.
- **Information needed:** Delegation counts, arrival/departure dates.
- **Risks/concerns:** Assignment conflicts, overcrowding.
- **Expected system interaction:** Billeting committee module.
- **Consultation priority:** Low
- **Validation questions:** Is billeting a standard feature of provincial meets, or does it vary?

### Media Organizations

- **Interest:** Timely, accurate access to schedules, results, and official information.
- **Responsibilities:** Public reporting on the meet.
- **Decisions influenced:** None within the system; consumers of published information.
- **Information needed:** Published schedules, results, medal tally, advisories, and possibly media accreditation.
- **Risks/concerns:** Delayed or inaccurate public information reflecting poorly on their reporting.
- **Expected system interaction:** Public portal; media accreditation surface if scoped.
- **Consultation priority:** Low
- **Validation questions:** What media accreditation process, if any, currently exists?

### Auditors

- **Interest:** Verifiable, traceable records of official decisions and financial/operational activity.
- **Responsibilities:** Post-event or ongoing compliance review.
- **Decisions influenced:** Audit findings, compliance recommendations.
- **Information needed:** Audit logs, official decision records, financial/operational reports.
- **Risks/concerns:** Insufficient traceability preventing effective audit.
- **Expected system interaction:** Read-only audit/reporting access.
- **Consultation priority:** Medium
- **Validation questions:** What audit standards or requirements currently apply to DepEd-run meets?

### Data Privacy and Legal Stakeholders

- **Interest:** Compliance with applicable data privacy law (e.g., handling of minors' personal and medical data).
- **Responsibilities:** Privacy policy definition, legal compliance review.
- **Decisions influenced:** Data handling policy, consent requirements, retention rules.
- **Information needed:** Data flows, data categories collected, retention practices.
- **Risks/concerns:** Non-compliance with data privacy law; inadequate consent mechanisms for minors.
- **Expected system interaction:** Policy and compliance review, not operational use.
- **Consultation priority:** High
- **Validation questions:** What data privacy law and DepEd-specific data protection policy applies to this system? Is a formal Privacy Impact Assessment required before Phase 0.2 proceeds?

### Vendors and Implementation Partners

- **Interest:** Successful delivery of the platform per scope and quality expectations.
- **Responsibilities:** Development, deployment, and support of PMMS.
- **Decisions influenced:** Technical architecture, implementation approach.
- **Information needed:** Confirmed scope, priorities, acceptance criteria.
- **Risks/concerns:** Scope ambiguity, unvalidated assumptions causing rework.
- **Expected system interaction:** Full development/administrative access during build.
- **Consultation priority:** High
- **Validation questions:** What governance/change-control process will apply between the vendor/implementation team and DepEd sponsors?

### Public Spectators

- **Interest:** Access to schedules, results, and general meet information.
- **Responsibilities:** None.
- **Decisions influenced:** None.
- **Information needed:** Public schedules, results, medal tally, advisories.
- **Risks/concerns:** Inaccurate or delayed public information.
- **Expected system interaction:** Public portal (read-only).
- **Consultation priority:** Low
- **Validation questions:** None at this phase.

---

## Consultation Sequencing Summary

| Priority | Stakeholder Groups |
|---|---|
| High | DepEd Leadership, Meet Organizing Committee, Schools Division Office, Coaches, Tournament Managers, Technical Officials, ICT Support Teams, Data Privacy and Legal Stakeholders, Vendors and Implementation Partners |
| Medium | Participating Schools, Delegations, Athletes, Committee Heads, Medical Personnel, Security Providers, Auditors |
| Low | Parents and Guardians, Local Government Units, Venue Owners, Transportation Providers, Food Providers, Billeting Providers, Media Organizations, Public Spectators |

This sequencing is a Phase 0.1 proposal to inform Phase 0.2 planning and does not commit specific individuals or dates.
