# DdOPAA Provincial Meet 2026 — Final TWG and Operational Role Matrix

## Authority model

- **Top Management / Overall Chairperson:** executive direction, policy oversight, approval, and escalation.
- **Incident Command:** incident coordination and emergency command.
- **DSAC:** validates athlete profile, eligibility documents, school/delegation/category consistency, and athlete qualification.
- **Medical:** evaluates medical requirements and controls medical clearance decisions.
- **District Sports Coordinator (DSC):** monitors readiness of schools, coaches, athletes, documents, eligibility and medical-status completion in the assigned School District; does not approve DSAC or Medical decisions.
- **Municipal / Team Manager:** municipality-wide monitoring and delegation readiness; does not replace DSAC/Medical approving authority.
- **Tournament Manager (TM):** sport/event operational lead for assigned MeetSport/category.
- **Assistant TM:** supports the Tournament Manager within assigned scope.
- **Tournament Secretary:** sport-level records, schedules, result-document routing and secretariat support.
- **Tournament ICT:** sport-level PMMS/live-score/device/connectivity support; distinct from central ICT.
- **Technical Official (TO):** performs sport-specific officiating/technical functions and records required competition outputs.
- **Results Committee:** confirms submitted competition results before finalization, standings and medal tally updates.
- **Coach:** self-registers, selects authorized sport assignment(s), registers/enrolls athletes, uploads requirements, submits entries, and tracks returned/pending registrations.

## TWG units from the final workbook

| TWG / Unit | Primary Responsibility |
|---|---|
| Top Management | Provides executive direction, policy oversight, approval, and escalation support for the Provincial Meet. |
| Incident Command | Coordinates overall incident command, emergency decision-making, and cross-team response during meet operations. |
| Sports Lines Up and Placement | Coordinates sports line-up, placements, event sequencing, and related competition deployment. |
| Secretariat | Provides central administrative documentation, records, communications, minutes, and official secretariat support. |
| Grievance | Receives, documents, routes, and coordinates resolution of grievances and formal concerns. |
| Opening and Closing Program | Plans and coordinates opening and closing ceremonies, program flow, participants, and related logistics. |
| Decoration | Plans and manages approved venue/program decorations and visual preparation. |
| Playing Venue | Coordinates venue readiness, facility assignments, venue concerns, and operational requirements. |
| Food / Meals | Coordinates meal schedules, distribution information, and food-related operational requirements. |
| Usherettes | Provides guest assistance, ushering, seating guidance, and front-of-house support during official activities. |
| Peace and Security | Coordinates safety, access control, crowd management, security concerns, and liaison with security personnel. |
| Billeting | Coordinates delegation accommodation, host-school billeting, capacity, assignments, and billeting concerns. |
| Finance | Coordinates approved financial documentation, disbursement support, and finance-related meet records within authorized processes. |
| Logistics | Coordinates equipment, supplies, movement, staging, and operational logistics across meet activities. |
| Medical | Provides medical evaluation, medical clearance, first-aid/response coordination, referral, and health-related event support. |
| Learners Rights and Protection Desk Committee | Supports learner protection, safeguarding, rights concerns, referral, and appropriate response during the meet. |
| Quality Assurance, Monitoring & Evaluation | Monitors implementation quality, compliance, operational performance, and post-activity evaluation. |
| Water, Light & Sanitation | Coordinates water, electrical/light readiness, sanitation, and related facility concerns. |
| Information | Coordinates official information, public advisories, approved communication, and information dissemination. |
| Clean & Green | Coordinates cleanliness, waste management support, venue environmental readiness, and clean-up activities. |
| Event Secretariat | Provides sport/event-level documentation support, forms, records routing, and coordination with event officials. |
| Support Staff | Provides general operational support as assigned by meet management. |
| DSAC | Evaluates and validates athlete profile, eligibility documents, school/delegation consistency, category qualifications, and athlete eligibility status. |
| Announcers | Provides approved event announcements, public-address support, and official program/event information. |
| Kitchen Personnel | Supports food preparation and kitchen operations under the Food/Meals function. |

## User-account rule

All personnel listed in the **TM and TO** worksheet are migrated as people + meet-sport assignments and are placed in the system-user provisioning queue. The migration **does not invent email addresses or passwords**. Codex must provision them through the existing Laravel user/account workflow and force initial password setup.

Coaches are deliberately **not pre-created** from this workbook. Coach self-registration remains enabled; after registration they select/request their sport assignment(s), then enroll athletes within approved municipality/school/sport scope.